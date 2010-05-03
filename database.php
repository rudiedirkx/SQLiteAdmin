<?php

require_once('inc.database.php');

echo '<fieldset><legend>Tables</legend>'."\n";
echo '<table border="1" cellpadding="4" cellspacing="2">'."\n";
echo '<tr><th>sqlite_master</th><td><a href="browse.php'.QS.'&tbl=sqlite_master">'.$db->count('sqlite_master').' rows</a></td><td colspan="4"></td></tr>'."\n";
foreach ( $db->select('sqlite_master', 'type = \'table\'') AS $t ) {
	echo '<tr>';
	echo '<th>'.$t['tbl_name'].'</th>';
	echo '<td><a href="browse.php'.QS.'&tbl='.$t['tbl_name'].'">'.$db->count($t['tbl_name']).' rows</a></td>';
	echo '<td><a href="structure.php'.QS.'&tbl='.$t['tbl_name'].'">structure</a></td>';
	echo '<td><a href="insert.php'.QS.'&tbl='.$t['tbl_name'].'">insert</a></td>';
	echo '<td><a href="#truncate_table.php'.QS.'&tbl='.$t['tbl_name'].'">truncate</a></td>';
	echo '<td><a href="#drop_table.php'.QS.'&tbl='.$t['tbl_name'].'">drop</a></td>';
	echo '</tr>';
}
echo '</table>'."\n";
echo '</fieldset>'."\n";

echo '<br />';

echo '<form method="post" action="?db='.$_GET['db'].'&query=1"><fieldset><legend>Query</legend>'."\n";
if ( isset($_POST['sql']) ) {
	$arrQueries = array_filter(explode(";\n\n", str_replace("\r", '', $_POST['sql'])), create_function('$q', 'return "" != trim($q);'));
	if ( 1 < count($arrQueries) ) {
		$db->begin();
	}
	foreach ( $arrQueries AS $q ) {
		echo '<div style="background-color:#faa;border:solid 3px white;padding:3px;">';
		echo '<div style="background-color:#afa;margin-bottom:3px;padding:2px;">'.$q.'</div>';
		echo '<pre>';
		if ( 0 === strpos($q2=strtolower(trim($q)), 'select') || 0 === strpos($q2, 'show') || 0 === strpos($q2, 'pragma') ) {
			if ( is_bool($r = $db->fetch($q)) ) {
				var_dump($r);
				echo $db->error."\n";
			}
			else {
				print_r($r);
			}
		}
		else {
			var_dump($r = $db->query($q));
			echo $db->error."\n";
			if ( !$r ) {
				if ( 1 < count($arrQueries) ) {
					$db->rollback();
					echo '<div><b><font size=5>ROLLBACK EXECUTED AFTER ERROR</font></b></div>';
					echo '<div style="background-color:#ddd;margin-top:3px;padding:2px;">Affected: '.$db->affected_rows().'; Insert ID: '.$db->insert_id().'</div>';
					echo '</pre></div>';
					$bRolledBack = true;
					break;
				}
			}
		}
		echo '</pre>';
		echo '<div style="background-color:#ddd;margin-top:3px;padding:2px;">Affected: '.$db->affected_rows().'; Insert ID: '.$db->insert_id().'</div>';
		echo '</div>';
	}
	if ( 1 < count($arrQueries) && empty($bRolledBack) ) {
		$db->commit();
	}
}
echo '<div><b>END QUERIES WITH A <font size=6>;</font></b></div>';
echo '<textarea name="sql" style="width:100%;" rows="10">'.( isset($_POST['sql']) ? htmlspecialchars($_POST['sql']) : '' ).'</textarea><br /><input type="submit" value="Execute" />';
echo '</fieldset></form>'."\n";


