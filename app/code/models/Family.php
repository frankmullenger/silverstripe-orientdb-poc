<?php

class Family_GreatGrandParent extends DataObject {

	private static $db = array(
		'Name' => 'Varchar(255)',
		'Title' => 'Varchar(255)',
		'GreatGrandParent' => 'Boolean',
		'NewFieldOne' => 'Boolean'
	);

	private static $defaults = array(
		'GreatGrandParent' => 1
	);
}

class Family_GrandParent extends Family_GreatGrandParent {

	private static $db = array(
		'GrandParent' => 'Boolean'
	);

	private static $defaults = array(
		'GrandParent' => 1
	);
}

class Family_Parent extends Family_GrandParent {

	private static $db = array(
		'Parent' => 'Boolean'
	);

	private static $defaults = array(
		'Parent' => 1
	);
}

class Family_Child extends Family_Parent {

	private static $db = array(
		'Child' => 'Boolean'
	);

	private static $defaults = array(
		'Child' => 1
	);
}

class Family_Sibling extends Family_Parent {

	private static $db = array(
		'Sibling' => 'Boolean'
	);

	private static $defaults = array(
		'Sibling' => 1
	);
}