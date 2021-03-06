<?php

//@todo Alter so that vendor classes autoloaded with namespaces
require_once BASE_PATH . '/vendor/orientdb-php/orientdb-php/OrientDB/OrientDB.php';

/**
 * Notes:
 * Name has to be suffixed with "Database" @see DatabaseAdmin::doBuild()
 * Should now() be abstract method in SS_Database, required by @see Controller::init()
 * Should getVersion() be abstract method in SS_Database, required by DatabaseAdmin::doBuild()?
 * getVersion() is currently hard coded, no function in OrientDB to get the version currently
 * IdColumn() abstract method, required by SS_Database::requireTable()?
 * supportsCollations abstract method required by SS_Database::requireField()?
 */
class OrientDatabase extends SS_Database {

	/**
	 * Miscellaneous DB instance variables
	 * @todo document these
	 */
	protected $db;
	protected $dbOpen;
	protected $tableList = array(); //@todo this is already in parent class actually, clashes?
	protected $lastInsertID;

	/**
	 * Connection to the DBMS.
	 * @var object
	 */
	protected $dbConn;

	/**
	 * True if we are connected to a database.
	 * @var boolean
	 */
	protected $active;

	/**
	 * The name of the database.
	 * @var string
	 */
	protected $database;

	/*
	 * This holds the parameters that the original connection was created with,
	 * so we can switch back to it if necessary (used for unit tests)
	 */
	protected $parameters;

	/**
	 * Connect to an OrientDB database.
	 * 
	 * @param array $parameters An map of parameters, which should include:
	 *  - database: The database to connect to
	 *  - path: the path to the OrientDB database file
	 *  - key: the encryption key (needs testing)
	 *  - memory: use the faster In-Memory database for unit tests
	 */
	public function __construct($parameters) {

		//We will store these connection parameters for use elsewhere (ie, unit tests)
		$this->parameters = $parameters;
		$this->connectDatabase();
	}

	/*
	 * Uses whatever connection details are in the $parameters array to connect to a database of a given name
	 */
	public function connectDatabase() {

		$parameters = $this->parameters;

		$server = $parameters['server'];
		$port = $parameters['port'];
		$serverUsername = $parameters['serverusername'];
		$serverPassword = $parameters['serverpassword'];
		$username = $parameters['username'];
		$password = $parameters['password'];
		$dbName = $parameters['database'];

		try {
			$this->db = new OrientDB($server, $port);
		}
		catch (Exception $e) {
			$this->databaseError('Failed to connect to Orient database: ' . $e->getMessage());
			return false;
		}

		//Connect to server - requires different user/pass to the DB itself
		try {
			$this->dbConn = $this->db->connect($serverUsername, $serverPassword);
		}
		catch (OrientDBException $e) {
			$this->databaseError('Failed to connect to Orient database: ' . $e->getMessage());
			return false;
		}

		//Connect to database
		try {
			$clusters = $this->dbOpen = $this->db->DBOpen($dbName, $username, $password);

			//Get the table list @see tableList()
			foreach ($clusters['clusters'] as $class) {
				if ($class instanceof stdClass) {
					$this->tableList[strtolower($class->name)] = $class->name;
				}
			}
			
			//By virtue of getting here, the connection is active:
			$this->active=true;
			$this->database = $dbName;
		}
		catch (OrientDBException $e) {
			$this->databaseError('Failed to connect to Orient database: ' . $e->getMessage());
			return false;
		}

		return true;
	}

	/**
	 * Execute the given SQL query.
	 * This abstract function must be defined by subclasses as part of the actual implementation.
	 * It should return a subclass of SS_Query as the result.
	 * @param string $sql The SQL query to execute
	 * @param int $errorLevel The level of error reporting to enable for the query
	 * @return SS_Query
	 */
	public function query($sql, $errorLevel = E_USER_ERROR) {

		if(isset($_REQUEST['previewwrite']) && in_array(strtolower(substr($sql,0,strpos($sql,' '))), array('insert','update','delete','replace'))) {
			Debug::message("Will execute: $sql");
			return;
		}

		if(isset($_REQUEST['showqueries'])) { 
			$starttime = microtime(true);
		}

		//Handle ends up being the result set in array form most of the time
		$handle = $this->db->query($sql);

		//If just a single record is returned then push it into an array, can be result of inserting records
		if ($handle instanceof OrientDBRecord) {

			//Set the last insert ID, @todo issue with this being triggered for queries other than inserts
			$this->lastInsertID = (string) $handle->recordID;
			$handle = array($handle);
		}

		//If nothing is returned make sure we "cast" to an array
		if (!$handle) {
			$handle = array();
		}

		//If 1/true is returned do nothing

		if(isset($_REQUEST['showqueries'])) {
			$endtime = round(microtime(true) - $starttime,4);
			Debug::message("\n$sql\n{$endtime}ms\n", false);
		}

		DB::$lastQuery = $handle;

		return new OrientQuery($this, $handle);
	}

	/**
	 * Convert a SQLQuery object into a SQL statement
	 * Caution: Expects correctly quoted and escaped SQL fragments.
	 * 
	 * @param $query SQLQuery
	 */
	public function sqlQueryToString(SQLQuery $query) {

		if ($query->getDelete()) {
			//Appended space at the end of string causing an issue but this might not be the best solution
			//@see sqlSelectToString() sqlFromToString
			$text = 'DELETE';
		} 
		else if ($traverse = $query->getTraverse()) {
			//Build the traverse string here
			$text = $this->sqlTraverseToString($traverse);
		}
		else {
			$text = $this->sqlSelectToString($query->getSelect(), $query->getDistinct());
		}

		if($query->getFrom()) $text .= $this->sqlFromToString($query->getFrom());
		if($query->getWhere()) $text .= $this->sqlWhereToString($query->getWhere(), $query->getConnective());

		// these clauses only make sense in SELECT queries, not DELETE
		if(!$query->getDelete()) {
			if($query->getGroupBy()) $text .= $this->sqlGroupByToString($query->getGroupBy());
			if($query->getHaving()) $text .= $this->sqlHavingToString($query->getHaving());
			if($query->getOrderBy()) $text .= $this->sqlOrderByToString($query->getOrderBy());
			if($query->getLimit()) $text .= $this->sqlLimitToString($query->getLimit());
		}

		// SS_Log::log(new Exception(print_r($text, true)), SS_Log::NOTICE);

		return $text;
	}

	public function sqlTraverseToString($traverse) {

		$component = array_pop($traverse);
		$text = "TRAVERSE $component";
		return $text;
	}

	/**
	 * Get the autogenerated ID from the previous INSERT query.
	 * @return int
	 */
	public function getGeneratedID($table) {
		return $this->lastInsertID;
	}

	/**
	 * Check if the connection to the database is active.
	 * @return boolean
	 */
	public function isActive() {
		return $this->active ? true : false;
	}

	/**
	 * Create the database and connect to it. This can be called if the
	 * initial database connection is not successful because the database
	 * does not exist.
	 * 
	 * It takes no parameters, and should create the database from the information
	 * specified in the constructor.
	 * 
	 * @return boolean Returns true if successful
	 */
	public function createDatabase() {

		$result = $this->db->DBCreate($this->database, OrientDB::DB_TYPE_LOCAL);

		//@todo this needs to use configuration details properly
		$this->db->DBOpen($this->database, 'admin', 'admin');
		$this->active = true;

		// $this->tableList = $this->fieldList = $this->indexList = null;
		$this->fieldList = $this->indexList = null;

		return $this->active;
	}

	/**
	 * Build the connection string from input
	 * @param array $parameters The connection details
	 * @return string $connect The connection string
	 **/
	public function getConnect($parameters) {
		//Not implemented in other DB connector classes
		return null;
	}

	/**
	 * Helper method to get parent class which has a class in the database.
	 * 
	 * @param  string $table Class name we are finding parent for
	 * @return mixed  string|null Returns name of parent class if it exists
	 */
	private function getParent($table) {
		$ancestry = ClassInfo::ancestry($table, true);
		unset($ancestry[$table]);
		$ancestry = array_reverse($ancestry);
		return array_shift($ancestry);
	}

	/**
	 * Create a new table.
	 *
	 * @todo support for indexes
	 * 
	 * @param $tableName The name of the table
	 * @param $fields A map of field names to field types
	 * @param $indexes A map of indexes
	 * @param $options An map of additional options.  The available keys are as follows:
	 *   - 'MSSQLDatabase'/'MySQLDatabase'/'PostgreSQLDatabase' - database-specific options such as "engine" for MySQL.
	 *   - 'temporary' - If true, then a temporary table will be created
	 * @return The table name generated.  This may be different from the table name, for example with temporary tables.
	 */
	public function createTable($table, $fields = null, $indexes = null, $options = null, $advancedOptions = null) {

		//Get parent class to inherit from
		$parentClass = $this->getParent($table);

		//Create the table
		try {

			if ($parentClass) {
				$createTableSQL = "create class $table extends $parentClass";
			}
			else {
				$createTableSQL = "create class $table";
			}

			$tableResult = $this->db->command(OrientDB::COMMAND_QUERY, $createTableSQL);
		}
		catch (OrientDBException $e) {
			// SS_Log::log(new Exception(print_r($e->getMessage(), true)), SS_Log::NOTICE);
		}

		//Create fields for each table
		if ($fields) foreach ($fields as $fieldName => $fieldType) {

			try {
				$createFieldSQL = "create property {$table}.{$fieldName} $fieldType";
				$fieldResult = $this->db->command(OrientDB::COMMAND_QUERY, $createFieldSQL);
			}
			catch (OrientDBException $e) {
				// SS_Log::log(new Exception(print_r($e->getMessage(), true)), SS_Log::NOTICE);
			}
		}
	}

	/**
	 * Alter a table's schema.
	 */
	public function alterTable($table, $newFields = null, $newIndexes = null, $alteredFields = null,
			$alteredIndexes = null, $alteredOptions=null, $advancedOptions=null) {
		SS_Log::log(new Exception(print_r(__method__, true)), SS_Log::NOTICE);
		exit(__method__);
	}

	/**
	 * Rename a table.
	 * @param string $oldTableName The old table name.
	 * @param string $newTableName The new table name.
	 */
	public function renameTable($oldTableName, $newTableName) {
		SS_Log::log(new Exception(print_r(__method__, true)), SS_Log::NOTICE);
		exit(__method__);
	}

	/**
	 * Create a new field on a table.
	 * @param string $table Name of the table.
	 * @param string $field Name of the field to add.
	 * @param string $spec The field specification, eg 'INTEGER NOT NULL'
	 */
	public function createField($table, $field, $spec) {
		SS_Log::log(new Exception(print_r(__method__, true)), SS_Log::NOTICE);
		exit(__method__);
	}

	/**
	 * Change the database column name of the given field.
	 * 
	 * @param string $tableName The name of the tbale the field is in.
	 * @param string $oldName The name of the field to change.
	 * @param string $newName The new name of the field
	 */
	public function renameField($tableName, $oldName, $newName) {
		SS_Log::log(new Exception(print_r(__method__, true)), SS_Log::NOTICE);
		exit(__method__);
	}

	/**
	 * Get a list of all the fields for the given table.
	 * Returns a map of field name => field spec.
	 * @param string $table The table name.
	 * @return array
	 */
	public function fieldList($table) {

		$properties = array();
		$schema = OrientSchema::get()
			->filter(array('Class' => $table))
			->first();

		if ($schema && $schema->exists()) {
			$properties = json_decode(stripslashes($schema->Properties), true);
		}

		return $properties;
	}

	/**
	 * Returns a list of all the tables in the database.
	 * Can only retrieve this from the clusters returned by DBOpen()
	 *
	 * @see  self::connectDatabase()
	 * @return array
	 */
	public function tableList() {

		//@todo this could use the OrientSchema table instead but would require extra DB call
		return $this->tableList;
	}
	
	/**
	 * Returns true if the given table exists in the database
	 */
	public function hasTable($tableName) {

		//@todo for some reason $this->tableList is empty array at this point, need to debug
		return in_array($tableName, $this->tableList);
	}

	/**
	 * Returns the enum values available on the given field
	 */
	public function enumValuesForField($tableName, $fieldName) {
		return;

		SS_Log::log(new Exception(print_r(__method__, true)), SS_Log::NOTICE);
		exit(__method__);
	}

	/**
	 * Returns an escaped string.
	 *
	 * @todo implement add slashes properly
	 * 
	 * @param string
	 * @return string - escaped string
	 */
	public function addslashes($val) {

		//@todo @orientdb This needs to be impelmented properly!
		return $val;
	}


	/**
	 * Generate a WHERE clause for text matching.
	 * 
	 * @param String $field Quoted field name
	 * @param String $value Escaped search. Can include percentage wildcards.
	 * @param boolean $exact Exact matches or wildcard support.
	 * @param boolean $negate Negate the clause.
	 * @param boolean $caseSensitive Perform case sensitive search.
	 * @return String SQL
	 */
	public function comparisonClause($field, $value, $exact = false, $negate = false, $caseSensitive = false) {

		if ($exact && $caseSensitive === null) {
			$comp = ($negate) ? '!=' : '=';
		} 
		else {
			$comp = ($caseSensitive) ? 'LIKE BINARY' : 'LIKE';
			if ($negate) {
				$comp = 'NOT ' . $comp;
			}
		}
		
		return sprintf("%s %s '%s'", $field, $comp, $value);
	}

	/**
	 * function to return an SQL datetime expression that can be used with the adapter in use
	 * used for querying a datetime in a certain format
	 * @param string $date to be formated, can be either 'now', literal datetime like '1973-10-14 10:30:00' or
	 *                     field name, e.g. '"SiteTree"."Created"'
	 * @param string $format to be used, supported specifiers:
	 * %Y = Year (four digits)
	 * %m = Month (01..12)
	 * %d = Day (01..31)
	 * %H = Hour (00..23)
	 * %i = Minutes (00..59)
	 * %s = Seconds (00..59)
	 * %U = unix timestamp, can only be used on it's own
	 * @return string SQL datetime expression to query for a formatted datetime
	 */
	public function formattedDatetimeClause($date, $format) {
		SS_Log::log(new Exception(print_r(__method__, true)), SS_Log::NOTICE);
		exit(__method__);
	}

	/**
	 * function to return an SQL datetime expression that can be used with the adapter in use
	 * used for querying a datetime addition
	 * @param string $date, can be either 'now', literal datetime like '1973-10-14 10:30:00' or field name,
	 *                      e.g. '"SiteTree"."Created"'
	 * @param string $interval to be added, use the format [sign][integer] [qualifier], e.g. -1 Day, +15 minutes,
	 *                         +1 YEAR
	 * supported qualifiers:
	 * - years
	 * - months
	 * - days
	 * - hours
	 * - minutes
	 * - seconds
	 * This includes the singular forms as well
	 * @return string SQL datetime expression to query for a datetime (YYYY-MM-DD hh:mm:ss) which is the result of
	 *                the addition
	 */
	public function datetimeIntervalClause($date, $interval) {
		SS_Log::log(new Exception(print_r(__method__, true)), SS_Log::NOTICE);
		exit(__method__);
	}

	/**
	 * function to return an SQL datetime expression that can be used with the adapter in use
	 * used for querying a datetime substraction
	 * @param string $date1, can be either 'now', literal datetime like '1973-10-14 10:30:00' or field name
	 *                       e.g. '"SiteTree"."Created"'
	 * @param string $date2 to be substracted of $date1, can be either 'now', literal datetime
	 *                      like '1973-10-14 10:30:00' or field name, e.g. '"SiteTree"."Created"'
	 * @return string SQL datetime expression to query for the interval between $date1 and $date2 in seconds which
	 *                is the result of the substraction
	 */
	public function datetimeDifferenceClause($date1, $date2) {
		SS_Log::log(new Exception(print_r(__method__, true)), SS_Log::NOTICE);
		exit(__method__);
	}

	/**
	 * Can the database override timezone as a connection setting,
	 * or does it use the system timezone exclusively?
	 * 
	 * @return Boolean
	 */
	public function supportsTimezoneOverride() {
		return false;
	}

	/*
	 * Does this database support transactions?
	 * 
	 * @return boolean
	 */
	public function supportsTransactions() {
		//TODO transaction support
		return false;
	}

	/*
	 * Start a prepared transaction
	 * See http://developer.postgresql.org/pgdocs/postgres/sql-set-transaction.html for details on
	 * transaction isolation options
	 */
	public function transactionStart($transaction_mode=false, $session_characteristics=false) {
		SS_Log::log(new Exception(print_r(__method__, true)), SS_Log::NOTICE);
		exit(__method__);
	}

	/*
	 * Create a savepoint that you can jump back to if you encounter problems
	 */
	public function transactionSavepoint($savepoint) {
		SS_Log::log(new Exception(print_r(__method__, true)), SS_Log::NOTICE);
		exit(__method__);
	}

	/*
	 * Rollback or revert to a savepoint if your queries encounter problems
	 * If you encounter a problem at any point during a transaction, you may
	 * need to rollback that particular query, or return to a savepoint
	 */
	public function transactionRollback($savepoint=false) {
		SS_Log::log(new Exception(print_r(__method__, true)), SS_Log::NOTICE);
		exit(__method__);
	}

	/*
	 * Commit everything inside this transaction so far
	 */
	public function transactionEnd() {
		SS_Log::log(new Exception(print_r(__method__, true)), SS_Log::NOTICE);
		exit(__method__);
	}

	/**
	 * Returns the database-specific version of the now() function, in this case sysdate()
	 * @see https://github.com/orientechnologies/orientdb/wiki/SQL-Where
	 *
	 * @return string SQL for inserting current date
	 */
	public function now() {
		return "sysdate('yyyy-MM-dd HH:mm:ss')";
	}

	/**
	 * Get the version of OrientDB, currently there is no function in OrientDB to return the version 
	 * so hardcoded for now.
	 * 
	 * @return string
	 */
	public function getVersion() {
		return '1.5.1';
	}

	/**
	 * This returns the column which is the primary key for each table
	 *
	 * There is no auto increment functionality in OrientDB and in fact the unique ID should 
	 * really be RID.
	 * @see  https://code.google.com/p/orient/wiki/Concepts#RecordID
	 * @see  https://github.com/orientechnologies/orientdb/issues/367
	 * @see  https://groups.google.com/forum/#!msg/orient-database/BXRDvpaUpvo/mCJtTSXEDWIJ
	 *
	 * @return string
	 */
	public function IdColumn(){
		return 'string';
	}

	/**
	 * Returns true if this database supports collations
	 * 
	 * @return boolean
	 */
	public function supportsCollations() {
		return false;
	}

	/**
	 * Generate the following table in the database, modifying whatever already exists
	 * as necessary.
	 * @todo Change detection for CREATE TABLE $options other than "Engine"
	 * 
	 * @param string $table The name of the table
	 * @param string $fieldSchema A list of the fields to create, in the same form as DataObject::$db
	 * @param string $indexSchema A list of indexes to create. See {@link requireIndex()}
	 * @param array $options
	 */
	public function requireTable($table, $fieldSchema = null, $indexSchema = null, $hasAutoIncPK=false, $options = Array(), $extensions=false) {

		//We never have auto incrementing ID field in OrientDB
		$hasAutoIncPK = false;

		if (!isset($this->tableList[strtolower($table)])) {
			$this->transCreateTable($table, $options, $extensions);
			$this->alterationMessage("Table $table: created","created");
		} 
		else {
			if(Config::inst()->get('Database', 'check_and_repair_on_build')) {
				$this->checkAndRepairTable($table, $options);
			} 
			
			// Check if options changed
			$tableOptionsChanged = false;
			if(isset($options[get_class($this)]) || true) {
				if(isset($options[get_class($this)])) {
					if(preg_match('/ENGINE=([^\s]*)/', $options[get_class($this)], $alteredEngineMatches)) {
						$alteredEngine = $alteredEngineMatches[1];
						$tableStatus = DB::query(sprintf(
							'SHOW TABLE STATUS LIKE \'%s\'',
							$table
						))->first();
						$tableOptionsChanged = ($tableStatus['Engine'] != $alteredEngine);
					}
				}
			}
			
			if($tableOptionsChanged || ($extensions && DB::getConn()->supportsExtensions())) 
				$this->transAlterTable($table, $options, $extensions);
		}

		//DB ABSTRACTION: we need to convert this to a db-specific version:
		$this->requireField($table, 'ID', DB::getConn()->IdColumn(false, $hasAutoIncPK));

		//Update the database schema, which is all kept in a single table in OrientDB
		$this->writeSchema($table, $fieldSchema);

		// Create custom fields
		if ($fieldSchema) {

			foreach($fieldSchema as $fieldName => $fieldSpec) {
				
				//Is this an array field?
				$arrayValue='';
				if (strpos($fieldSpec, '[')!==false) {

					//If so, remove it and store that info separately
					$pos=strpos($fieldSpec, '[');
					$arrayValue=substr($fieldSpec, $pos);
					$fieldSpec=substr($fieldSpec, 0, $pos);
				}

				$fieldObj = Object::create_from_string($fieldSpec, $fieldName);
				$fieldObj->arrayValue = $arrayValue;
				$fieldObj->setTable($table);
				$fieldObj->requireField();
			}
		}
		
		// Create custom indexes
		if($indexSchema) {
			foreach($indexSchema as $indexName => $indexDetails) {
				$this->requireIndex($table, $indexName, $indexDetails);
			}
		}
	}

	/**
	 * Generate the given index in the database, modifying whatever already exists as necessary.
	 * 
	 * The keys of the array are the names of the index.
	 * The values of the array can be one of:
	 *  - true: Create a single column index on the field named the same as the index.
	 *  - array('type' => 'index|unique|fulltext', 'value' => 'FieldA, FieldB'): This gives you full
	 *    control over the index.
	 * 
	 * @param string $table The table name.
	 * @param string $index The index name.
	 * @param string|boolean $spec The specification of the index. See requireTable() for more information.
	 */
	public function requireIndex($table, $index, $spec) {

		$newTable = false;
		
		//DB Abstraction: remove this ===true option as a possibility?
		if($spec === true) {
			$spec = "(\"$index\")";
		}
		
		//Indexes specified as arrays cannot be checked with this line: (it flattens out the array)
		if(!is_array($spec)) {
			$spec = preg_replace('/\s*,\s*/', ',', $spec);
		}

		if(!isset($this->tableList[strtolower($table)])) $newTable = true;

		if(!$newTable && !isset($this->indexList[$table])) {
			$this->indexList[$table] = $this->indexList($table);
		}
						
		//Fix up the index for database purposes
		$index = DB::getConn()->getDbSqlDefinition($table, $index, null, true);
		
		//Fix the key for database purposes
		$index_alt = DB::getConn()->modifyIndex($index, $spec);
	
		if(!$newTable) {
			if(isset($this->indexList[$table][$index_alt])) {
				if(is_array($this->indexList[$table][$index_alt])) {
					$array_spec = $this->indexList[$table][$index_alt]['spec'];
				} else {
					$array_spec = $this->indexList[$table][$index_alt];
				}
			}
		}

		if ($newTable || !isset($this->indexList[$table][$index_alt])) {

			$this->transCreateIndex($table, $index, $spec);
			$this->alterationMessage("Index $table.$index: created as " . DB::getConn()->convertIndexSpec($spec), "created");
		} 
		else if ($array_spec != DB::getConn()->convertIndexSpec($spec)) {

			$this->transAlterIndex($table, $index, $spec);
			$spec_msg=DB::getConn()->convertIndexSpec($spec);
			$this->alterationMessage("Index $table.$index: changed to $spec_msg" . " <i style=\"color: #AAA\">(from {$array_spec})</i>","changed");			
		}
	}

	/**
	 * Generate the given field on the table, modifying whatever already exists as necessary.
	 * @param string $table The table name.
	 * @param string $field The field name.
	 * @param array|string $spec The field specification. If passed in array syntax, the specific database
	 * 	driver takes care of the ALTER TABLE syntax. If passed as a string, its assumed to
	 * 	be prepared as a direct SQL framgment ready for insertion into ALTER TABLE. In this case you'll
	 * 	need to take care of database abstraction in your DBField subclass.  
	 */
	public function requireField($table, $field, $spec) {

		//@todo this is starting to get extremely fragmented.
		//There are two different versions of $spec floating around, and their content changes depending
		//on how they are structured.  This needs to be tidied up.
		$fieldValue = null;
		$newTable = false;

		// backwards compatibility patch for pre 2.4 requireField() calls
		$spec_orig = $spec;
		
		if (!is_string($spec)) {
			$spec['parts']['name'] = $field;
			$spec_orig['parts']['name'] = $field;

			//Convert the $spec array into a database-specific string
			$spec = DB::getConn()->$spec['type']($spec['parts'], true);
		}
		
		if (!isset($this->tableList[strtolower($table)])) $newTable = true;

		if (!$newTable && !isset($this->fieldList[$table])) {
			$this->fieldList[$table] = $this->fieldList($table);
		}

		if (is_array($spec)) {
			$specValue = DB::getConn()->$spec_orig['type']($spec_orig['parts']);
		} 
		else {
			$specValue = $spec;
		}

		// We need to get db-specific versions of the ID column:
		if ($spec_orig == DB::getConn()->IdColumn() || $spec_orig == DB::getConn()->IdColumn(true)) {
			$specValue=DB::getConn()->IdColumn(true);
		}
		
		if (!$newTable) {
			if(isset($this->fieldList[$table][$field])) {
				if(is_array($this->fieldList[$table][$field])) {
					$fieldValue = $this->fieldList[$table][$field]['data_type'];
				} else {
					$fieldValue = $this->fieldList[$table][$field];
				}
			}
		}
		
		// Get the version of the field as we would create it. This is used for comparison purposes to see if the
		// existing field is different to what we now want
		if (is_array($spec_orig)) {
			$spec_orig = DB::getConn()->$spec_orig['type']($spec_orig['parts']);
		}

		if ($newTable || $fieldValue=='') {

			$this->transCreateField($table, $field, $spec_orig);
			$this->alterationMessage("Field {$table}.{$field}: created as $spec_orig","created");
		} 
		else if ($fieldValue != $specValue) {

			// If enums/sets are being modified, then we need to fix existing data in the table.
			// Update any records where the enum is set to a legacy value to be set to the default.
			// One hard-coded exception is SiteTree - the default for this is Page.
			foreach(array('enum','set') as $enumtype) {
				if(preg_match("/^$enumtype/i",$specValue)) {
					$newStr = preg_replace("/(^$enumtype\s*\(')|('$\).*)/i","",$spec_orig);
					$new = preg_split("/'\s*,\s*'/", $newStr);
				
					$oldStr = preg_replace("/(^$enumtype\s*\(')|('$\).*)/i","", $fieldValue);
					$old = preg_split("/'\s*,\s*'/", $newStr);

					$holder = array();
					foreach($old as $check) {
						if(!in_array($check, $new)) {
							$holder[] = $check;
						}
					}
					if(count($holder)) {
						$default = explode('default ', $spec_orig);
						$default = $default[1];
						if($default == "'SiteTree'") $default = "'Page'";
						$query = "UPDATE \"$table\" SET $field=$default WHERE $field IN (";
						for($i=0;$i+1<count($holder);$i++) {
							$query .= "'{$holder[$i]}', ";
						}
						$query .= "'{$holder[$i]}')";
						DB::query($query);
						$amount = DB::affectedRows();
						$this->alterationMessage("Changed $amount rows to default value of field $field"
							. " (Value: $default)");
					}
				}
			}
			$this->transAlterField($table, $field, $spec_orig);
			$this->alterationMessage(
				"Field $table.$field: changed to $specValue <i style=\"color: #AAA\">(from {$fieldValue})</i>",
				"changed"
			);
		}
	}

	/**
	 * Writes the schema to a "table" in OrientDB, this is useful for determining which classes and 
	 * properties exist in the database as queries such as "list classes" and "desc <class>" are not 
	 * currently supported by the PHP connector.
	 * 
	 * @param  string $table  Name of table/class
	 * @param  array $schema  List of properties => property types
	 * @return string         ID of the record
	 */
	private function writeSchema($table, $schema) {

		//@todo this needs to be safe for using on the first DB build
		//@todo there appears to be a bug where /dev/build needs to be run twice?
		try {
			$schematic = OrientSchema::get()
				->filter(array('Class' => $table))
				->first();

			if (!$schematic || !$schematic->exists()) {
				$schematic = OrientSchema::create();
			}

			$schematic->Class = $table;
			$schematic->Properties = addslashes(json_encode($schema));
			return $schematic->write();
		}
		catch (Exception $e) {
			return true;
		}
	}

	public function getDbSqlDefinition($tableName, $indexName, $indexSpec){
		return $indexName;
	}

	/*
	 * Change the index name depending on database requirements.
	 */
	public function modifyIndex($index){
		return $index;
	}

	/**
	 * This takes the index spec which has been provided by a class (ie static $indexes = blah blah)
	 * and turns it into a proper string.
	 * Some indexes may be arrays, such as fulltext and unique indexes, and this allows database-specific
	 * arrays to be created. See {@link requireTable()} for details on the index format.
	 *
	 * @todo Support for indexes @see https://github.com/orientechnologies/orientdb/wiki/Indexes
	 *
	 * @param string|array $indexSpec
	 * @return string MySQL compatible ALTER TABLE syntax
	 */
	public function convertIndexSpec($indexSpec) {
		return '';
	}

	/**
	 * @todo Support for indexes @see https://github.com/orientechnologies/orientdb/wiki/Indexes
	 * 
	 * @param string $indexName
	 * @param string|array $indexSpec See {@link requireTable()} for details
	 * @return string MySQL compatible ALTER TABLE syntax
	 */
	protected function getIndexSqlDefinition($indexName, $indexSpec=null) {
		return '';
	}

	/**
	 * Returns the SQL command to get all the tables in this database
	 *
	 * @todo The query "list classes" is not currently supported by the PHP connector
	 */
	public function allTablesSQL(){
		return '';
	}

	/**
	 * Enum type
	 *
	 * OrientDB does not have support for default values @see https://github.com/orientechnologies/orientdb/issues/665
	 *
	 * @todo Copy approach from SQLite3Database::enum()
	 * 
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function enum($values){
		return 'string';
	}

	/**
	 * DateTime type
	 *
	 * OrientDB Datetime and Date fields are integers basically @see https://code.google.com/p/orient/wiki/Types
	 * 
	 * @params array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function ss_datetime($values, $asDbValue=false){
		return 'string';
	}

	/**
	 * Return a varchar type-formatted string
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function varchar($values){
		return 'string';
	}

	/**
	 * Return a text type-formatted string
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function text($values){
		return 'string';
	}

	/**
	 * Return a boolean type-formatted string
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function boolean($values){
		return 'short';
	}

	/**
	 * Return a int type-formatted string
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function int($values){
		return 'integer';
	}

	/**
	 * Return a date type-formatted string
	 * 
	 * @params array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function date($values){
		return 'string';
	}

	/**
	 * Return the string used to create this particular field in the database
	 *
	 * @see  self::createTable()
	 * @param  array $values
	 * @return string 
	 */
	public function linkset($values){

		//@todo if the relationClass is not yet created in the DB nothing will happen
		//so this creates a bit of an issue with having to run the /dev/build twice

		//Can assume that relationClass value exists @see OrientLinkSet::requireField()
		return 'linkset ' . $values['relationClass'];
	}

	/**
	 * Execute a complex manipulation on the database.
	 * A manipulation is an array of insert / or update sequences.  The keys of the array are table names,
	 * and the values are map containing 'command' and 'fields'.  Command should be 'insert' or 'update',
	 * and fields should be a map of field names to field values, including quotes.  The field value can
	 * also be a SQL function or similar.
	 * @param array $manipulation
	 */
	public function manipulate($manipulation) {

		//@todo Use manipulation ID field if it exists to set from to speed things up

		if ($manipulation) foreach($manipulation as $table => $writeInfo) {

			//Set the fields
			//ID is not auto incremented, it is a copy of RID so make sure it is included
			$writeInfo['fields']['ID'] = isset($writeInfo['id']) ? "'" . $writeInfo['id'] . "'" : null;

			if(isset($writeInfo['fields']) && $writeInfo['fields']) {

				$fieldList = $columnList = $valueList = array();
				foreach($writeInfo['fields'] as $fieldName => $fieldVal) {
					$fieldList[] = "$fieldName = $fieldVal";
					$columnList[] = "$fieldName";

					// Empty strings inserted as null in INSERTs.  Replacement of SS_Database::replace_with_null().
					if($fieldVal === "''") $valueList[] = "null";
					else $valueList[] = $fieldVal;
				}

				//UPDATE or INSERT
				switch($writeInfo['command']) {
					case "update":

						//Select from RID is faster than select from TableName and we don't nee the where
						$rid = '#' . $writeInfo['id'];
						$table = $rid;

						// Test to see if this update query shouldn't, in fact, be an insert
						//Note: If this fails will fall onto "insert" below and ID will not match RID... bit of a problem
						if ($this->query("SELECT * FROM $table")->value()) {

							$fieldList = implode(", ", $fieldList);
							$sql = "UPDATE $table SET $fieldList";
							$this->query($sql);
							break;
						}
						
						// ...if not, we'll skip on to the insert code

					case "insert":
						if(!isset($writeInfo['fields']['ID']) && isset($writeInfo['id'])) {
							$columnList[] = "\"ID\"";
							$valueList[] = "'" . $writeInfo['id'] . "'"; //ID is string x:y in OrientDB
						}
						
						$columnList = implode(", ", $columnList);
						$valueList = implode(", ", $valueList);
						$sql = "INSERT INTO $table ($columnList) VALUES ($valueList)";
						$this->query($sql);
						break;

					//Update container field on a class
					case "update_container":

						$rid = '#' . $writeInfo['id'];
						$containerCommand = $writeInfo['container_command'];
						$containerValues = $writeInfo['container_values'];
						$sql = "UPDATE $rid $containerCommand = $containerValues";
						$this->query($sql);
						break;

					default:
						$sql = null;
						user_error("SS_Database::manipulate() Can't recognise command '$writeInfo[command]'",
							E_USER_ERROR);
				}
			}
		}
	}

	/**
	 * Wrap a string into DB-specific quotes. MySQL, PostgreSQL and SQLite3 only need single quotes around the string.
	 *
	 * @todo this should perhaps be overridden, also see self::addslashes()
	 *
	 * @param string $string String to be prepared for database query
	 * @return string Prepared string
	 */
	public function prepStringForDB($string) {
		return "'" . Convert::raw2sql($string) . "'";
	}

	public function clearTable($table) {

		//Don't want to clear tables like OUser etc.
		if (ClassInfo::exists($table)) {
			try {
				DB::query("TRUNCATE class $table");
			}
			catch (Exception $e) {
				//@todo handle this
			}
		}
	}

}

