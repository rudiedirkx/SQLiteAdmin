<?php #1.6

require_once(dirname(__FILE__).'/inc.cls.db_generic.php');

class db_sqlite extends db_generic {

	static function fn_if( $f_bool, $f_yes, $f_no ) {
		return $f_bool ? $f_yes : $f_no;
	}

	static function fn_rand() {
		return rand(0, 99999999);
	}

	static function fn_md5( $str ) {
		return md5((string)$str);
	}

	static function fn_sha1( $str ) {
		return sha1((string)$str);
	}

	static public function open( $file ) {
		require_once(dirname(__FILE__).'/inc.cls.db_sqlite3.php');
		$db = new db_sqlite3($file);
		if ( $db->connected() ) {
			return $db;
		}
		$db = new db_sqlite($file);
		return $db;
	}

	protected $dbCon = null;
	public $error = '';
	public $errno = 0;
	public $num_queries = 0;
	public $last_query = '';

	public function toQueryFieldList($list) {
		$r = array();
		foreach ( $list AS $c => $t ) {
			$r[] = $c.' '.$t;
		}
		return $r;
	}

	public function begin() {
		return $this->query('BEGIN TRANSACTION');
	}
	public function commit() {
		return $this->query('COMMIT');
	}
	public function rollback() {
		return $this->query('ROLLBACK TRANSACTION');
	}

	public function addColumn($tbl, $col) {
		if ( !is_array($col) ) {
			$col = array($col);
		}
		$arrQueries = array();
		// 1. create tmp table
		$arrQueries[] = 'CREATE TEMPORARY TABLE tmp ( '.implode(', ', $this->toQueryFieldList($this->structure($tbl))).', '.implode(', ', $col).' )';
		// 2. fill tmp table
		$arrQueries[] = 'INSERT INTO tmp SELECT *'.str_repeat(', NULL', count($col)).' FROM '.$tbl.'';
		// 3. drop actual table
		$arrQueries[] = 'DROP TABLE '.$tbl.'';
		// 4. create new actual table
		$pk = $this->indices($tbl, true);
		$arrQueries[] = 'CREATE TABLE '.$tbl.' ( '.implode(', ', $this->toQueryFieldList($this->structure($tbl))).', '.implode(', ', $col).( $pk ? ', PRIMARY KEY ('.implode(', ', $pk).')' : '' ).' )';
		// 5. fill new actual table
		$arrQueries[] = 'INSERT INTO '.$tbl.' SELECT * FROM tmp';
		// 6. drop tmp table
		$arrQueries[] = 'DROP TABLE tmp';
//echo '<pre>';print_r($arrQueries);exit;
		$this->begin();
		foreach ( $arrQueries AS $q ) {
			$r = $this->query($q);
			if ( !$r ) {
				$this->rollback();
				return false;
//				$bRolledBack = true;
//				break;
			}
		}
		$this->commit();
		return true;
	}

	public function indices( $tbl, $pk = false ) {
		if ( $pk ) {
			$pk = array();
			$cols = $this->fetch('PRAGMA table_info(`'.$tbl.'`)');
			foreach ( $cols AS $c ) {
				if ( $c['pk'] ) {
					$pk[] = $c['name'];
				}
			}
			return $pk;
		}
		$arrIndices = $this->fetch('PRAGMA index_list(`'.$tbl.'`)');
		if ( !function_exists('fn_db_sqlite_colname') ) {
			function fn_db_sqlite_colname($c) {
				return $c['name'];
			}
		}
		foreach ( $arrIndices AS $k => &$i ) {
			if ( $i['unique'] ) {
				unset($arrIndices[$k]);
			}
			else {
				$i['columns'] = array_map('fn_db_sqlite_colname', $this->fetch('PRAGMA index_info('.$i['name'].')'));
			}
			unset($i);
		}
		return $arrIndices;
	}

	public function structure($tbl) {
		$info = $this->fetch('PRAGMA table_info(`'.$tbl.'`)');
		$structure = array();
		foreach ( $info AS $col ) {
			$structure[$col['name']] = strtoupper($col['type']);
		}
		return (object)$structure;
	}

	public function __construct( $f_szDatabase, $f_szUser = null, $f_szPass = null, $f_szDb = null ) {
		try {
			$this->dbCon = new SQLiteDatabase( $f_szDatabase, 0777 );
		} catch ( SQLiteException $ex ) {
			try {
				$this->dbCon = new SQLite3( $f_szDatabase );
			}
			catch ( Exception $ex2 ) {
				$this->saveError();
			}
		}
	}

	public function saveError() {
		if ( $this->connected() ) {
			$this->errno = $this->dbCon->lastError();
			$this->error = $this->errno ? sqlite_error_string($this->errno) : '';
		}
		else {
			$this->error = 'Unable to open database';
			$this->errno = 1;
		}
	}

	public function connected() {
		return is_object($this->dbCon);
	}

	public function escape($v) {
		return sqlite_escape_string((string)$v);
	}

	public function insert_id() {
		return $this->dbCon->lastInsertRowid();
	}

	public function affected_rows() {
		return $this->dbCon->changes();
	}

	public function query( $f_szSqlQuery ) {
		$this->num_queries++;
		$this->last_query = $f_szSqlQuery;
		$r = $this->dbCon->query($f_szSqlQuery) or $this->saveError();
		$this->saveError();
		return $r;
	}

	public function fetch($f_szSqlQuery) {
		$r = $this->query($f_szSqlQuery);
		if ( !$r ) {
			return false;
		}
		return $r->fetchAll(SQLITE_ASSOC);
	}

	public function fetch_fields($f_szSqlQuery) {
		$r = $this->query($f_szSqlQuery);
		if ( !$r ) {
			return false;
		}
		$a = array();
		while ( $l = $r->fetch(SQLITE_NUM) ) {
			$a[$l[0]] = $l[1];
		}
		return $a;
	}

	public function select_one($tbl, $field, $where = '') {
		$r = $this->query('SELECT '.$field.' FROM '.$tbl.( $where ? ' WHERE '.$where : '' ).' LIMIT 1;');
		if ( !$r ) {
			return false;
		}
		return 0 < $r->numRows() ? $r->fetchSingle() : false;
	}

	public function count_rows($f_szSqlQuery) {
		$r = $this->query($f_szSqlQuery);
		if ( !$r ) {
			return false;
		}
		return $r->numRows();
	}

	public function select_by_field($tbl, $field, $where = '') {
		$r = $this->query('SELECT * FROM '.$tbl.( $where ? ' WHERE '.$where : '' ).';');
		if ( !$r ) {
			return false;
		}
		$a = array();
		while ( $l = $r->fetch(SQLITE_ASSOC) ) {
			$a[$l[$field]] = $l;
		}
		return $a;
	}

} // END Class db_sqlite


