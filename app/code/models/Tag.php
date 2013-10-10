<?php

class Tag extends DataObject {

	private static $db = array(
		'Name' => 'Varchar(255)'
	);

	private static $belongs_many_many = array(
		'Articles' => 'Article'
	);
}