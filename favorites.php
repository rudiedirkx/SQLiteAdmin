<?php

require_once 'inc.config.php';

list($_db) = requireParams('db');
require_once 'inc.database.php';

$conditions = array('user_id' => $g_objUser->id, 'alias_id' => $g_objUser->alias->id);

// CREATE
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
		header('Location: favorites.php?db=' . $_db);
		exit;
	}

	echo $master->error;
	exit;
}

// DELETE
else if ( isset($_POST['del']) ) {
	$where = $master->stringifyConditions($conditions + array('id' => $_POST['del']));
	$master->delete('favorites', $where);
	header('Location: favorites.php?db=' . $_db);
	exit;
}

require_once 'tpl.header.php';
require_once 'tpl.database.php';

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
				<a href="browse.php?db=<?= $_db ?>&tbl=<?= $fav['tbl'] ?>&sql=<?= html(urlencode($fav['query'])) ?>">Exec</a>
			</td>
			<td class="query"><?= html($fav['query']) ?></td>
			<td><?= date('Y-m-d H:i', $fav['created_on']) ?></td>
			<td>
				<form method="post">
					<input type="hidden" name="del" value="<?= $fav['id'] ?>" />
					<button>del</button>
				</form>
			</td>
		</tr>
	<?endforeach?>
</table>
