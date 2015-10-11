<?php

// Select db meta record
$objDb = $g_objUser->getAliasByAlias($_db);
if ( !$objDb ) {
	return missingParams(array('db'));
}

// Actual db connection
$db = db_sqlite::open($objDb->path);
if ( !$db->connected() ) {
	exit("Can't connect: " . html($db->error));
}

$g_objUser->loadAlias($objDb->alias);
