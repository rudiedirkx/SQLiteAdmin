<?php

require_once 'inc.config.php';

list($_db) = requireParams('db');
require_once 'inc.database.php';

$conditions = array('alias_id' => $g_alias->id);

// if ( count($_POST) ) {
// 	header('Content-type: text/plain; charset=utf-8');
// 	print_r($_POST);
// 	exit;
// }

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
elseif ( isset($_POST['del']) ) {
	$where = $master->stringifyConditions($conditions + array('id' => $_POST['del']));
	$master->delete('favorites', $where);
	header('Location: favorites.php?db=' . $_db);
	exit;
}

// UPDATE
elseif ( isset($_POST['favorites']) ) {
	foreach ( $_POST['favorites'] as $id => $fav ) {
		$master->update('favorites', ['query' => $fav['query']], ['id' => $id]);
	}

	header('Location: favorites.php?db=' . $_db);
	exit;
}

require_once 'tpl.header.php';
require_once 'tpl.database.php';

$favorites = $master->select('favorites', $master->stringifyConditions($conditions) . ' ORDER BY created_on DESC');

?>
<style>
.query {
	white-space: pre-wrap;
	font-family: monospace;
}
body.query-pre .query {
	white-space: pre;
}
.query strong.table {
	color: green;
}
.query + textarea {
	width: 100%;
	height: 8em;
}
body.query-edit .query,
body:not(.query-edit) textarea,
body:not(.query-edit) .submit {
	display: none;
}
</style>

<form method="post">
	<label>
		<input type="checkbox" onclick="document.body.classList.toggle('query-pre', this.checked)" />
		Real <code>pre</code> SQL
	</label>
	<label>
		<input type="checkbox" onclick="document.body.classList.toggle('query-edit', this.checked)" />
		Edit SQL
	</label>
	<button name="save" value="1" class="submit">Save</button>

	<table border="1" cellspacing="0" width="100%">
		<?foreach ($favorites as $fav):?>
			<tr valign="top">
				<td width="10">
					<a href="browse.php?db=<?= $_db ?>&tbl=<?= $fav['tbl'] ?>&sql=<?= html(urlencode($fav['query'])) ?>">Exec</a>
				</td>
				<td>
					<div class="query"><?= preg_replace_callback("#\\b({$fav['tbl']})\\b#", function($m) {
						return '<strong class="table">' . $m[1] . '</strong>';
					}, html($fav['query'])) ?></div>
					<textarea name="favorites[<?= $fav['id'] ?>][query]"><?= html(trim($fav['query'])) ?></textarea>
				</td>
				<td width="10" nowrap>
					<?= date('Y-m-d', $fav['created_on']) ?><br>
					<?= date('H:i', $fav['created_on']) ?>
				</td>
				<td width="10">
					<button
						type="button"
						name="del"
						value="<?= $fav['id'] ?>"
						onclick="return confirm('Are you sure you want to DELETE this fav?')"
					>del</button>
				</td>
			</tr>
		<?endforeach?>
	</table>
	<p class="submit"><button name="save" value="1">Save</button></p>
</form>
