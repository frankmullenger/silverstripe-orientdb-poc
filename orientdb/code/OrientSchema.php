<?php
class OrientSchema extends DataObject {

	private static $db = array(
		'Class' => 'Varchar(255)',
		'Properties' => 'Text'
	);
}