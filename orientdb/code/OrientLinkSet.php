<?php

class OrientLinkSet extends DBField {

	public function __construct($name = null) {
		parent::__construct($name);
	}

	/**
	 * Compose the requirements for this field
	 *
	 * Note: Issue with this approach is if the same name is used in different relations,
	 * believe this is a limitation with SilverStripe anyway.
	 * many_many = array('Name' => 'SomeClass')
	 * belongs_many_many = array('Name' => 'SomeOtherClass')
	 * 
	 * @return void
	 */
	public function requireField() {

		//Get the type of object for this relation and pass to OrientDatabase::linkset() effectively
		//@todo @has_many same treatment for has_many
		//@todo @belongs_many_many same treatment for belongs_many_many

		$relationClass = null;

		//Does the tableName always match the class name? 
		$manyMany = Config::inst()->get($this->tableName, 'many_many', Config::UNINHERITED);
		if (isset($manyMany[$this->name])) {
			$relationClass = $manyMany[$this->name];
		}

		$belongsManyMany = Config::inst()->get($this->tableName, 'belongs_many_many', Config::UNINHERITED);
		if (isset($belongsManyMany[$this->name])) {
			$relationClass = $belongsManyMany[$this->name];
		}

		//If the relation class cannot be found do not create the linkset at all
		if (!$relationClass) {
			return;
		}

		$parts = array(
			'datatype' => 'linkset',
			'precision' => null,
			'null' => null,
			'default' => null,
			'arrayValue' => null,
			'relationClass' => $relationClass
		);
		
		$values = array(
			'type' => 'linkset', 
			'parts' => $parts
		);

		DB::requireField($this->tableName, $this->name, $values);
	}
}