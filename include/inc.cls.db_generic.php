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
	public function escape( $v ) {}
	public function insert_id() {}
	public function affected_rows() {}
	public function query( $f_szSqlQuery ) {}
	public function fetch($f_szSqlQuery) {}
	public function fetch_fields($f_szSqlQuery) {}
	public function select_one($tbl, $field, $where = '') {}
	public function count_rows($f_szSqlQuery) {}
	public function select_by_field($tbl, $field, $where = '') {}

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
		return "'".$this->escape($v)."'";
	}

	public function select($f_szTable, $f_szWhere = '') {
		return $this->fetch('SELECT * FROM '.$f_szTable.( $f_szWhere ? ' WHERE '.$f_szWhere : '' ).';');
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

	public function update($tbl, $update, $where = '') {
		if ( !is_string($update) ) {
			$u = '';
			foreach ( (array)$update AS $k => $v ) {
				$u .= ',' . $k . '=' . $this->escapeAndQuote($v);
			}
			$update = substr($u, 1);
		}
		$query = 'UPDATE '.$tbl.' SET '.$update.( $where ? ' WHERE '.$where : '' ).';';
		return $this->query($query);
	}

	public function delete($tbl, $where) {
		return $this->query('DELETE FROM '.$tbl.' WHERE '.$where.';');
	}


} // END Class db_generic

?>