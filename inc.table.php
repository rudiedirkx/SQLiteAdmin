<?php

$tables = $db->getTables();
if ( !isset($tables[$_tbl]) ) {
	return missingParams(array('tbl'));
}

$objTbl = (object)$tables[$_tbl];
