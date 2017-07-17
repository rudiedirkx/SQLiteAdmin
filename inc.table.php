<?php

$tables = $db->getTables();
if ( !isset($tables[$_tbl]) && !in_array($_tbl, array('sqlite_master', 'sqlite_sequence')) ) {
	return missingParams(array('tbl'));
}

$objTbl = (object) (@$tables[$_tbl] ?: array('name' => $_tbl));
