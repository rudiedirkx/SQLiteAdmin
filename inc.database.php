<?php

require_once('inc.config.php');

if ( empty($_GET['db']) || 0 == count($arrDb=$master->select('aliases', "alias = '".$master->escape($_GET['db'])."'")) ) {
	$arrAliases = $master->select('aliases', '1 ORDER BY alias ASC');
	echo '<select onchange="document.location=\'?db=\'+encodeURIComponent(this.value);"><option value="">--</option>';
	foreach ( $arrAliases AS $a ) {
		echo '<option value="'.$a['alias'].'">'.$a['alias'].'</option>';
	}
	echo '</select>';
	exit;
}

$objDb = (object)$arrDb[0];
$db = db_sqlite::open($objDb->path);
if ( !$db->connected() ) {
	exit('Can\'t connect: '.$db->error);
}

//var_dump($db);

echo '<fieldset><legend>Selected <a href="database.php?db='.$_GET['db'].'">database</a></legend>'.$objDb->path.' (<a href="aliases.php">aliases</a>)</fieldset><br />'."\n\n";


