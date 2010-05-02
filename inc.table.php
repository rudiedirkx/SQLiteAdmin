<?php

require_once('inc.database.php');

if ( empty($_GET['tbl']) || ( 'sqlite_master' != $_GET['tbl'] && 0 == count($arrTbl=$db->select('sqlite_master', "type = 'table' AND tbl_name = '".$db->escape($_GET['tbl'])."'")) ) ) {
	echo '<p style="color:red;">Select a table</p>';
	require_once('database.php');
	exit;
}

$objTbl = (object)( empty($arrTbl) ? array('type' => 'table', 'name' => 'sqlite_master', 'tbl_name' => 'sqlite_master') : $arrTbl[0] );
$szTable = $objTbl->tbl_name;

echo '<fieldset><legend>Selected table: `'.$szTable.'`</legend><a href="browse.php?db='.$_GET['db'].'&tbl='.$_GET['tbl'].'">browse</a> | <a href="structure.php'.QS.'">structure</a> | <a href="insert.php'.QS.'">insert</a><!-- | <a href="truncate.php'.QS.'">truncate</a> | <a href="drop.php'.QS.'">drop</a>--></fieldset><br />'."\n\n";

?>