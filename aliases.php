<?php

require_once('inc.config.php');

if ( isset($_POST['alias'], $_POST['path'], $_POST['description']) ) {
	if ( isset($_GET['edit']) ) {
		$master->update('aliases', $_POST, 'alias = \''.$master->escape($_GET['edit']).'\'');
	}
	else {
		$master->insert('aliases', $_POST);
	}
	header('Location: aliases.php');
	exit;
}

else if ( isset($_GET['delete']) ) {
	$master->delete('aliases', 'alias = \''.$master->escape($_GET['delete']).'\'');
	header('Location: aliases.php');
	exit;
}

else if ( isset($_GET['download']) ) {
	$path = $master->select_one('aliases', 'path', 'alias = \''.$master->escape($_GET['download']).'\'');
	if ( file_exists($path) && is_readable($path) ) {
		header('Content-type: text/plain');
		header('Content-disposition: attachment; filename="'.basename($path).'"');
		readfile($path);
	}
	exit;
}

echo '<title>Aliases</title>';

$arrAliases = $master->select('aliases', '1 ORDER BY alias');

echo '<table border="1" cellpadding="4" cellspacing="2">'."\n";
echo '<tr><th></th><th>Alias</th><th>Path</th><th>Description</th><th>Version</th><th>Readable</th><th>Size</th><th>Writable</th><th>Delete</th><th>Download</th></tr>'."\n";
foreach ( $arrAliases AS $a ) {
	$version = !file_exists($a['path']) || 0 == filesize($a['path']) ? '-' : ( 'SQLite format 3' == substr(file_get_contents($a['path']), 0, 15) ? '3' : '2' );
	echo '<tr>';
	echo '<td><a href="database.php?db='.urlencode($a['alias']).'">open</a></td>';
	echo '<td><a href="?edit='.urlencode($a['alias']).'">'.$a['alias'].'</a></td>';
	echo '<td>'.$a['path'].'</td>';
	echo '<td>'.$a['description'].'</td>';
	echo '<td align="center">'.$version.'</td>';
	echo '<th>'.(is_readable($a['path'])?'Y':'N').'</th>';
	echo '<th>'.( is_readable($a['path']) ? number_format(ceil(filesize($a['path'])/1024), 0, '.', ' ').' KB' : '-' ).'</th>';
	echo '<th>'.(is_writable($a['path'])?'Y':'N').'</th>';
	echo '<td align="center"><a href="?delete='.urlencode($a['alias']).'">del</a></td>';
	echo '<td align="center"><a href="?download='.urlencode($a['alias']).'">download</a></td>';
	echo '</tr>'."\n";
}
echo '</table>'."\n";

echo '<br />'."\n";

$arrAlias = null;
if ( !empty($_GET['edit']) ) {
	$arrAlias = $master->select('aliases', 'alias = \''.$master->escape($_GET['edit']).'\' LIMIT 2');
	if ( 1 == count($arrAlias) ) {
		$arrAlias = $arrAlias[0];
	}
	else {
		unset($_GET['edit'], $arrAlias);
		$arrAlias = null;
	}
}

echo '<form method="post" action="aliases.php'.( !empty($_GET['edit']) ? '?edit='.$_GET['edit'] : '' ).'"><table border="1" cellpadding="4" cellspacing="2">'."\n";
echo '<tr><th colspan="2">'.( !empty($_GET['edit']) ? 'Edit' : 'New' ).' alias</th></tr>'."\n";
echo '<tr><th>Alias</th><td><input type="text" name="alias" value="'.( $arrAlias ? $arrAlias['alias'] : '' ).'" size="60" /></td></tr>'."\n";
echo '<tr><th>Path</th><td><input type="text" name="path" value="'.( $arrAlias ? $arrAlias['path'] : '' ).'" size="60" /></td></tr>'."\n";
echo '<tr><th>Description</th><td><input type="text" name="description" value="'.( $arrAlias ? $arrAlias['description'] : '' ).'" size="60" /></td></tr>'."\n";
echo '<tr><th colspan="2"><input type="submit" value="Save" /></th></tr>'."\n";
echo '</table></form>'."\n";


