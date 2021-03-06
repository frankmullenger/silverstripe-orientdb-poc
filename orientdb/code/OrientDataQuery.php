<?php

class OrientDataQuery extends DataQuery {

	/**
	 * Set up the simplest initial query
	 */
	public function initialiseQuery() {

		//@todo could probably use ClassInfo::hasTable() instead here

		// Get the tables to join to.
		// Don't get any subclass tables - let lazy loading do that.
		$tableClasses = ClassInfo::ancestry($this->dataClass, true);
		
		// Error checking
		if(!$tableClasses) {
			if(!SS_ClassLoader::instance()->hasManifest()) {
				user_error("DataObjects have been requested before the manifest is loaded. Please ensure you are not"
					. " querying the database in _config.php.", E_USER_ERROR);
			} else {
				user_error("DataList::create Can't find data classes (classes linked to tables) for"
					. " $this->dataClass. Please ensure you run dev/build after creating a new DataObject.",
					E_USER_ERROR);
			}
		}

		//Base table is not an ancestor in OrientDB
		$baseClass = array_pop($tableClasses);

		// Build our intial query
		$this->query = new OrientSQLQuery(array());
		$this->query->setDistinct(false);
		
		if($sort = singleton($this->dataClass)->stat('default_sort')) {
			$this->sort($sort);
		}

		//TODO: sometimes we want to set from to be the @RID
		$this->query->setFrom("$baseClass");

		$obj = Injector::inst()->get($baseClass);
		$obj->extend('augmentDataQueryCreation', $this->query, $this);
	}

	/**
	 * Ensure that the query is ready to execute.
	 *
	 * @return SQLQuery
	 */
	public function getFinalisedQuery($queriedColumns = null) {

		//@todo need to set queried columns to * rather than specify each individual column
		
		if(!$queriedColumns) $queriedColumns = $this->queriedColumns;
		if($queriedColumns) {
			$queriedColumns = array_merge($queriedColumns, array('Created', 'LastEdited', 'ClassName'));
		}

		$query = clone $this->query;

		// Generate the list of tables to iterate over and the list of columns required by any existing where clauses.
		// This second step is skipped if we're fetching the whole dataobject as any required columns will get selected
		// regardless.
		if($queriedColumns) {
			$tableClasses = ClassInfo::dataClassesFor($this->dataClass);

			// foreach ($query->getWhere() as $where) {
			// 	// Check for just the column, in the form '"Column" = ?' and the form '"Table"."Column"' = ?
			// 	if (preg_match('/^"([^"]+)"/', $where, $matches) ||
			// 		preg_match('/^"([^"]+)"\."[^"]+"/', $where, $matches)) {
			// 		if (!in_array($matches[1], $queriedColumns)) $queriedColumns[] = $matches[1];
			// 	}
			// }
		}
		else {
			$tableClasses = ClassInfo::ancestry($this->dataClass, true);
		}

		//Base class is not an ancestor in OrientDB
		$tableNames = array_keys($tableClasses);
		$baseClass = array_pop($tableNames);

		// Resolve colliding fields
		// if($this->collidingFields) {
		// 	foreach($this->collidingFields as $k => $collisions) {
		// 		$caseClauses = array();
		// 		foreach($collisions as $collision) {
		// 			if(preg_match('/^"([^"]+)"/', $collision, $matches)) {
		// 				$collisionBase = $matches[1];
		// 				$collisionClasses = ClassInfo::subclassesFor($collisionBase);
		// 				$collisionClasses = array_map(array(DB::getConn(), 'prepStringForDB'), $collisionClasses);
		// 				$caseClauses[] = "WHEN $baseClass.ClassName IN ["
		// 					. implode(", ", $collisionClasses) . "] THEN $collision";
		// 			} else {
		// 				user_error("Bad collision item '$collision'", E_USER_WARNING);
		// 			}
		// 		}
		// 		$query->selectField("CASE " . implode( " ", $caseClauses) . " ELSE NULL END", $k);
		// 	}
		// }

		//Filter results for only a certain class
		if($this->filterByClassName) {

			// If querying the base class, don't bother filtering on class name
			if($this->dataClass != $baseClass) {
				// Get the ClassName values to filter to
				$classNames = ClassInfo::subclassesFor($this->dataClass);
				if(!$classNames) user_error("DataList::create() Can't find data sub-classes for '$callerClass'");
				$classNames = array_map(array(DB::getConn(), 'prepStringForDB'), $classNames);
				$query->addWhere("$baseClass.ClassName IN [" . implode(",", $classNames) . "]");
			}
		}

		// $query->selectField("$baseClass.ID", "ID");
		// $query->selectField("CASE WHEN $baseClass.ClassName IS NOT NULL THEN $baseClass.ClassName"
		// 	. " ELSE " . DB::getConn()->prepStringForDB($baseClass) . " END", "RecordClassName");
		
		//@todo remove hack to force getting all properties all the time
		$query->setSelect("*");

		$obj = Injector::inst()->get($this->dataClass);
		$obj->extend('augmentSQL', $query, $this);

		$this->ensureSelectContainsOrderbyColumns($query);

		return $query;
	}

	/**
	 * Update the SELECT clause of the query with the columns from the given table
	 */
	protected function selectColumnsFromTable(SQLQuery &$query, $tableClass, $columns = null) {

		// Add SQL for multi-value fields
		$databaseFields = DataObject::database_fields($tableClass);
		$compositeFields = DataObject::composite_fields($tableClass, false);

		if($databaseFields) foreach($databaseFields as $k => $v) {
			if((is_null($columns) || in_array($k, $columns)) && !isset($compositeFields[$k])) {
				// Update $collidingFields if necessary
				if($expressionForField = $query->expressionForField($k)) {
					if(!isset($this->collidingFields[$k])) $this->collidingFields[$k] = array($expressionForField);
					$this->collidingFields[$k][] = "$tableClass.$k";
				
				} else {
					$query->selectField("$tableClass.$k", $k);
				}
			}
		}
		if($compositeFields) foreach($compositeFields as $k => $v) {
			if((is_null($columns) || in_array($k, $columns)) && $v) {
				$dbO = Object::create_from_string($v, $k);
				$dbO->addToQuery($query);
			}
		}
	}

	/**
	 * Return the number of records in this query.
	 * Note that this will issue a separate SELECT COUNT() query.
	 */
	public function count() {
		$baseClass = ClassInfo::baseDataClass($this->dataClass);
		return $this->getFinalisedQuery()->count('*');
	}

	public function traverse($containerName) {

		$this->query->setTraverse($containerName);
		return $this;
	}

	public function from($from) {

		$this->query->setFrom($from);
		return $this;
	}
}



