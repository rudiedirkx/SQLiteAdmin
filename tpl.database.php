<fieldset>
	<legend>
		Selected <a href="database.php?db=<?= html($_db) ?>">database</a> |
		<a href="favorites.php?db=<?= html($_db) ?>">favorites</a>
	</legend>
	[<?= html($_db) ?>]: <u><?= html($objDb->path) ?></u>
	(<?= format_size(filesize($objDb->path)) ?>)
	&nbsp;
	(<a href="aliases.php">aliases</a>)
	(access: <?= $g_objUser->isAdmin() ? 'unlimited' : implode(', ', $g_objUser->alias->allowedQueries()) ?>)
</fieldset>

<br />
