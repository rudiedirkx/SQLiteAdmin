<?php

require_once 'inc.config.php';

list($_db) = requireParams('db');
require_once 'inc.database.php';

require_once 'tpl.header.php';
require_once 'tpl.database.php';

$tables = $db->getTables();

$recreatableTables = array_diff(array_keys($tables), ['_version']);

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
				<td><a href="<?= QS ?>&recreate[]=<?= $t['tbl_name'] ?>">recreate</a></td>
			</tr>
		<?endforeach?>
	</table>
</fieldset>

<br />

<fieldset>
	<legend>Export</legend>
	<a href="<?= QS ?>&exportdata=1">Export data</a> |
	<a href="<?= QS ?>&exportstructure=1">Export structure</a> |
	<a href="<?= QS ?>&recreate[]=<?= implode('&recreate[]=', $recreatableTables) ?>">Recreate all</a>
</fieldset>

<br />

<?php

$sql = trim(@$_POST['sql']);

// EXPORT ALL DATA
if ( !$sql && @$_GET['exportdata'] ) {
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

// EXPORT ALL STRUCTURE
elseif ( !$sql && @$_GET['exportstructure'] ) {
	foreach ( $tables as $table ) {
		$sql .= rtrim($table['sql'], ';') . ";\n\n\n\n";
	}
}

// RECREATE SOME TABLES
elseif ( !$sql && @$_GET['recreate'] ) {
	$tableStructures = array_map(function($table) use ($db) {
		return $db->structure($table['name']);
	}, array_intersect_key($tables, array_flip($_GET['recreate'])));
	$tableColumns = array_map(function($columns) {
		$_columns = array();
		foreach ( $columns as $_col => $_typ ) {
			$_columns[$_col] = '"' . $_col . '" ' . strtoupper($_typ ?: 'TEXT');
		}
		return $_columns;
	}, $tableStructures);

	$sqls = array();
	foreach ( $tableColumns as $_tbl => $_columns ) {
		$sqls[] = 'CREATE TEMPORARY TABLE "tmp__' . $_tbl . '" (' . implode(', ', $_columns) . ');';
	}
	foreach ( $tableColumns as $_tbl => $_columns ) {
		$sqls[] = 'INSERT INTO "tmp__' . $_tbl . '" SELECT "' . implode('", "', array_keys($_columns)) . '" FROM "' . $_tbl . '";';
	}
	foreach ( $tableColumns as $_tbl => $_columns ) {
		$sqls[] = 'DROP TABLE "' . $_tbl . '";';
	}
	foreach ( $tableColumns as $_tbl => $_columns ) {
		$sqls[] = rtrim(trim($tables[$_tbl]['sql']), ';') . ';';
	}
	foreach ( $tableColumns as $_tbl => $_columns ) {
		$sqls[] = 'INSERT INTO "' . $_tbl . '" SELECT "' . implode('", "', array_keys($_columns)) . '" FROM "tmp__' . $_tbl . '";';
	}

	$sql = implode("\n\n", $sqls);
}

?>
<form method="post" action="?db=<?= html($_GET['db']) ?>&query=1">
	<fieldset>
		<legend>Query</legend>
		<?php

if ( isset($_POST['sql']) ) {
	$arrQueries = array_filter(explode(";\n\n", str_replace("\r", '', $_POST['sql'])), function($sql) { return trim($sql); });
	if ( 1 < count($arrQueries) ) {
		$db->begin();
	}

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

		?>
		<p><textarea name="sql" style="width: 100%" rows="10"><?= html($sql) ?></textarea></p>
		<p><button>Execute</button></p>
	</fieldset>
</form>

<script>
(function() {
	const el = document.querySelector('textarea[name="sql"]');
	const handler = function(e) {
		while (this.scrollHeight > this.offsetHeight) {
			this.rows++;
		}
	};
	handler.call(el);
	el.addEventListener('input', handler);
})();
</script>
