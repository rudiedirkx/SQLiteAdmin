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

$g_arrAllowedAliases = array();
function logincheck() {
	if ( defined('USER_ID') && isset($GLOBALS['g_objUser']) ) {
		return true;
	}
	if ( isset($_SESSION[S_NAME]['user_id'], $_SESSION[S_NAME]['logouttime']) && time() < (int)$_SESSION[S_NAME]['logouttime'] && 1 == count($u = $GLOBALS['master']->select('users', 'id = '.(int)$_SESSION[S_NAME]['user_id'])) ) {
		$GLOBALS['g_objUser'] = (object)$u[0];
		define( 'USER_ID', (int)$_SESSION[S_NAME]['user_id'] );
		return true;
	}
	return false;
}


if ( logincheck() ) {
	if ( $g_objUser->user_type == 0 ) {
		// admin
		$g_arrAliases = $master->select('aliases', '1 ORDER BY alias');
	}
	else {
		// normal user
		$g_arrAliases = $master->select('aliases', '1 ORDER BY alias');
	}
}
else {
	// guest
	$g_arrAliases = $master->select('aliases', 'public = 1 ORDER BY alias');
}


