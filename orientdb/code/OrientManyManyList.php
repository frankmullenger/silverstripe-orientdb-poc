<?php

/**
 * Subclass of {@link DataList} representing a many_many relation.
 *
 * @package framework
 * @subpackage model
 */
class OrientManyManyList extends OrientRelationList {
	
	protected $joinTable;
	
	protected $localKey;
	
	protected $foreignKey;

	protected $extraFields;

	/**
	 * Create a new ManyManyList object.
	 * 
	 * A ManyManyList object represents a list of DataObject records that correspond to a many-many
	 * relationship.  In addition to, 
	 * 
	 * Generation of the appropriate record set is left up to the caller, using the normal
	 * {@link DataList} methods.  Addition arguments are used to support {@@link add()}
	 * and {@link remove()} methods.
	 * 
	 * @param string $dataClass The class of the DataObjects that this will list.
	 * @param string $joinTable The name of the table whose entries define the content of this many_many relation.
	 * @param string $localKey The key in the join table that maps to the dataClass' PK.
	 * @param string $foreignKey The key in the join table that maps to joined class' PK.
	 * @param string $extraFields A map of field => fieldtype of extra fields on the join table.
	 * 
	 * @example new ManyManyList('Group','Group_Members', 'GroupID', 'MemberID');
	 */
	public function __construct($dataClass, $joinTable, $localKey, $foreignKey, $extraFields = array()) {

		parent::__construct($dataClass);

		// No join table required
		$this->joinTable = null; 
		$this->localKey = null;
		$this->foreignKey = null;
		$this->extraFields = null;

		// $baseClass = ClassInfo::baseDataClass($dataClass);
	}

	/**
	 * Return a filter expression for when getting the contents of the relationship for some foreign ID
	 * @return string
	 */
	protected function foreignIDFilter($id = null) {
		return;
	}

	/**
	 * Return a filter expression for the join table when writing to the join table
	 *
	 * When writing (add, remove, removeByID), we need to filter the join table to just the relevant
	 * entries. However some subclasses of ManyManyList (Member_GroupSet) modify foreignIDFilter to
	 * include additional calculated entries, so we need different filters when reading and when writing
	 *
	 * @return string
	 */
	protected function foreignIDWriteFilter($id = null) {
		return;
	}

	/**
	 * Add an item to this many_many relationship
	 * Does so by adding an entry to the joinTable.
	 * @param $extraFields A map of additional columns to insert into the joinTable
	 */
	public function add($item, $extraFields = null) {

		//Note: extra fields are not supported

		SS_Log::log(new Exception(print_r('%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%', true)), SS_Log::NOTICE);
		SS_Log::log(new Exception(print_r($item->toMap(), true)), SS_Log::NOTICE);
		SS_Log::log(new Exception(print_r($this->dataClass, true)), SS_Log::NOTICE);

		if (is_numeric($item)) {
			$itemID = $item;
		} 
		else if ($item instanceof $this->dataClass) {
			$itemID = $item->ID;
		} 
		else {
			throw new InvalidArgumentException("ManyManyList::add() expecting a $this->dataClass object, or ID value", E_USER_ERROR);
		}

		// Validate foreignID, ID of the record that owns the LinkSet
		$foreignID = $this->getForeignID();
		if(!$foreignID) {
			throw new Exception("ManyManyList::add() can't be called until a foreign ID is set", E_USER_WARNING);
		}
		SS_Log::log(new Exception(print_r($foreignID, true)), SS_Log::NOTICE);
		


		if ($itemID && $foreignID) {
			$manipulation = array();

			$tableName = $this->getParentClass();
			SS_Log::log(new Exception(print_r($tableName, true)), SS_Log::NOTICE);

			$componentName = $this->getComponentName();
			SS_Log::log(new Exception(print_r($componentName, true)), SS_Log::NOTICE);

			//Unfortunately need to use "set" command the first time
			$hasExisting = false;
			$parentItem = $tableName::get()
				->filter(array('ID' => $foreignID))
				->first();

			$existingComponent = $parent->Tags()->exists();

			// SS_Log::log(new Exception(print_r($parentItem, true)), SS_Log::NOTICE);

			// SS_Log::log(new Exception(print_r($this->exists(), true)), SS_Log::NOTICE);

			//TODO need to use "set" command the first time

			//We are updating a record's LinkSet
			$manipulation[$tableName]['command'] = 'update_container';

			//Use "set" command the first time we add entries to this list
			$command = ($hasExisting) ? 'add' : 'set';
			$manipulation[$tableName]['container_command'] = "$command $componentName";
			$manipulation[$tableName]['id'] = $foreignID;
			$manipulation[$tableName]['container_values'] = '[#' . $itemID . ']';


			DB::manipulate($manipulation);
		}

		return;


		//@todo need to inspect the linklist here instead
		// if($foreignFilter) {
		// 	$query = new SQLQuery("*", array("$this->joinTable"));
		// 	$query->setWhere($foreignFilter);
		// 	$hasExisting = ($query->count() > 0);
		// } else {
		// 	$hasExisting = false;	
		// }
		$hasExisting = false;

		//Ignore the foreign filter, get the current class and update the linklist on it with a JSON array
		$foreignData = json_encode((array)$foreignIDs);

		SS_Log::log(new Exception(print_r($foreignData, true)), SS_Log::NOTICE);

		// Insert or update
		foreach((array)$foreignIDs as $foreignID) {
			$manipulation = array();
			if($hasExisting) {
				$manipulation[$this->joinTable]['command'] = 'update';	
				$manipulation[$this->joinTable]['where'] = "{$this->joinTable}.{$this->foreignKey} = " . 
					"'" . Convert::raw2sql($foreignID) . "'" .
					" AND {$this->localKey} = {$itemID}";
			} else {
				$manipulation[$this->joinTable]['command'] = 'insert';	
			}

			//Extra fields are not supported
			// if($extraFields) foreach($extraFields as $k => $v) {
			// 	if(is_null($v)) $manipulation[$this->joinTable]['fields'][$k] = 'NULL';
			// 	else $manipulation[$this->joinTable]['fields'][$k] =  "'" . Convert::raw2sql($v) . "'";
			// }

			$manipulation[$this->joinTable]['fields'][$this->localKey] = $itemID;
			$manipulation[$this->joinTable]['fields'][$this->foreignKey] = $foreignID;

			DB::manipulate($manipulation);
		}
	}

	/**
	 * Remove the given item from this list.
	 * Note that for a ManyManyList, the item is never actually deleted, only the join table is affected
	 * @param $itemID The ID of the item to remove.
	 */
	public function remove($item) {
		if(!($item instanceof $this->dataClass)) {
			throw new InvalidArgumentException("ManyManyList::remove() expecting a $this->dataClass object");
		}
		
		return $this->removeByID($item->ID);
	}

	/**
	 * Remove the given item from this list.
	 * Note that for a ManyManyList, the item is never actually deleted, only the join table is affected
	 * @param $itemID The item it
	 */
	public function removeByID($itemID) {
		if(!is_numeric($itemID)) throw new InvalidArgumentException("ManyManyList::removeById() expecting an ID");

		$query = new SQLQuery("*", array("$this->joinTable"));
		$query->setDelete(true);

		if($filter = $this->foreignIDWriteFilter($this->getForeignID())) {
			$query->setWhere($filter);
		} else {
			user_error("Can't call ManyManyList::remove() until a foreign ID is set", E_USER_WARNING);
		}
		
		$query->addWhere("$this->localKey = {$itemID}");
		$query->execute();
	}

	/**
	 * Remove all items from this many-many join.  To remove a subset of items, filter it first.
	 */
	public function removeAll() {
		$base = ClassInfo::baseDataClass($this->dataClass());

		// Remove the join to the join table to avoid MySQL row locking issues.
		$query = $this->dataQuery();
		$query->removeFilterOn($query->getQueryParam('Foreign.Filter'));

		$query = $query->query();
		$query->setSelect("{$base}.ID");

		$from = $query->getFrom();
		unset($from[$this->joinTable]);
		$query->setFrom($from);
		$query->setDistinct(false);
		$query->setOrderBy(null, null); // ensure any default sorting is removed, ORDER BY can break DELETE clauses

		// Use a sub-query as SQLite does not support setting delete targets in
		// joined queries.
		$delete = new SQLQuery();
		$delete->setDelete(true);
		$delete->setFrom("$this->joinTable");
		$delete->addWhere($this->foreignIDFilter());
		$delete->addWhere("{$this->joinTable}.{$this->localKey} IN ({$query->sql()})");
		$delete->execute();
	}

	/**
	 * No support for extra fields
	 *	
	 * @param string $componentName The name of the component
	 * @param int $itemID The ID of the child for the relationship
	 * @return array Map of fieldName => fieldValue
	 */
	public function getExtraData($componentName, $itemID) {
		$result = array();
		return $result;
	}

	/**
	 * Gets the join table used for the relationship.
	 *
	 * @return string the name of the table
	 */
	public function getJoinTable() {
		//No join table necessary
		return null;
	}

	/**
	 * Gets the key used to store the ID of the local/parent object.
	 *
	 * @return string the field name
	 */
	public function getLocalKey() {
		return $this->localKey;
	}

	/**
	 * Gets the key used to store the ID of the foreign/child object.
	 *
	 * @return string the field name
	 */
	public function getForeignKey() {
		return $this->foreignKey;
	}

	/**
	 * Gets the extra fields included in the relationship.
	 *
	 * @return array a map of field names to types
	 */
	public function getExtraFields() {
		return $this->extraFields;
	}

}
