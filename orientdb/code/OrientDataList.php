<?php
class OrientDataList extends DataList {

	/**
	 * Create a new DataList.
	 * No querying is done on construction, but the initial query schema is set up.
	 *
	 * @param string $dataClass - The DataObject class to query.
	 */
	public function __construct($dataClass) {

		parent::__construct($dataClass);

		$this->dataClass = $dataClass;
		$this->dataQuery = new OrientDataQuery($this->dataClass);
	}

	/**
	 * Return a new instance of the list with an added filter
	 */
	public function addFilter($filterArray) {

		//@todo Set the from to be an RID for better performance
		// if (isset($filterArray['ID'])) {
		// 	$rid = '#' . $filterArray['ID'];
		// 	$query = $this->dataQuery()->query();
		// 	$query->setFrom($rid);
		// 	unset($filterArray['ID']);
		// }

		$list = $this;

		foreach($filterArray as $field => $value) {
			$fieldArgs = explode(':', $field);
			$field = array_shift($fieldArgs);
			$filterType = array_shift($fieldArgs);
			$modifiers = $fieldArgs;
			$list = $list->applyFilterContext($field, $filterType, $modifiers, $value);
		}

		return $list;
	}

	/**
	 * Translates a filter type to a SQL query. using OrientDB Filters instead.
	 *
	 * @param string $field - the fieldname in the db
	 * @param string $filter - example StartsWith, relates to a filtercontext
	 * @param array $modifiers - Modifiers to pass to the filter, ie not,nocase
	 * @param string $value - the value that the filtercontext will use for matching
	 * @todo Deprecated SearchContexts and pull their functionality into the core of the ORM
	 */
	private function applyFilterContext($field, $filter, $modifiers, $value) {

		//Unfortunately need to use subclasses for the Filters as there are assumptions in the filters about the 
		//SQL being generated such as using "OR" and double quotes around fieldnames
		//Rather than dependency injection here could we use different namespaces?

		if ($filter) {
			$className = "Orient{$filter}Filter";
		}

		if (!class_exists($className)) {
			$className = "{$filter}Filter";
		}

		if (!class_exists($className)) {
			$className = 'OrientExactMatchFilter';
		}

		if (!class_exists($className)) {
			$className = 'OrientExactMatchFilter';
			array_unshift($modifiers, $filter);
		}

		$t = new $className($field, $value, $modifiers);
		return $this->alterDataQuery(array($t, 'apply'));
	}

	/**
	 * Filter this list to only contain the given Primary IDs
	 *
	 * @param array $ids Array of integers, will be automatically cast/escaped.
	 * @return DataList
	 */
	public function byIDs(array $ids) {
		$ids = array_map('intval', $ids); // sanitize
		return $this->where("ID IN [" . implode(',', $ids) ."]");
	}

	/**
	 * Return the first DataObject with the given ID
	 * 
	 * @param int $id
	 * @return DataObject
	 */
	public function byID($id) {
		return $this->filter(array('ID' => $id))->first();
	}

	public function traverse($containerName) {

		//Should this go into OrientRelationList?
		return $this->alterDataQuery(function($query) use ($containerName){
			$query->traverse($containerName);
		});
	}

	public function from($from) {
		return $this->alterDataQuery(function($query) use ($from){
			$query->from($from);
		});
	}

	public function innerJoin($table, $onClause, $alias = null) {
		user_error("OrientDB does not support inner joins, need to refactor to traversal.", E_USER_ERROR);
	}

	public function leftJoin($table, $onClause, $alias = null) {
		user_error("OrientDB does not support left joins, need to refactor to traversal.", E_USER_ERROR);
	}

	/**
	 * Return an array of the actual items that this DataList contains at this stage.
	 * This is when the query is actually executed.
	 *
	 * Some queries such as traverses return multiple types of records, only certain 
	 * records (those matching the type of the component being traversed) should be
	 * returned.
	 *
	 * @return array
	 */
	public function toArray() {
		$query = $this->dataQuery->query();
		$rows = $query->execute();
		$results = array();
		
		foreach ($rows as $row) {
			if ($obj = $this->createDataObject($row)) {
				$results[] = $obj;
			}
		}
		return $results;
	}

	/**
	 * Create a DataObject from the given SQL row
	 *
	 * @todo need to improve logic for this method
	 * 
	 * @param array $row
	 * @return DataObject
	 */
	protected function createDataObject($row) {
		$defaultClass = $this->dataClass;

		// Failover from RecordClassName to ClassName
		if (empty($row['RecordClassName'])) {
			$row['RecordClassName'] = $row['ClassName'];
		}
		
		// Instantiate the class mentioned in RecordClassName only if it exists, otherwise default to $this->dataClass
		if (class_exists($row['RecordClassName'])) {
			$item = Injector::inst()->create($row['RecordClassName'], $row, false, $this->model);
		} 
		else {
			$item = Injector::inst()->create($defaultClass, $row, false, $this->model);
		}

		//set query params on the DataObject to tell the lazy loading mechanism the context the object creation context
		$item->setSourceQueryParams($this->dataQuery()->getQueryParams());


		//If traversing only return objects of the component type
		$query = $this->dataQuery->query();
		if ($query->getTraverse()) {

			if ($row['RecordClassName'] == $defaultClass) {
				$item = Injector::inst()->create($defaultClass, $row, false, $this->model);
				$item->setSourceQueryParams($this->dataQuery()->getQueryParams());
			}
			else {
				$item = null;
			}
		}

		return $item;
	}
}