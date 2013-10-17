# Pipelines

## Getting a DataObject

### DataObject::get()

```php
	$result = DataList::create(get_called_class());
	$result->setDataModel(DataModel::inst());
	return $result;
```

* Returns DataList

### DataList::create() / DataList::__construct()

```php
	$this->dataClass = $dataClass;
	$this->dataQuery = new DataQuery($this->dataClass);
```

* DataList is then altered with _where()_, _filter()_, _sort()_, _limit()_ etc.
* DataList is immutable, all of the above operations return clone of the object
* DataQuery is mutable, all of the above operations alter the DataQuery using _alterDataQuery()_
* DataQuery is a wrapper for SQLQuery
* DataList implements SS_List SS_Filterable SS_Sortable SS_Limitable

### DataList::toArray()

```php
	$query = $this->dataQuery->query();
	$rows = $query->execute();
	$results = array();
	
	foreach($rows as $row) {
		$results[] = $this->createDataObject($row);
	}
```

* At this point DataQuery::__construct() has called DataQuery::initialiseQuery() which has initialised the SQLQuery and called setFrom() etc.

### DataQuery::query()

### DataQuery::getFinalisedQuery()

* Returns SQLQuery
* Builds the query with quite a lot of logic and using functions on SQLQuery like _addFrom

### DataQuery::execute()

### SQLQuery::execute()

```php
	return DB::query($this->sql(), E_USER_ERROR);
```

### SQLQuery::sql()

```php
	$sql = DB::getConn()->sqlQueryToString($this);
```

### SS_Database::sqlQueryToString

* It is here that SQLite3Database or MySQLDatabase can override _sqlQueryToString()_ 


### DB:query()

```php
	return self::getConn()->query($sql, $errorLevel);
```

### DB::getConn()

* Returns SS_Database, which could be SQLite3Database or MySQLDatabase etc.

### SQLite3Database::query() or MySQLDatabase::query()

```php
	$handle = $this->dbConn->query($sql);
```

* dbConn is the connection to the DBMS created in the __constructor() e.g:

```php
	$this->dbConn = new MySQLi($parameters['server'], $parameters['username'], $parameters['password']);
	$this->dbConn = new SQLite3($file, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE, $parameters['key']);
```

__MySQLi::query__

http://www.php.net/manual/en/mysqli.query.php

* Returns mysqli_result object: http://www.php.net/manual/en/class.mysqli-result.php

__SQLite3::query()__

http://www.php.net/manual/en/sqlite3.query.php

* Returns SQLite3Result object: http://www.php.net/manual/en/class.sqlite3result.php

Returns SS_Query object:

```php
return new MySQLQuery($this, $handle);
return new SQLite3Query($this, $handle);
```

The SS_Query object implements Iterator, in both cases the query results are returned in...

### SS_Query::nextRecord()

```php
	//SQLite3Query::nextRecord();
	$data = $this->handle->fetchArray(SQLITE3_ASSOC)

	//MySQLQuery::nextRecord();
	$data = $this->handle->fetch_assoc()
```

## Getting a component

_TODO_

## Building the database

_TODO_
