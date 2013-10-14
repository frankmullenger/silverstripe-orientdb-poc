<?php

class OrientSQLQuery extends SQLQuery {

	protected $traverse = array();

	/**
	 * Return the number of rows in this query if the limit were removed.  Useful in paged data sets.
	 * 
	 * @todo Remove the AS "0" part of the query
	 * 
	 * @param String $column Quoted, escaped column name
	 * @return int
	 */
	public function count($column = null) {

		// Choose a default column
		if($column == null) {
			$column = '*';
		}

		$clone = clone $this;
		$clone->select = array("count($column)");
		$clone->limit = null;
		$clone->orderby = null;
		$clone->groupby = null;

		$count = $clone->execute()->value();
		return $count;
	}

	/**
	 * Set the list of columns to be selected by the query.
	 *
	 * <code>
	 *  // pass fields to select as single parameter array
	 *  $query->setSelect(array("Col1","Col2"))->setFrom("MyTable");
	 *
	 *  // pass fields to select as multiple parameters
	 *  $query->setSelect("Col1", "Col2")->setFrom("MyTable");
	 * </code>
	 *
	 * @param string|array $fields
	 * @param boolean $clear Clear existing select fields?
	 * @return SQLQuery
	 */
	public function setSelect($fields) {
		$this->select = array();
		if (func_num_args() > 1) {
			$fields = func_get_args();
		} else if(!is_array($fields)) {
			$fields = array($fields);
		}
		return $this->addSelect($fields);
	}

	/**
	 * Add to the list of columns to be selected by the query.
	 *
	 * <code>
	 *  // pass fields to select as single parameter array
	 *  $query->addSelect(array("Col1","Col2"))->setFrom("MyTable");
	 *
	 *  // pass fields to select as multiple parameters
	 *  $query->addSelect("Col1", "Col2")->setFrom("MyTable");
	 * </code>
	 *
	 * @param string|array $fields
	 * @param boolean $clear Clear existing select fields?
	 * @return SQLQuery
	 */
	public function addSelect($fields) {
		if (func_num_args() > 1) {
			$fields = func_get_args();
		} else if(!is_array($fields)) {
			$fields = array($fields);
		}

		foreach($fields as $idx => $field) {
			if(preg_match('/^(.*) +AS +"?([^"]*)"?/i', $field, $matches)) {
				Deprecation::notice("3.0", "Use selectField() to specify column aliases");
				$this->selectField($matches[1], $matches[2]);
			} else {
				$this->selectField($field, is_numeric($idx) ? null : $idx);
			}
		}
		
		return $this;
	}

	/**
	 * Return an itemised select list as a map, where keys are the aliases, and values are the column sources.
	 * Aliases will always be provided (if the alias is implicit, the alias value will be inferred), and won't be
	 * quoted.
	 *
	 * Column sources will have double quotes stripped out
	 * E.g., 'Title' => '"SiteTree"."Title"'.
	 */
	public function getSelect() {

		//Stripping out double quotes for OrientDB compatible SQL, this is a bit of a hack but the alternative is quite involved 
		//this is perhaps the best approach for PoC
		$select = array();
		foreach ($this->select as $alias => $column) {
			$select[str_replace('"', '', $alias)] = str_replace('"', '', $column);
		}
		return $select;
	}

	/**
	 * Select an additional field.
	 *
	 * @param $field String The field to select (escaped SQL statement)
	 * @param $alias String The alias of that field (escaped SQL statement).
	 * Defaults to the unquoted column name of the $field parameter.
	 * @return SQLQuery
	 */
	public function selectField($field, $alias = null) {
		if(!$alias) {
			if(preg_match('/"([^"]+)"$/', $field, $matches)) $alias = $matches[1];
			else $alias = $field;
		}
		$this->select[$alias] = $field;
		return $this;
	}

	/**
	 * Return a list of FROM clauses used internally.
	 * @return array
	 */
	public function getFrom() {

		//@todo @orientdb strip out double quotes
		return $this->from;
	}

	/**
	 * Return a list of HAVING clauses used internally.
	 * @return array
	 */
	public function getHaving() {

		//@todo @orientdb strip out double quotes
		return $this->having;
	}

	/**
	 * Return a list of GROUP BY clauses used internally.
	 * @return array
	 */
	public function getGroupBy() {

		//@todo @orientdb strip out double quotes
		return $this->groupby;
	}

	/**
	 * Return a list of WHERE clauses used internally.
	 * @return array
	 */
	public function getWhere() {

		//Stripping out double quotes for OrientDB compatible SQL, this is a bit of a hack but the alternative is quite involved 
		//this is perhaps the best approach for PoC
		$where = array();
		foreach ($this->where as $alias => $column) {
			$where[str_replace('"', '', $alias)] = str_replace('"', '', $column);
		}
		return $where;
	}

	/**
	 * Returns the current order by as array if not already. To handle legacy
	 * statements which are stored as strings. Without clauses and directions,
	 * convert the orderby clause to something readable.
	 *
	 * @return array
	 */
	public function getOrderBy() {
		$orderby = $this->orderby;
		if(!$orderby) $orderby = array();

		if(!is_array($orderby)) {
			// spilt by any commas not within brackets
			$orderby = preg_split('/,(?![^()]*+\\))/', $orderby);
		}

		foreach($orderby as $k => $v) {
			if(strpos($v, ' ') !== false) {
				unset($orderby[$k]);

				$rule = explode(' ', trim($v));
				$clause = $rule[0];
				$dir = (isset($rule[1])) ? $rule[1] : 'ASC';

				$orderby[$clause] = $dir;
			}
		}

		//Stripping out double quotes for OrientDB compatible SQL, this is a bit of a hack but the alternative is quite involved 
		//this is perhaps the best approach for PoC
		$orderBy = array();
		foreach ($orderby as $column => $order) {
			$orderBy[str_replace('"', '', $column)] = str_replace('"', '', $order);
		}
		return $orderBy;
	}

	public function traverse() {
		//TODO set up the query to traverse
	}

	/**
	 * Generate the SQL statement for this query.
	 * 
	 * @return string
	 */
	public function sql() {
		// TODO: Don't require this internal-state manipulate-and-preserve - let sqlQueryToString() handle the new
		// syntax
		$origFrom = $this->from;
		// Sort the joins
		$this->from = $this->getOrderedJoins($this->from);
		// Build from clauses
		foreach($this->from as $alias => $join) {
			// $join can be something like this array structure
			// array('type' => 'inner', 'table' => 'SiteTree', 'filter' => array("SiteTree.ID = 1", 
			// "Status = 'approved'", 'order' => 20))
			if(is_array($join)) {
				if(is_string($join['filter'])) $filter = $join['filter'];
				else if(sizeof($join['filter']) == 1) $filter = $join['filter'][0];
				else $filter = "(" . implode(") AND (", $join['filter']) . ")";

				$aliasClause = ($alias != $join['table']) ? " AS \"" . Convert::raw2sql($alias) . "\"" : "";
				$this->from[$alias] = strtoupper($join['type']) . " JOIN \"" 
					. $join['table'] . "\"$aliasClause ON $filter";
			}
		}

		$sql = DB::getConn()->sqlQueryToString($this);
		
		if($this->replacementsOld) {
			$sql = str_replace($this->replacementsOld, $this->replacementsNew, $sql);
		}

		$this->from = $origFrom;

		// The query was most likely just created and then exectued.
		if(trim($sql) === 'SELECT * FROM') {
			return '';
		}
		return $sql;
	}

	/**
	 * Joins are not supported in OrientDB
	 */

	public function addLeftJoin($table, $onPredicate, $tableAlias = '', $order = 20) {

		//@todo @orientdb Disable left joins in OrientDB need to be handled in a different way
		return $this;
	}

	public function leftjoin($table, $onPredicate, $tableAlias = null, $order = 20) {
		Deprecation::notice('3.0', 'Please use addLeftJoin() instead!');
		$this->addLeftJoin($table, $onPredicate, $tableAlias);
	}

	public function addInnerJoin($table, $onPredicate, $tableAlias = null, $order = 20) {
		return $this;
	}

	public function innerjoin($table, $onPredicate, $tableAlias = null, $order = 20) {
		Deprecation::notice('3.0', 'Please use addInnerJoin() instead!');
		return $this->addInnerJoin($table, $onPredicate, $tableAlias, $order);
	}

	public function addFilterToJoin($table, $filter) {
		return $this;
	}

	public function setJoinFilter($table, $filter) {
		return $this;
	}

	public function isJoinedTo($tableAlias) {
		return false;
	}
}