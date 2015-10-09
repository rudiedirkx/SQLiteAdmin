<?php

require_once('inc.table.php');

$iPage = isset($_GET['page']) && 0 <= (int)$_GET['page'] ? (int)$_GET['page'] : 0;
$iLimit = 200;
$iStart = $iPage * $iLimit;

$szSql = 'SELECT * FROM "'.$szTable.'" WHERE 1 LIMIT '.$iStart.', '.$iLimit;
if ( !empty($_GET['sql']) ) {
	$szSql = $_GET['sql'];
}

//$arrTable = $db->structure($szTable);

$nocrop = (int)!empty($_GET['nocrop']);
$flip = (int)!empty($_GET['flip']);

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
		<input type="hidden" name="nocrop" value="<?= $nocrop ?>" />
		<input type="hidden" name="flip" value="<?= $flip ?>" />
		<input type="hidden" name="db" value="<?= $_GET['db'] ?>" />
		<input type="hidden" name="tbl" value="<?= $_GET['tbl'] ?>" />
		<textarea tabindex="1" id="sqlq" name="sql" style="width: 100%" rows="4"><?= html($szSql) ?></textarea>
	</form>

	<form class="favorite" method="post" action="favorites.php?db=<?= $_GET['db'] ?>&tbl=<?= $_GET['tbl'] ?>">
		<input type="hidden" name="sql" value="<?= html($szSql) ?>" />
		<button>Fav!</button>
	</form>
</div>

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

$arrContents = $db->fetch($szSql);
if ( $arrContents ) {
	$szCountSql = $szSql;
	$szCountSql = preg_replace('#(limit|offset)\s+\d+(?:\s*,\s*\d+)?#i', '', $szCountSql);
	$total = $db->fetch_one('SELECT COUNT(1) FROM (' . trim($szCountSql) . ')');

	$header = '';
	$header .= count($arrContents) . ' / ' . $total . ' records | ';
	$header .= '<a href="?' . http_build_query(array('nocrop' => (int)!$nocrop) + $_GET) . '">'.( $nocrop ? 'crop' : 'nocrop' ).'</a> | ';
	$header .= '<a href="?' . http_build_query(array('flip' => (int)!$flip) + $_GET) . '">flip</a>';

	echo '<table border="1" cellpadding="6" cellspacing="0">' . "\n";
	echo '<thead>' . "\n";
	echo '<tr><th colspan="' . ( $flip ? 2 : count($arrContents[0]) ) . '">' . $header . '</th></tr>' . "\n";
	if ( !$flip ) {
		echo '<tr class="pre">';
		foreach ( $arrContents[0] AS $k => $v ) {
			echo '<th>' . html($k) . '</th>' . "\n";
		}
		echo '</tr>' . "\n";
	}
	echo '</thead>' . "\n";
	if ( !$flip ) {
		echo '<tbody class="pre">' . "\n";
	}
	foreach ( $arrContents AS $i => $r ) {
		if ( $flip ) {
			echo '<tbody class="pre">' . "\n";
			echo '<tr><th></th><th># ' . ($i+1) . '</th></tr>' . "\n";
		}
		else {
			echo '<tr>' . "\n";
		}
		foreach ( $r AS $k => $v ) {
			if ( $flip ) {
				echo '<tr>' . "\n";
				echo '<th>' . html($k) . '</th>' . "\n";
			}
			if ( $v === null ) {
				echo '<td class="nil">NIL</td>' . "\n";
			}
			else {
				echo '<td>';
				echo !$nocrop && 80 < strlen($v) ? html(substr($v, 0, 78)).'...' : html($v);
				echo '</td>' . "\n";
			}
			if ( $flip ) {
				echo '</tr>' . "\n";
			}
		}
		if ( $flip ) {
			echo '</tbody>' . "\n";
		}
		else {
			echo '</tr>' . "\n";
		}
	}
	if ( !$flip ) {
		echo '</tbody>' . "\n";
	}
	echo '</table>'."\n";
}
else {
	if ( $arrContents === false ) {
		echo '<pre style="padding: 10px; border: solid 2px red; background-color: #eee">' . $db->error . '</pre>';
	}
	else {
		echo '<p>no records returned</p>';
	}
}


