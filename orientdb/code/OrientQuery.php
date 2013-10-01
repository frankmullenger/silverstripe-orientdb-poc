<?php

/**
 * A result-set from a SQLite3 database.
 * @package SQLite3Database
 */
//class OrientDBQuery extends OrientDBData {
class OrientQuery extends SS_Query {

	/**
	 * The OrientDatabase object that created this result set.
	 */
	protected $database;

	/**
	 * The result set array
	 */
	protected $handle;

	/**
	 * Hook the result-set given into a Query class, suitable for use by framework.
	 * 
	 * @param database The database object that created this query.
	 * @param handle A result set array
	 */
	public function __construct(OrientDatabase $database, $handle) {
		$this->database = $database;

		//Create array of arrays rather than array of objects
		$handleData = array();
		if (is_array($handle)) foreach ($handle as $key => $val) {

			//Loop the OrientDBData object and build array of data for record
			$temp = array();
			foreach ($val->data as $fieldName => $fieldValue) {
				$temp[$fieldName] = $fieldValue;
			}
			$handleData[$key] = $temp;
		}
		$this->handle = $handleData;
	}

	public function __destruct() {
		if($this->handle) $this->handle = array();
	}

	public function seek($row) {
		reset($this->handle);
		$i = 0;
		foreach ($this->handle as $key => $val) {
			$i++;
		}
		return true;
	}

	public function numRecords() {
		return count($this->handle);
	}

	public function nextRecord() {

		//Return current element and increment pointer
		if ($data = current($this->handle)) {
			next($this->handle);
		}
		else {
			$data = false;
		}
		return $data;
	}
}

