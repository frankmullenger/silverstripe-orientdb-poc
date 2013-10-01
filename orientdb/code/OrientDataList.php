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

		//This needs to exist in this class probably because we are using Object::use_custom_class()

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
		//As Hamish said: "ick".

		if($filter) {
			$className = "Orient{$filter}Filter";
		} else {
			$className = 'OrientExactMatchFilter';
		}

		if(!class_exists($className)) {
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
		$baseClass = ClassInfo::baseDataClass($this->dataClass);
		return $this->where("$baseClass.ID IN [" . implode(',', $ids) ."]");
	}

	/**
	 * Return the first DataObject with the given ID
	 * 
	 * @param int $id
	 * @return DataObject
	 */
	public function byID($id) {
		$baseClass = ClassInfo::baseDataClass($this->dataClass);
		return $this->where("$baseClass.ID = " . (int)$id)->First();
	}
}