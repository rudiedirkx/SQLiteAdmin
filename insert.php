<?php

require_once 'inc.config.php';

list($_db, $_tbl) = requireParams('db', 'tbl');
require_once 'inc.database.php';
require_once 'inc.table.php';

require_once 'tpl.header.php';
require_once 'tpl.database.php';
require_once 'tpl.table.php';

if ( isset($_POST['insert']) ) {
	function str_concat($arr) {
		$r = '';
		foreach ( $arr AS $str ) {
			$r .= (string)$str;
		}
		return $r;
	}
	echo '<pre>';
	$iRows = 0;
	$db->begin();
	foreach ( $_POST['insert'] AS $rec ) {
		if ( '' != str_concat($rec) ) {
			$iRows++;
			$i = $db->insert($_tbl, $rec);
			if ( !$i ) {
				$db->rollback();
				echo '[Error: '.$db->error."]\n";
				echo '<h1>ROLLBACKED</h1>';
				exit;
			}
		}
	}
	$db->commit();
	echo $iRows.' rows added'."\n";
	exit;
}

$cols = (array)$db->structure($_tbl);
if ( 1 == count($pk = $db->indices($_tbl, true)) ) {
	foreach ( $cols AS $k => $v ) {
		if ( $k == $pk[0] ) {
			unset($cols[$k]);
		}
	}
}

echo '<form method="post" action=""><table border="1">';
echo '<tr>';
foreach ( $cols AS $c => $x ) {
	echo '<th>'.$c.'</th>';
}
echo '</tr>';
for ( $i=0; $i<20; $i++ ) {
	echo '<tr>';
	foreach ( $cols AS $c => $x ) {
		echo '<td><input type="text" name="insert['.$i.']['.$c.']" /></td>';
	}
	echo '</tr>';
}
echo '<tr><td colspan="'.count($cols).'" align="center"><input type="submit" value="Insert!" /></td></trs>';
echo '</table></form>';


