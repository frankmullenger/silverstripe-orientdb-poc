<?php

class Person extends DataObject {

	private static $db = array(
		'Name' => 'Varchar(255)'
	);

	private static $has_many = array(
		'Posts' => 'Post'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Posts', GridField::create(
			'Posts',
			'Posts',
			$this->Posts(),
			GridFieldConfig_RecordEditor::create()
				->removeComponentsByType('GridFieldEditButton')
				->removeComponentsByType('GridFieldDeleteAction')
				->removeComponentsByType('GridFieldAddNewButton')
		));

		return $fields;
	}
}