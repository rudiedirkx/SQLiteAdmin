<?php

require_once('inc.database.php');

// echo '<pre>';
// print_r($objDb);
// print_r($g_objUser);
// exit;

$conditions = array('user_id' => $g_objUser->id, 'alias_id' => $g_objUser->alias->id);

if ( isset($_POST['sql'], $_GET['tbl']) ) {
	$insert = $conditions + array(
		'name' => '',
		'tbl' => $_GET['tbl'],
		'query' => $_POST['sql'],
		'created_on' => time(),
		'is_public' => 0,
	);
	$ok = $master->insert('favorites', $insert);
	if ( $ok ) {
		echo '<meta http-equiv="refresh" content="0; url=favorites.php?db=' . $_GET['db'] . '" />';
		exit;
	}

	echo $master->error;
	exit;
}

else if ( isset($_POST['del']) ) {
	$where = $master->stringifyConditions($conditions + array('id' => $_POST['del']));
	$master->delete('favorites', $where);
	echo '<meta http-equiv="refresh" content="0; url=favorites.php?db=' . $_GET['db'] . '" />';
	exit;
}

$favorites = $master->select('favorites', $master->stringifyConditions($conditions) . ' ORDER BY created_on DESC');

?>
<style>
td.query {
	white-space: pre-wrap;
	font-family: monospace;
}
</style>

<table border="1" cellspacing="0">
	<?foreach ($favorites as $fav):?>
		<tr valign="top">
			<td>
				<a href="browse.php?db=<?= $_GET['db'] ?>&tbl=<?= $fav['tbl'] ?>&sql=<?= htmlspecialchars(urlencode($fav['query'])) ?>">Exec</a>
			</td>
			<td class="query"><?= htmlspecialchars($fav['query']) ?></td>
			<td><?= date('Y-m-d H:i', $fav['created_on']) ?></td>
			<td>
				<form method="post" action>
					<input type="hidden" name="del" value="<?= $fav['id'] ?>" />
					<button>del</button>
				</form>
			</td>
		</tr>
	<?endforeach?>
</table>
