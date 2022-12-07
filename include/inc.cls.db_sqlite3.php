<?php #1.4

require_once(dirname(__FILE__).'/inc.cls.db_sqlite.php');

class db_sqlite3 extends db_sqlite {

	public $m_iAffectedRows = 0;

	public function begin() {
		return $this->dbCon->beginTransaction();
	}
	public function commit() {
		return $this->dbCon->commit();
	}
	public function rollback() {
		return $this->dbCon->rollBack();
	}

	public function __construct( $f_szDatabase, $f_szUser = null, $f_szPass = null, $f_szDb = null ) {
		$this->dbCon = new PDO('sqlite:' . $f_szDatabase);

		// Register custom functions
		$refl = new ReflectionClass(get_class($this));
		$methods = $refl->getMethods(ReflectionMethod::IS_STATIC);
		foreach ( $methods AS $method ) {
			if ( 0 === strpos($method->name, 'fn_') ) {
				$functionName = strtoupper(substr($method->name, 3));
				$this->dbCon->sqliteCreateFunction($functionName, array('db_sqlite', $method->name));
			}
		}
	}

	public function saveError( $error = true ) {
		if ( $error ) {
			$error = $this->dbCon->errorInfo();
			$this->errno = $error[1];
			$this->error = $error[2];
			$this->m_iAffectedRows = 0;
		}
		else {
			$this->errno = 0;
			$this->error = '';
		}
	}

	public function connected() {
		return false !== $this->dbCon->query('SELECT 1 FROM sqlite_master');
	}

	public function escape($v) {
		return str_replace("'", "''", (string)$v);
	}

	public function insert_id() {
		return $this->dbCon->lastInsertId();
	}

	public function affected_rows() {
		return $this->m_iAffectedRows;
	}

	public function query( $f_szSqlQuery ) {
		$this->num_queries++;
		$this->last_query = $f_szSqlQuery;
		$t = microtime(1);
		try {
			if ( false === ($r = $this->dbCon->query($f_szSqlQuery)) ) {
				$this->last_query_time = microtime(1) - $t;
				$this->saveError(true);
				return false;
			}
		}
		catch (PDOException $ex) {
			$this->last_query_time = microtime(1) - $t;
			$this->errno = $ex->getCode();
			$this->error = $ex->getMessage();
			$this->m_iAffectedRows = 0;
			return false;
		}
		$this->last_query_time = microtime(1) - $t;
		$this->saveError(false);
		$this->m_iAffectedRows = $r->rowCount();
		return $r;
	}

	public function fetch_columns($f_szSqlQuery) {
		$r = $this->query($f_szSqlQuery);
		if ( !$r ) {
			return false;
		}

		$cols = $r->columnCount();
		$header = [];
		for ($i = 0; $i < $cols; $i++) {
			$meta = $r->getColumnMeta($i);
			$header[] = $meta['name'];
		}

		$rows = $r->fetchAll(PDO::FETCH_NUM);
		if ( !count($rows) ) {
			return [];
		}

		array_unshift($rows, $header);
		return $rows;
	}

	public function fetch($f_szSqlQuery) {
		$r = $this->query($f_szSqlQuery);
		if ( !$r ) {
			return false;
		}
		return $r->fetchAll(PDO::FETCH_ASSOC);
	}

	public function fetch_fields($f_szSqlQuery) {
		$r = $this->query($f_szSqlQuery);
		if ( !$r ) {
			return false;
		}
		$a = array();
		while ( $l = $r->fetch(PDO::FETCH_NUM) ) {
			$a[$l[0]] = $l[1];
		}
		return $a;
	}

	public function fetch_one($query) {
		$r = $this->query($query);
		if ( !$r ) {
			return false;
		}
		return $r->fetchColumn();
	}

	public function count_rows($f_szSqlQuery) {
		$r = $this->query($f_szSqlQuery);
		if ( !$r ) {
			return false;
		}
		return count($r->fetchAll());
	}

	public function select_by_field($tbl, $field, $where = '') {
		$r = $this->query('SELECT * FROM '.$tbl.( $where ? ' WHERE '.$where : '' ).';');
		if ( !$r ) {
			return false;
		}
		$a = array();
		while ( $l = $r->fetch(PDO::FETCH_ASSOC) ) {
			$a[$l[$field]] = $l;
		}
		return $a;
	}

} // END Class db_sqlite3


