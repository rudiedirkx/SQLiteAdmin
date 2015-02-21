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

tbody.pre td {
	font-family: Courier New;
	font-size: 13px;
	white-space: pre;
	color: #444;
}
tbody.pre td.nil {
	color: #ddd;
	font-style: italic;
}
</style>

<div class="form">
	<form class="query" action>
		<input type="hidden" name="nocrop" value="<?= $nocrop ?>" />
		<input type="hidden" name="db" value="<?= $_GET['db'] ?>" />
		<input type="hidden" name="tbl" value="<?= $_GET['tbl'] ?>" />
		<textarea tabindex="1" id="sqlq" name="sql" style="width: 100%" rows="4"><?= htmlspecialchars($szSql) ?></textarea>
	</form>

	<form class="favorite" method="post" action="favorites.php?db=<?= $_GET['db'] ?>&tbl=<?= $_GET['tbl'] ?>">
		<input type="hidden" name="sql" value="<?= htmlspecialchars($szSql) ?>" />
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
	$szCountSql = preg_replace('#LIMIT\s\d+,\s*\d+\s*$#', '', $szCountSql);
	$szCountSql = preg_replace('#LIMIT\s\d+(?:\s+OFFSET\s+\d+)?\s*$#', '', $szCountSql);
	$total = $db->fetch_one('SELECT COUNT(1) FROM (' . $szCountSql . ')');

	$_GET['nocrop'] = (int)!$nocrop;
	$qs = http_build_query($_GET);

	echo '<table border="1" cellpadding="4" cellspacing="2">';
	echo '<thead>';
	echo '<tr><th colspan="' . count($arrContents[0]) . '">' . count($arrContents) . ' / ' . $total . ' records | <a href="?'.$qs.'">'.( $nocrop ? 'crop' : 'nocrop' ).'</a></th></tr>';
	echo '<tr>';
	foreach ( $arrContents[0] AS $k => $v ) {
		echo '<th>' . $k . '</th>';
	}
	echo '</tr>';
	echo '</thead>';
	echo '<tbody class="pre">';
	foreach ( $arrContents AS $k => $r ) {
		echo '<tr>';
		foreach ( $r AS $k => $v ) {
			if ( $v === null ) {
				echo '<td class="nil">NIL</td>';
			}
			else {
				echo '<td>';
				echo !$nocrop && 80 < strlen($v) ? htmlspecialchars(substr($v, 0, 78)).'...' : htmlspecialchars($v);
				echo '</td>';
			}
		}
		echo '</tr>';
	}
	echo '</tbody>';
	echo '</table>'."\n";
}
else {
	if ( false === $arrContents ) {
		echo '<p>'.$db->error.'</p>';
	}
	else {
		echo '<p>no records returned</p>';
	}
}


