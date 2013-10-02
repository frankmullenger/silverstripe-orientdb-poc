<?php

class Vehicle extends DataObject {

	private static $db = array(
		'Title' => 'Varchar(255)',
		'Type' => 'Varchar(255)'
	);	
}

class Car extends Vehicle {

	private static $db = array(
		'Wheels' => 'Int'
	);
}

class Boat extends Vehicle {

	private static $db = array(
		'LifeRafts' => 'Int'
	);
}