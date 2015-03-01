<?php

require_once('./include/inc.cls.db_sqlite.php');

header('Content-type: text/html; charset=utf-8');
header('X-XSS-Protection: 0');

$master = db_sqlite::open(__DIR__ . '/config/config.db');
if ( !$master->connected() ) {
	exit("Can't connect to master.");
}

session_start();

function ensureMasterStructure( $act = true ) {
	global $master;

	$schema = require 'inc.db-schema.php';

	$mustExistTables = array_keys($schema['tables']);
	$exist = $master->select_fields('sqlite_master', 'tbl_name, tbl_name', array('type' => 'table', 'tbl_name' => $mustExistTables));
	if ( count($exist) < count($mustExistTables) ) {
		// No structure
		if ( !$act ) {
			exit("Can't setup master structure.");
		}

		foreach (array_diff_key($schema['tables'], $exist) as $tableName => $columns) {
			$master->createTable($tableName, $columns);
		}

		return ensureMasterStructure(false);
	}

	return true;
}
ensureMasterStructure();

define( 'QS', '?'.$_SERVER['QUERY_STRING'] );

define( 'S_NAME', 'sliteadmin' );

class User {
	public function __construct($data) {
		$this->fill($data);
		$this->master = $GLOBALS['master'];
	}
	public function fill($data) {
		foreach ( $data AS $k => $v ) {
			$this->$k = $v;
		}
	}
	public function isAdmin() {
		return 0 == (int)$this->user_type;
	}
	public function getAliasByAlias( $alias ) {
		$a = $this->master->select('aliases', "alias = '".$this->master->escape($alias)."'" . ( !$this->isAdmin() ? ' AND (public = 1 OR id IN ( SELECT alias_id FROM user_alias_access WHERE user_id = '.USER_ID.' ))' : '' ));
		return $a ? (object)$a[0] : false;
	}
	public function getAliasById( $id ) {
		$a = $this->master->select('aliases', 'id = '.(int)$id . ( !$this->isAdmin() ? ' AND (public = 1 OR id IN ( SELECT alias_id FROM user_alias_access WHERE user_id = '.USER_ID.' ))' : '' ));
		return $a ? (object)$a[0] : false;
	}
	public function getAliases() {
		if ( $this->isAdmin() ) {
			// admin
			return $this->master->select_by_field('aliases', 'alias', '1 ORDER BY alias');
		}
		// normal user
		return $this->master->select_by_field('aliases', 'alias', 'public = 1 OR id IN ( SELECT alias_id FROM user_alias_access WHERE user_id = '.USER_ID.' ) ORDER BY alias');
	}
	public function loadAlias($name) {
		if ( isset($GLOBALS['g_arrAliases'][$name]) ) {
			return $this->alias = new UsedAlias($GLOBALS['g_arrAliases'][$name], $this);
		}
		return false;
	}
}

class UsedAlias {
	public function __construct($data, $user) {
		$this->fill($data);
		$this->master = $GLOBALS['master'];
		$this->user = $user;
	}
	public function fill($data) {
		foreach ( $data AS $k => $v ) {
			$this->$k = $v;
		}
	}
	public function allowedQueries() {
		static $aq;
		if ( !isset($aq) ) {
			$allow = $this->master->select('user_alias_access', 'user_id = '.(int)$this->user->id.' AND alias_id = '.(int)$this->id.'');
//var_dump($allow);
			$aq = $allow ? array_map('trim', explode(',', $allow[0]['allowed_queries'])) : array();
		}
		return $aq;
//		return array('select', 'insert', 'update', 'delete', 'alter');
//		return explode(',', strtolower($this->allowed_queries));
	}
	public function allowQuery($query) {
		if ( $this->user->isAdmin() ) {
			return true;
		}
		$query = strtolower(query);
		foreach ( $this->allowedQueries() AS $qtype ) {
			if ( 0 === strpos($query, $qtype.' ') ) {
				return true;
			}
		}
		return false;
	}
}

function bigNumber( $number ) {
	return number_format($number, 0, '.', ' ');
}

function logincheck() {
	if ( defined('USER_ID') && isset($GLOBALS['g_objUser']) ) {
		return true;
	}

	if ( isset($_SESSION[S_NAME]['user_id'], $_SESSION[S_NAME]['logouttime']) && time() < (int)$_SESSION[S_NAME]['logouttime'] && 1 == count($u = $GLOBALS['master']->select('users', 'id = '.(int)$_SESSION[S_NAME]['user_id'])) ) {
		$GLOBALS['g_objUser'] = new User($u[0]);
		define( 'USER_ID', (int)$_SESSION[S_NAME]['user_id'] );
		return true;
	}

	return false;
}

function isAdmin() {
	return logincheck() && $GLOBALS['g_objUser']->isAdmin();
}


if ( 'login.php' != basename($_SERVER['PHP_SELF']) && !logincheck() ) {
	$goto = 'login.php?goto='.urlencode($_SERVER['REQUEST_URI']);
	header('Location: ' . $goto);
	exit;
}


if ( logincheck() ) {
	$g_arrAliases = $g_objUser->getAliases();
}
else {
	// guest
	$g_arrAliases = $master->select('aliases', 'public = 1 ORDER BY alias');
}
//print_r($g_arrAliases);


