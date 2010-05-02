<?php

require_once('inc.config.php');

if ( !isset($_GET['db']) ) {
	exit('Need &db');
}

$db = new db_sqlite('./dbs/'.$_GET['db'].'');
var_dump($db);


