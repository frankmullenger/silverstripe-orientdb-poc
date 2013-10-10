<?php

class TestAdmin extends ModelAdmin {

	private static $url_segment = 'test';

	private static $url_priority = 50;

	private static $menu_title = 'Test';

	private static $menu_priority = 100;

	public $showImportForm = false;

	private static $managed_models = array(
		'TestObject'
	);

}