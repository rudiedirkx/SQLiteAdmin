<?php

require_once('./include/inc.cls.db_sqlite.php');

$master = db_sqlite::open('./config.db');
if ( !$master->connected() ) {
	exit('Master isn\'t connected.');
}

if ( 0 == $master->count('sqlite_master', 'type = \'table\' AND tbl_name = \'aliases\'') ) {
//	if ( !$master->query('CREATE TABLE aliases ( alias VARCHAR NOT NULL UNIQUE, path VARCHAR NOT NULL, description VARCHAR NOT NULL )') ) {
		exit('Master Alias table missing.');
//	}
}

session_start();

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
		return array('select', 'insert', 'update', 'delete', 'alter');
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
	echo '<!doctype html?<html><head><meta http-equiv="refresh" content="1;url='.$goto.'"></head><body><p>You gotsta <a href="'.$goto.'">login</a>....</p></body></html>';
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


