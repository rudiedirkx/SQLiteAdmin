<?php

require_once('inc.table.php');

$iPage = isset($_GET['page']) && 0 <= (int)$_GET['page'] ? (int)$_GET['page'] : 0;
$iLimit = 200;
$iStart = $iPage * $iLimit;
$bCrop = empty($_GET['nocrop']);

$szSql = 'SELECT * FROM "'.$szTable.'" WHERE 1 LIMIT '.$iStart.', '.$iLimit;
if ( !empty($_GET['sql']) ) {
	$szSql = $_GET['sql'];
}

//$arrTable = $db->structure($szTable);

echo '<form action="?">';
echo '<input type="hidden" name="db" value="'.$_GET['db'].'" />';
echo '<input type="hidden" name="tbl" value="'.$_GET['tbl'].'" />';
echo '<textarea id="sqlq" name="sql" style="width:99%;" rows=4 autofocus>'.htmlspecialchars($szSql).'</textarea>';
echo '</form>';
echo '<script>document.getElementById(\'sqlq\').addEventListener(\'keydown\', function(e){ if (e.keyCode == 13 && e.ctrlKey) { e.preventDefault(); this.form.submit(); } });</script>';

$arrContents = $db->fetch($szSql);
if ( $arrContents ) {
	echo '<style style="text/css">tbody.pre td { font-family:\'Courier New\'; font-size:13px; white-space:pre; }</style>';
	echo '<table border="1" cellpadding="4" cellspacing="2">';
	echo '<thead><tr><th colspan="'.count($arrContents[0]).'">'.count($arrContents).' / '.$db->count($szTable, '1').' records | <a href="?'.$_SERVER['QUERY_STRING'].'&nocrop=1">nocrop</a></th></tr>';
	echo '<tr>';
	foreach ( $arrContents[0] AS $k => $v ) {
		echo '<th>'.$k.'</th>';
	}
	echo '</tr>';
	echo '</thead><tbody class="pre">';
	foreach ( $arrContents AS $k => $r ) {
		echo '<tr>';
		foreach ( $r AS $k => $v ) {
			echo '<td>'.( null === $v ? '<i>NULL</i>' : ( $bCrop && 50 < strlen($v) ? htmlspecialchars(substr($v, 0, 47)).'...' : htmlspecialchars($v) ) ).'</td>';
		}
		echo '</tr>';
	}
	echo '</tbody></table>'."\n";
}
else {
	if ( false === $arrContents ) {
		echo '<p>'.$db->error.'</p>';
	}
	else {
		echo '<p>no records returned</p>';
	}
}


