<?php

class Article extends DataObject {

	private static $db = array(
		'Title' => 'Varchar(255)'
	);

	private static $many_many = array(
		'Tags' => 'Tag'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Tags', GridField::create(
			'Tags',
			'Tags',
			$this->Tags(),
			GridFieldConfig_RecordEditor::create()
				->removeComponentsByType('GridFieldEditButton')
				->removeComponentsByType('GridFieldDeleteAction')
				->removeComponentsByType('GridFieldAddNewButton')
		));

		return $fields;
	}
}