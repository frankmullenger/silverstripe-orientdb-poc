<?php

class PostAdmin extends ModelAdmin {

	private static $url_segment = 'posts';

	private static $url_priority = 50;

	private static $menu_title = 'Posts';

	private static $menu_priority = 50;

	public $showImportForm = false;

	private static $managed_models = array(
		'Post',
		'Person'
	);

}