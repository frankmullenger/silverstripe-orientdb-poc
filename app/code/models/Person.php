<?php

class Person extends DataObject {

	private static $db = array(
		'Name' => 'Varchar(255)'
	);

	private static $has_many = array(
		'Posts' => 'Post'
	);
}