<?php

// Alias record
$a = $master->select('aliases', ['alias' => $_db]);
$g_alias = count($a) ? (object) $a[0] : null;
if ( !$g_alias ) {
	return missingParams(array('db'));
}

// Actual db connection
$db = db_sqlite::open($g_alias->path);
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
