<?php

class TestObject extends DataObject {

	private static $db = array(
		'Title' => 'Varchar(255)',
		'Description' => 'Text',
		'Code' => 'Varchar(255)',
		'Locked' => 'Boolean',
		'Sort' => 'Int'
	);	
}