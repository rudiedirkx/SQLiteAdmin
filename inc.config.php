<?php

require __DIR__ . '/env.php';
require_once __DIR__ . '/include/inc.cls.db_sqlite.php';
require_once __DIR__ . '/inc.functions.php';

header('Content-type: text/html; charset=utf-8');
header('X-XSS-Protection: 0');

$master = db_sqlite::open(__DIR__ . '/config/config.db');
if ( !$master->connected() ) {
	exit("Can't connect to master.");
}

// Better speed and no need for writable dirs.
$master->query('PRAGMA synchronous=OFF');
$master->query('PRAGMA journal_mode=OFF');

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

function html( $text ) {
	return @htmlspecialchars($text, ENT_COMPAT, 'UTF-8') ?: @htmlspecialchars($text, ENT_COMPAT, 'ISO-8859-1');
}

function bigNumber( $number ) {
	return number_format($number, 0, '.', ' ');
}

function requireParams($param1) {
	$want = array_flip(func_get_args());
	$have = array_filter($_GET);
	$miss = array_diff_key($want, $have);
	if ( $miss ) {
		return missingParams(array_keys($miss));
	}

	$values = array();
	foreach ($want as $name => $foo) {
		$values[] = $have[$name];
	}
	return $values;
}

function missingParams($params) {
	exit('Missing params: ' . html(implode(', ', $params)));
}

function logincheck() {
	if ( !isset($_SESSION[S_NAME]['user'], $_SESSION[S_NAME]['pass']) ) {
		return false;
	}

	$user = $_SESSION[S_NAME]['user'];
	$pass = $_SESSION[S_NAME]['pass'];

	if ( !isset($_SESSION[S_NAME]['logouttime']) || time() > $_SESSION[S_NAME]['logouttime'] ) {
		return false;
	}

	if ( !isset(ADMIN_USERS[$user]) || sha1(ADMIN_USERS[$user]) !== $pass ) {
		return false;
	}

	return true;
}

function tokencheck() {
	return sha1($_SESSION[S_NAME]['pass']) === ($_GET['_token'] ?? 'x');
}

function tokenmake() {
	return sha1($_SESSION[S_NAME]['pass']);
}

if ( 'login.php' !== basename($_SERVER['PHP_SELF']) && !logincheck() ) {
	$goto = 'login.php?goto=' . urlencode($_SERVER['REQUEST_URI']);
	header('Location: ' . $goto);
	exit;
}

$g_arrAliases = $master->select_by_field('aliases', 'alias', '1=1 ORDER BY alias ASC');
// print_r($g_arrAliases);
