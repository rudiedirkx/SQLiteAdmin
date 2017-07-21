<?php

require_once 'inc.config.php';

list($_db) = requireParams('db');
require_once 'inc.database.php';

require_once 'tpl.header.php';
require_once 'tpl.database.php';

$tables = $db->getTables();

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
					<th nowrap align="left"><?= $table ?></th>
					<td nowrap><a href="browse.php<?= QS ?>&tbl=<?= $table ?>"><?= bigNumber($db->count($table)) ?> rows</a></td>
					<td colspan="3"></td>
				</tr>
			<? endif ?>
		<? endforeach ?>
		<?foreach ($tables AS $t):
			$type = $t['type'] != 'table' ? ' (' . strtoupper($t['type']) . ')' : '';
			?>
			<tr>
				<th nowrap align="left"><?= $t['tbl_name'] . $type ?></th>
				<td nowrap><a href="browse.php<?= QS ?>&tbl=<?= $t['tbl_name'] ?>"><?= bigNumber($db->count('"' . $t['tbl_name'] . '"')) ?> rows</a></td>
				<td><a href="structure.php<?= QS ?>&tbl=<?= $t['tbl_name'] ?>">structure</a></td>
				<td><a href="insert.php<?= QS ?>&tbl=<?= $t['tbl_name'] ?>">insert</a></td>
				<td><a href="<?= QS ?>&recreate=<?= $t['tbl_name'] ?>">recreate</a></td>
			</tr>
		<?endforeach?>
	</table>
</fieldset>

<br />

<fieldset>
	<legend>Export</legend>
	<a href="<?= QS ?>&export=1">Export all</a>
</fieldset>

<br />

<?php

$sql = trim(@$_POST['sql']);

// EXPORT ALL TABLES
if ( !$sql && @$_GET['export'] ) {
	foreach ( $tables as $table ) {
		$rows = $db->select($table['tbl_name']);

		$columns = array_map(function($col) {
			return '"' . $col . '"';
		}, array_keys($rows[0]));
		$inserts = [];
		foreach ( array_chunk($rows, 10) as $chunk ) {
			$inserts[] = 'INSERT INTO "' . $table['tbl_name'] . '" (' . implode(', ', $columns) . ") VALUES\n(" . implode("),\n(", array_map(function($row) use ($db) {
				return implode(', ', array_map(function($value) use ($db) {
					return $value === null ? 'NULL' : "'" . $db->escape($value) . "'";
				}, $row));
			}, $chunk)) . ');';
		}

		$sql .= implode("\n\n\n\n", $inserts) . "\n\n\n\n\n\n";
	}
}

// RECREATE 1 TABLE
elseif ( !$sql && @$_GET['recreate'] ) {
	$_tbl = preg_replace('#[^\w\d ]#', '', $_GET['recreate']);
	$objTable = $db->structure($_tbl);

	// @todo Keep PRIMARY KEY

	if ( $objTable ) {
		$_columns = array();
		foreach ( (array)$objTable as $_col => $_typ) {
			$_columns[$_col] = '"' . $_col . '" ' . strtoupper($_typ ?: 'TEXT');
		}

		$sqls = array();
		$sqls[] = 'CREATE TEMPORARY TABLE "tmp__' . $_tbl . '" (' . implode(', ', $_columns) . ');';
		$sqls[] = 'INSERT INTO "tmp__' . $_tbl . '" SELECT "' . implode('", "', array_keys($_columns)) . '" FROM "' . $_tbl . '";';
		$sqls[] = 'DROP TABLE "' . $_tbl . '";';
		$sqls[] = 'CREATE TABLE "' . $_tbl . '" (' . implode(', ', $_columns) . ');';
		$sqls[] = 'INSERT INTO "' . $_tbl . '" SELECT "' . implode('", "', array_keys($_columns)) . '" FROM "tmp__' . $_tbl . '";';

		$sql = implode("\n\n", $sqls);
	}
}

echo '<form method="post" action="?db=' . $_GET['db'] . '&query=1"><fieldset><legend>Query</legend>'."\n";
if ( isset($_POST['sql']) ) {
	$arrQueries = array_filter(explode(";\n\n", str_replace("\r", '', $_POST['sql'])), function($sql) { return trim($sql); });
	if ( 1 < count($arrQueries) ) {
		$db->begin();
	}
	// Here somewhere should be the call(s) to $g_objUser->alias->allowQuery($query)
	foreach ( $arrQueries AS $q ) {
		echo '<div style="background-color:#faa; border:solid 3px white;padding:3px;">';
		echo '<div style="background-color:#afa; margin-bottom:3px;padding:2px;">' . html($q) . '</div>';
		$error = false;
		echo '<pre>';
		if ( 0 === strpos($q2=strtolower(trim($q)), 'select') || 0 === strpos($q2, 'show') || 0 === strpos($q2, 'pragma') ) {
			if ( is_bool($r = $db->fetch($q)) ) {
				$error = true;
				var_dump($r);
				echo 'ERROR: ' . $db->error."\n";
			}
			else {
				echo html(print_r($r, 1));
			}
		}
		else {
			ob_start();
			var_dump($r = $db->query($q));
			$dump = ob_get_clean();
			echo html($dump);

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
echo '<textarea name="sql" style="font-size:13px;width:100%;" rows="10">' . html($sql) . '</textarea><br /><input type="submit" value="Execute" />';
echo '</fieldset></form>'."\n";
