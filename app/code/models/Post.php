<?php

class Post extends DataObject {

	private static $db = array(
		'Title' => 'Varchar(255)'
	);

	private static $has_one = array(
		'Author' => 'Person'
	);

	private static $summary_fields = array(
		'Title',
		'Author.Name'
	);
}