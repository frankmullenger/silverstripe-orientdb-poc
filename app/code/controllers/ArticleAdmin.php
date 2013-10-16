<?php

class ArticleAdmin extends ModelAdmin {

	private static $url_segment = 'articles';

	private static $url_priority = 50;

	private static $menu_title = 'Articles';

	private static $menu_priority = 60;

	public $showImportForm = false;

	private static $managed_models = array(
		'Article',
		'Tag'
	);

}