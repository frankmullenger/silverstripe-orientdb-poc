<?php

/**
 * Subclassing SearchFilter to override some methods that make assumptions about SQL format
 */
abstract class OrientSearchFilter extends SearchFilter {

	/**
	 * Normalizes the field name to table mapping.
	 * 
	 * @return string
	 */
	public function getDbName() {
		// Special handler for "NULL" relations
		if($this->name == "NULL") {
			return $this->name;
		}
		
		// This code finds the table where the field named $this->name lives
		// Todo: move to somewhere more appropriate, such as DataMapper, the
		// magical class-to-be?
		$candidateClass = $this->model;

		while($candidateClass != 'DataObject') {
			if(DataObject::has_own_table($candidateClass) 
					&& singleton($candidateClass)->hasOwnTableDatabaseField($this->name)) {
				break;
			}

			$candidateClass = get_parent_class($candidateClass);
		}

		if($candidateClass == 'DataObject') {
			// fallback to the provided name in the event of a joined column
			// name (as the candidate class doesn't check joined records)
			$parts = explode('.', $this->fullName);
			return '"' . implode('"."', $parts) . '"';
		}
		
		//Cannot use ClassName.PropertyName in OrientDB queries
		return "$this->name";
	}
}