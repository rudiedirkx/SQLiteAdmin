<?php

require_once('inc.database.php');

$tables = $db->select('sqlite_master', "
	type IN ('table', 'view') AND
	tbl_name NOT IN ('sqlite_sequence')
	ORDER BY tbl_name ASC
");

?>
<style>
.result-meta {
	background-color: #ddd;
	margin-top: 3px;
	padding: 3px;
}
.result-meta.success {
	color: green;
}
.result-meta.error {
	padding: 8px;
	background-color: red;
	color: #fff;
}
</style>

<fieldset>
	<legend>Tables</legend>
	<table border="1" cellpadding="4" cellspacing="2">
		<? foreach (array('sqlite_master', 'sqlite_sequence') as $table):
			$rows = $db->count($table);
			if ($rows !== false): ?>
				<tr>
					<th align="left"><?= $table ?></th>
					<td><a href="browse.php<?= QS ?>&tbl=<?= $table ?>"><?= bigNumber($db->count($table)) ?> rows</a></td>
					<td colspan="4"></td>
				</tr>
			<? endif ?>
		<? endforeach ?>
		<?foreach ($tables AS $t):
			$type = $t['type'] != 'table' ? ' (' . strtoupper($t['type']) . ')' : '';
			?>
			<tr>
				<th align="left"><?= $t['tbl_name'] . $type ?></th>
				<td><a href="browse.php<?= QS ?>&tbl=<?= $t['tbl_name'] ?>"><?= bigNumber($db->count('"' . $t['tbl_name'] . '"')) ?> rows</a></td>
				<td><a href="structure.php<?= QS ?>&tbl=<?= $t['tbl_name'] ?>">structure</a></td>
				<td><a href="insert.php<?= QS ?>&tbl=<?= $t['tbl_name'] ?>">insert</a></td>
				<td><a href="#truncate_table.php<?= QS ?>&tbl=<?= $t['tbl_name'] ?>">truncate</a></td>
				<td><a href="#drop_table.php<?= QS ?>&tbl=<?= $t['tbl_name'] ?>">drop</a></td>
			</tr>
		<?endforeach?>
	</table>
</fieldset>

<br />

<?php

echo '<form method="post" action="?db='.$_GET['db'].'&query=1"><fieldset><legend>Query</legend>'."\n";
if ( isset($_POST['sql']) ) {
	$arrQueries = array_filter(explode(";\n\n", str_replace("\r", '', $_POST['sql'])), create_function('$q', 'return "" != trim($q);'));
	if ( 1 < count($arrQueries) ) {
		$db->begin();
	}
	// Here somewhere should be the call(s) to $g_objUser->alias->allowQuery($query)
	foreach ( $arrQueries AS $q ) {
		echo '<div style="background-color:#faa; border:solid 3px white;padding:3px;">';
		echo '<div style="background-color:#afa; margin-bottom:3px;padding:2px;">'.$q.'</div>';
		$error = false;
		echo '<pre>';
		if ( 0 === strpos($q2=strtolower(trim($q)), 'select') || 0 === strpos($q2, 'show') || 0 === strpos($q2, 'pragma') ) {
			if ( is_bool($r = $db->fetch($q)) ) {
				$error = true;
				var_dump($r);
				echo 'ERROR: ' . $db->error."\n";
			}
			else {
				print_r($r);
			}
		}
		else {
			var_dump($r = $db->query($q));
			if ( !$r ) {
				$error = true;
				echo 'ERROR: ' . $db->error."\n";
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
		$class = $error ? 'error' : 'success';
		echo '<div class="result-meta '.$class.'">Affected: '.$db->affected_rows().'; Insert ID: '.$db->insert_id().'</div>';
		echo '</div>';
	}
	if ( 1 < count($arrQueries) && empty($bRolledBack) ) {
		$db->commit();
	}
}
echo '<div><b>END QUERIES WITH A <font size=6>;</font></b></div>';
echo '<textarea name="sql" style="font-size:13px;width:100%;" rows="10">'.( isset($_POST['sql']) ? htmlspecialchars($_POST['sql']) : '' ).'</textarea><br /><input type="submit" value="Execute" />';
echo '</fieldset></form>'."\n";


