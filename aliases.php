<?php

require_once('inc.config.php');

if ( logincheck() && $g_objUser->isAdmin() && isset($_POST['alias'], $_POST['path'], $_POST['description']) ) {
	if ( isset($_GET['edit']) ) {
		$master->update('aliases', $_POST, 'alias = \''.$master->escape($_GET['edit']).'\'');
	}
	else {
		$master->insert('aliases', $_POST);
	}
	header('Location: aliases.php');
	exit;
}

else if ( logincheck() && $g_objUser->isAdmin() && isset($_GET['delete']) ) {
	$master->delete('aliases', 'alias = \''.$master->escape($_GET['delete']).'\'');
	header('Location: aliases.php');
	exit;
}

else if ( logincheck() && isset($_GET['download']) ) {
	$alias = $g_objUser->getAliasByAlias( $_GET['download'] );
	if ( $alias && file_exists($alias->path) && is_readable($alias->path) ) {
		header('Content-type: text/plain');
		header('Content-disposition: attachment; filename="'.basename($alias->path).'"');
		readfile($alias->path);
	}
	exit;
}

?>
<html>

<head>
<title>Aliases<?php echo logincheck() ? ' ('.$g_objUser->username.')' : ''; ?></title>
<link rel="stylesheet" href="base.css" />
</head>

<body>

<?php include('inc.logincheckheader.php'); ?>

<table border="1" cellpadding="4" cellspacing="2">
<tr>
	<th></th>
	<th>Alias</th>
	<th>Public?</th>
	<th>Path</th>
	<th>Description</th>
	<th>Version</th>
	<th>Readable?</th>
	<th>Size</th>
	<th>Writable?</th>
	<th colspan="2"></th>
</tr>
<?php

$n = 0;
foreach ( $g_arrAliases AS $a ) {
	$version = '-';
	if ( file_exists($a['path']) && 0 < filesize($a['path']) ) {
		$version = '?';
		$fp = fopen($a['path'], 'r');
		$bytes = strtolower(fread($fp, 40));
		fclose($fp);
		if ( is_int(strpos($bytes, 'sqlite 2')) ) {
			$version = 2;
		}
		else if ( is_int(strpos($bytes, 'sqlite format 3')) ) {
			$version = 3;
		}
	}

	$kb = filesize($a['path']) / 1024;
	$size =  $kb > 1500 ? round($kb / 1024, 1) . ' <b>MB</b>' : round($kb) . ' kB';

	$odd = !($n % 2);
	$zebra = $odd ? 'odd' : 'even';

	echo '<tr class="' . $zebra . '">';
	echo '<td><a href="database.php?db=' . urlencode($a['alias']) . '">open</a></td>';
	echo '<td><a href="?edit=' . urlencode($a['alias']) . '">' . $a['alias'] . '</a></td>';
	echo '<td align="center">' . ( $a['public'] ? 'Y' : 'N' ) . '</td>';
	echo '<td>' . $a['path'] . '</td>';
	echo '<td>' . $a['description'] . '</td>';
	echo '<td align="center">' . $version . '</td>';
	echo '<td align="center">' . ( is_readable($a['path']) ? 'Y' : 'N' ) . '</td>';
	echo '<td align="right">' . ( is_readable($a['path']) ? $size : '-' ) . '</td>';
	echo '<td align="center">' . ( is_writable($a['path']) ? 'Y' : 'N' ) . '</td>';
	if ( isAdmin() ) {
		echo '<td align="center"><a href="?delete=' . urlencode($a['alias']) . '">del</a></td>';
	}
	echo '<td align="center"><a href="?download=' . urlencode($a['alias']) . '">download</a></td>';
	echo '</tr>'."\n";

	$n++;
}
echo '</table>'."\n";

if ( logincheck() && $g_objUser->isAdmin() ) {

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

	echo '<form enctype="multipart/form-data" method="post" action="aliases.php'.( !empty($_GET['edit']) ? '?edit='.$_GET['edit'] : '' ).'">';
	echo '<table border="1" cellpadding="4" cellspacing="2">'."\n";
	echo '<tr><th colspan="2">'.( !empty($_GET['edit']) ? 'Edit' : 'New' ).' alias</th></tr>'."\n";
	echo '<tr><th>Alias</th><td><input type="text" name="alias" value="'.( $arrAlias ? html($arrAlias['alias']) : '' ).'" size="60" /></td></tr>'."\n";
	echo '<tr><th>Path</th><td><input type="text" name="path" value="'.( $arrAlias ? html($arrAlias['path']) : '' ).'" size="60" /></td></tr>'."\n";
	echo '<tr><th>Description</th><td><input type="text" name="description" value="'.( $arrAlias ? html($arrAlias['description']) : '' ).'" size="60" /></td></tr>'."\n";
	echo '<tr><th colspan="2"><input type="submit" value="Save" /></th></tr>'."\n";
	echo '</table>';
	echo '</form>'."\n";

}

?>
</body>

</html>


