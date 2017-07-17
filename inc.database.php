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

// set encoding
$db->query('PRAGMA encoding="UTF-8"');

// screw ACID, go SPEED!
$db->query('PRAGMA synchronous=OFF');
$db->query('PRAGMA journal_mode=OFF');

// in case it uses foreign key constraints
try {
	$db->query('PRAGMA foreign_keys = ON');
}
catch ( Exception $ex ) {}

$g_objUser->loadAlias($objDb->alias);
