<?php

/**
 * A DataList that represents a relation.
 *
 * Adds the notion of a foreign ID that can be optionally set.
 * 
 * @package framework
 * @subpackage model
 */
abstract class OrientRelationList extends OrientDataList {

	public function getForeignID() {
		return $this->dataQuery->getQueryParam('Foreign.ID');
	}

	/**
	 * Returns a copy of this list with the ManyMany relationship linked to 
	 * the given foreign ID.
	 *
	 * @param int|array $id An ID or an array of IDs.
	 */
	public function forForeignID($id) {

		// Turn a 1-element array into a simple value
		if(is_array($id) && sizeof($id) == 1) $id = reset($id);

		// Calculate the new filter
		$filter = $this->foreignIDFilter($id);

		$list = $this->alterDataQuery(function($query, $list) use ($id, $filter){
			// Check if there is an existing filter, remove if there is
			$currentFilter = $query->getQueryParam('Foreign.Filter');
			if($currentFilter) {
				try {
					$query->removeFilterOn($currentFilter);
				}
				catch (Exception $e) { /* NOP */ }
			}

			// Add the new filter
			$query->setQueryParam('Foreign.ID', $id);
			$query->setQueryParam('Foreign.Filter', $filter);
			$query->where($filter);
		});

		return $list;
	}

	/**
	 * Helper method to return the parent class for this relationship.
	 * 
	 * @return string Parent class of the relationship
	 */
	public function getParentClass() {
		return $this->dataQuery->getQueryParam('Parent.Class');
	}

	/**
	 * Set the parent class for relations that are managed in LinkSets basically
	 * 
	 * @param  string $parentClass The parent class that owns the LinkSet
	 * @return OrientRelationList  This list
	 */
	public function forParentClass($parentClass) {

		$list = $this->alterDataQuery(function($query, $list) use ($parentClass){

			// Check if there is an existing filter, remove if there is
			$currentFilter = $query->getQueryParam('Parent.Class');
			if ($currentFilter) {
				try {
					$query->removeFilterOn($currentFilter);
				}
				catch (Exception $e) { /* NOP */ }
			}

			// Add the new filter
			$query->setQueryParam('Parent.Class', $parentClass);
		});

		return $list;
	}

	/**
	 * Helper method to return the name for this relationship/component.
	 * 
	 * @return string Name of the relationship/component.
	 */
	public function getComponentName() {
		return $this->dataQuery->getQueryParam('Component.Name');
	}

	/**
	 * Set the component name for this relation
	 * 
	 * @param  string $parentClass The parent class that owns the LinkSet
	 * @return OrientRelationList  This list
	 */
	public function forComponentName($componentName) {

		$list = $this->alterDataQuery(function($query, $list) use ($componentName){

			// Check if there is an existing filter, remove if there is
			$currentFilter = $query->getQueryParam('Component.Name');
			if ($currentFilter) {
				try {
					$query->removeFilterOn($currentFilter);
				}
				catch (Exception $e) { /* NOP */ }
			}

			// Add the new filter
			$query->setQueryParam('Component.Name', $componentName);
		});

		return $list;
	}

	/**
	 * Returns a where clause that filters the members of this relationship to 
	 * just the related items.
	 *
	 * @param $id (optional) An ID or an array of IDs - if not provided, will use the current ids as per getForeignID
	 */
	abstract protected function foreignIDFilter($id = null);
}