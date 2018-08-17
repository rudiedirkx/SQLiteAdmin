<?php

require_once 'inc.config.php';

list($_db, $_tbl) = requireParams('db', 'tbl');
require_once 'inc.database.php';
require_once 'inc.table.php';

$iPage = max(0, (int)@$_GET['page']);
$iLimit = 200;
$iStart = $iPage * $iLimit;

$szSql = trim(@$_GET['sql'] ?: 'SELECT * FROM ' . $db->escapeAndQuoteStructure($_tbl) . ' WHERE 1 LIMIT ' . $iStart . ', ' . $iLimit);

$nocrop = (int)!empty($_GET['nocrop']);
$flip = (int)!empty($_GET['flip']);
$export = (int)!empty($_GET['export']);

$arrContents = $db->fetch($szSql);
if ( $arrContents ) {
	$cell = function($value, $th = false) use ($export) {
		return $export ? $value : array('th' => $th, 'data' => $value);
	};

	$data = array();
	if ($flip) {
		foreach ($arrContents as $i => $row) {
			// Row delimiter row
			$data[] = array(
				$cell('', true),
				$cell('# ' . ($i+1), true),
			);

			foreach ($row as $name => $value) {
				// One row per column
				$data[] = array(
					$cell($name, true),
					$cell($value),
				);
			}
		}
	}
	else {
		foreach ($arrContents as $i => $row) {
			$subdata = array();
			foreach ($row as $name => $value) {
				// Header
				if ( !isset($data[1]) ) {
					$data[0][] = $cell($name, true);
				}

				// Column
				$subdata[] = $cell($value, false);
			}
			$data[] = $subdata;
		}
	}

	if ( $export ) {
		csv_header('export.csv');
		echo csv_rows($data);
		exit;
	}
}

require_once 'tpl.header.php';
require_once 'tpl.database.php';
require_once 'tpl.table.php';

?>
<style>
.form {
	position: relative;
}
.form .favorite {
	position: absolute;
	top: 0;
	right: 0;
}
.form .favorite button {
	padding: 5px 12px;
}

.pre td,
.pre th {
	text-align: left;
	font-family: monospace;
	font-size: 13px;
	white-space: pre;
	color: #444;
}
.pre td.nil {
	color: #ddd;
	font-style: italic;
}
</style>

<div class="form">
	<form class="query" action>
		<input type="hidden" name="nocrop" value="<?= (int)$nocrop ?>" />
		<input type="hidden" name="flip" value="<?= (int)$flip ?>" />
		<input type="hidden" name="db" value="<?= html($_db) ?>" />
		<input type="hidden" name="tbl" value="<?= html($_tbl) ?>" />
		<textarea tabindex="1" id="sqlq" name="sql" style="width: 100%; padding-right: 4em; tab-size: 4" rows="4"><?= html($szSql) ?></textarea>
	</form>

	<form class="favorite" method="post" action="favorites.php?db=<?= html($_db) ?>&tbl=<?= html($_tbl) ?>">
		<input type="hidden" name="sql" value="<?= html($szSql) ?>" />
		<button>Fav!</button>
	</form>
</div>

<script src="auto-indent.js"></script>
<script>
var rowser = function() {
	this._rows || (this._rows = this.rows);
	this.rows = this._rows-1;
	while ( this.scrollHeight > this.offsetHeight ) {
		this.rows++;
	}
	this.rows++;
};
var sqlq = document.getElementById('sqlq');
doAutoIndent(sqlq);
sqlq.addEventListener('keydown', function(e) {
	rowser.call(this);
	if (e.keyCode == 13 && e.ctrlKey) {
		e.preventDefault();
		this.form.submit();
	}
});
sqlq.addEventListener('keyup', rowser);
rowser.call(sqlq);
</script>
<?php

if ( $arrContents ) {
	$szCountSql = $szSql;
	$szCountSql = preg_replace('#(limit|offset)\s+\d+(?:\s*,\s*\d+)?#i', '', $szCountSql);
	$total = $db->fetch_one('SELECT COUNT(1) FROM (' . trim($szCountSql) . ')');

	$header = '';
	$header .= count($arrContents) . ' / ' . $total . ' records | ';
	$header .= '<a href="?' . http_build_query(array('nocrop' => (int)!$nocrop) + $_GET) . '">'.( $nocrop ? 'crop' : 'nocrop' ).'</a> | ';
	$header .= '<a href="?' . http_build_query(array('flip' => (int)!$flip) + $_GET) . '">flip</a> | ';
	$header .= '<a href="?' . http_build_query(array('export' => 1) + $_GET) . '">export</a>';

	$cropper = function($value) use ($nocrop) {
		return $nocrop || mb_strlen($value) <= 80 ? $value : mb_substr($value, 0, 78) . '...';
	};

	$encoder = function($value) use ($export) {
		return $export ? (string) $value : ( $value === null ? '<i>NIL</i>' : html($value) );
	};

	echo '<table border="1" cellpadding="6" cellspacing="0">' . "\n";
	echo '<thead>' . "\n";
	echo '<tr><th colspan="' . count($data[0]) . '">' . $header . '</th></tr>' . "\n";
	echo '</thead>' . "\n";
	echo '<tbody class="pre">' . "\n";
	foreach ($data as $i => $row) {
		echo '<tr>';
		foreach ($row as $j => $cell) {
			$tag = $cell['th'] ? 'th' : 'td';
			echo '<' . $tag . '>' . $encoder($cropper($cell['data'])) . '</' . $tag . '>';
		}
		echo '</tr>';
	}
	echo '</tbody>' . "\n";
	echo '</table>' . "\n";
}
else {
	if ( $arrContents === false ) {
		echo '<pre style="padding: 10px; border: solid 2px red; background-color: #eee">' . $db->error . '</pre>';
	}
	else {
		echo '<p>no records returned</p>';
	}
}


