<?php

class OrientLinkSet extends DBField {

	public function __construct($name = null) {
		parent::__construct($name);
	}

	public function requireField() {

		//Get the type of object for this relation and pass to OrientDatabase::linkset() effectively
		//@todo @has_many same treatment for has_many
		//@todo @belongs_many_many same treatment for belongs_many_many

		//Does the tableName always match the class name? 
		$manyMany = Config::inst()->get($this->tableName, 'many_many', Config::UNINHERITED);

		//If the relation class cannot be found do not create the linkset at all
		if (!isset($manyMany[$this->name])) {
			return;
		}

		$parts = array(
			'datatype' => 'linkset',
			'precision' => null,
			'null' => null,
			'default' => null,
			'arrayValue' => null,
			'relationClass' => $manyMany[$this->name]
		);
		
		$values = array(
			'type' => 'linkset', 
			'parts' => $parts
		);

		DB::requireField($this->tableName, $this->name, $values);
	}
}