<fieldset>
	<legend>
		Selected <a href="database.php?db=<?= html($_db) ?>">database</a> |
		<a href="favorites.php?db=<?= html($_db) ?>">favorites</a>
	</legend>
	[<?= html($_db) ?>]: <u><?= html($g_alias->path) ?></u>
	(<?= format_size(filesize($g_alias->path)) ?>)
	(<a href="aliases.php">aliases</a>)
</fieldset>

<br />
