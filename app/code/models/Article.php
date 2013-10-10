<?php

class Article extends DataObject {

	private static $db = array(
		'Title' => 'Varchar(255)'
	);

	private static $many_many = array(
		'Tags' => 'Tag'
	);
}