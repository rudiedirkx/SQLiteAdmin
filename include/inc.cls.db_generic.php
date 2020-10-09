<?php #1.6

abstract class DB_Generic {

	protected $dbCon;
	public $error = '';
	public $errno = 0;
	public $num_queries = 0;

	public function __construct( $f_szHost, $f_szUser = null, $f_szPass = null, $f_szDb = null ) {}
	public function saveError() {}
	public function connected() {
		return false;
	}
	abstract public function escape($v);
	abstract public function insert_id();
	abstract public function affected_rows();
	abstract public function query($f_szSqlQuery);
	abstract public function fetch($f_szSqlQuery);
	abstract public function fetch_fields($f_szSqlQuery);
	abstract public function fetch_one($f_szSqlQuery);
	abstract public function count_rows($f_szSqlQuery);
	abstract public function select_by_field($tbl, $field, $where = '');

	public function getTables() {
		return $this->select_by_field('sqlite_master', 'tbl_name', "
			type IN ('table', 'view') AND tbl_name NOT IN ('sqlite_sequence', 'sqlite_master')
			ORDER BY tbl_name ASC
		");
	}

	public function createTable( $tableName, $columns ) {
		$sql = 'CREATE TABLE "' . $tableName . '" (' . "\n";
		$first = true;
		foreach ( $columns AS $columnName => $details ) {
			// the very simple columns: array( 'a', 'b', 'c' )
			if ( is_int($columnName) ) {
				$columnName = $details;
				$details = array();
			}

			$columnSQL = $this->columnSQL($columnName, $details);

			$comma = $first ? ' ' : ',';
			$sql .= '  ' . $comma . $columnSQL . "\n";

			$first = false;
		}
		$sql .= ');';

		return $this->query($sql);
	}

	public function columnSQL( $columnName, $details ) {
		$properties = array();

		// if PK, forget the rest
		if ( !empty($details['pk']) ) {
			$properties[] = 'INTEGER PRIMARY KEY AUTOINCREMENT';
		}
		// check special stuff
		else {
			// type
			$type = isset($details['type']) ? strtoupper($details['type']) : 'TEXT';
			isset($details['unsigned']) && $type = 'INT';
			$properties[] = $type;

			// not null
			if ( isset($details['null']) ) {
				$properties[] = $details['null'] ? 'NULL' : 'NOT NULL';
			}

			// unique
			if ( !empty($details['unique']) ) {
				$properties[] = 'UNIQUE';
			}

			// constraints
			if ( !empty($details['unsigned']) ) {
				$properties[] = 'CHECK ("'.$columnName.'" >= 0)';
			}
			if ( isset($details['min']) ) {
				$properties[] = 'CHECK ("'.$columnName.'" >= ' . (float)$details['min'] . ')';
			}
			if ( isset($details['max']) ) {
				$properties[] = 'CHECK ("'.$columnName.'" <= ' . (float)$details['max'] . ')';
			}

			// default -- ignore NULL
			if ( isset($details['default']) ) {
				$D = $details['default'];
				$properties[] = 'DEFAULT ' . ( is_int($D) || is_float($D) ? $D : $this->escapeAndQuote($D) );
			}

			// foreign key relationship
			if ( isset($details['references']) ) {
				list($tbl, $col) = $details['references'];
				$properties[] = 'REFERENCES ' . $tbl . '(' . $col . ')';
			}

			// Case-insensitive (not the default in SQLite)
			if ( 'TEXT' == $type ) {
				$properties[] = 'COLLATE NOCASE';
			}
		}

		// SQL
		return '"' . $columnName . '" ' . implode(' ', $properties);
	}

	public function stringifyConditions( $conditions, $operator = 'AND' ) {
		if ( !is_array($conditions) ) {
			return $conditions;
		}

		$array = array();
		foreach ($conditions as $column => $value) {
			$value = is_array($value) ? array_map(array($this, 'escapeAndQuote'), $value) : $this->escapeAndQuote($value);
			$array[] = $column . ( is_array($value) ? ' IN (' . implode(', ', $value) . ')' : ' = ' . $value );
		}

		return implode(' ' . $operator . ' ', $array);
	}

	public function escapeAndQuoteStructure( $value ) {
		return '"' . addslashes($value) . '"';
	}

	public function escapeAndQuote($v) {
		if ( $v === true ) {
			return "'1'";
		}
		else if ( $v === false ) {
			return "'0'";
		}
		else if ( $v === null ) {
			return 'NULL';
		}
		return "'" . $this->escape($v) . "'";
	}

	public function select($table, $where = '') {
		$where = $this->stringifyConditions($where);
		return $this->fetch('SELECT * FROM '.$table.( $where ? ' WHERE '.$where : '' ).';');
	}

	public function select_one($table, $field, $where = '') {
		$where = $this->stringifyConditions($where);
		return $this->fetch_one('SELECT ' . $field . ' FROM ' . $table . ( $where ? ' WHERE ' . $where : '' ));
	}

	public function max($tbl, $field, $where = '') {
		return $this->select_one($tbl, 'MAX('.$field.')', $where);
	}

	public function min($tbl, $field, $where = '') {
		return $this->select_one($tbl, 'MIN('.$field.')', $where);
	}

	public function count($tbl, $where = '') {
		return $this->select_one($tbl, 'COUNT(1)', $where);
	}

	public function select_fields($tbl, $fields, $where = '') {
		$where = $this->stringifyConditions($where);
		return $this->fetch_fields('SELECT '.$fields.' FROM '.$tbl.( $where ? ' WHERE '.$where : '' ).';');
	}

	public function replace_into($tbl, $values) {
		foreach ( $values AS $k => $v ) {
			$values[$k] = $this->escapeAndQuote($v);
		}
		return $this->query('REPLACE INTO '.$tbl.' ('.implode(',', array_keys($values)).') VALUES ('.implode(",", $values).');');
	}

	public function insert($tbl, $values) {
		foreach ( $values AS $k => $v ) {
			$values[$k] = $this->escapeAndQuote($v);
		}
		$szSqlQuery = 'INSERT INTO '.$tbl.' ('.implode(', ', array_keys($values)).') VALUES ('.implode(", ", $values).');';
		return $this->query($szSqlQuery);
	}

	public function update($tbl, $update, $where = null) {
		$update = $this->stringifyUpdates($update);
		$where = $this->stringifyConditions($where);

		$query = 'UPDATE '.$tbl.' SET '.$update.( $where ? ' WHERE '.$where : '' ).';';
		return $this->query($query);
	}

	public function delete($tbl, $where) {
		$where = $this->stringifyConditions($where);
		return $this->query('DELETE FROM '.$tbl.' WHERE '.$where.';');
	}

	public function stringifyUpdates( $updates ) {
		if ( !is_array($updates) ) return $updates;

		$u = '';
		foreach ( (array)$updates AS $k => $v ) {
			if ( is_int($k) ) {
				$u .= ', ' . $v;
			}
			else {
				$u .= ', ' . $k . ' = ' . $this->escapeAndQuote($v);
			}
		}
		$updates = substr($u, 1);

		return $updates;
	}

} // END Class db_generic

?>
