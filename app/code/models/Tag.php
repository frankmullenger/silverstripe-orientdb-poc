<?php

class Tag extends DataObject {

	private static $db = array(
		'Name' => 'Varchar(255)'
	);

	private static $belongs_many_many = array(
		'Articles' => 'Article'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Articles', GridField::create(
			'Articles',
			'Articles',
			$this->Articles(),
			GridFieldConfig_RecordEditor::create()
				->removeComponentsByType('GridFieldEditButton')
				->removeComponentsByType('GridFieldDeleteAction')
				->removeComponentsByType('GridFieldAddNewButton')
		));

		return $fields;
	}
}