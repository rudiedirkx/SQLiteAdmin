<?php

require_once('./include/inc.cls.db_sqlite.php');

$master = db_sqlite::open('./config.db');
if ( !$master->connected() ) {
	exit('Master isn\'t connected.');
}

if ( 0 == $master->count('sqlite_master', 'type = \'table\' AND tbl_name = \'aliases\'') ) {
	if ( !$master->query('CREATE TABLE aliases ( alias VARCHAR NOT NULL UNIQUE, path VARCHAR NOT NULL, description VARCHAR NOT NULL )') ) {
		exit('Master Alias table missing.');
	}
}

define( 'QS', '?'.$_SERVER['QUERY_STRING'] );


