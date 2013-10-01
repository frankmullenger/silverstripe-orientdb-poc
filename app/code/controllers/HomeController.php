<?php

/**
 * @package {@packagename}
 * @subpackage controllers
 */
class HomeController extends AppController {
	
	private static $allowed_actions = array(
		'index'
	);
	
	public function index() {
		return $this->customise(new ArrayData(array(
			'Title' => 'Home'
		)))->renderWith(array(
			'HomeController',
			'AppController'
		));
	}
}