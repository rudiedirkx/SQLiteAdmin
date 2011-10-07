<?php

require_once('inc.config.php');

if ( empty($_GET['db']) || !($objDb = $g_objUser->getAliasByAlias($_GET['db'])) ) {
	echo '<select onchange="document.location=\'?db=\'+encodeURIComponent(this.value);"><option value="">--</option>';
	foreach ( $g_arrAliases AS $a ) {
		echo '<option value="'.$a['alias'].'">'.$a['alias'].'</option>';
	}
	echo '</select>';
	exit;
}

$db = db_sqlite::open($objDb->path);
if ( !$db->connected() ) {
	exit('Can\'t connect: '.$db->error);
}

$g_objUser->loadAlias($objDb->alias);

include 'inc.tpl.header.php';

include 'inc.logincheckheader.php';

echo '<fieldset><legend>Selected <a href="database.php?db='.$_GET['db'].'">database</a></legend>['.$_GET['db'].']: &nbsp; <u>'.$objDb->path.'</u> &nbsp; (<a href="aliases.php">aliases</a>) (current access: '.( $g_objUser->isAdmin() ? 'unlimited' : implode(', ', $g_objUser->alias->allowedQueries()) ).')</fieldset><br />'."\n\n";


