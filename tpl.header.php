<!doctype html>
<html>

<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta charset="utf-8" />
	<link rel="stylesheet" href="base.css" />
	<link rel="icon" href="favicon.png" />
	<title>SQLite Admin</title>
	<style>
	input, select, textarea {
		box-sizing: border-box;
	}
	</style>
</head>

<body>

<?php if ( logincheck() ): ?>
	<div style="padding-bottom: 10px">
		Logged in as: <b><?= html($_SESSION[S_NAME]['user']) ?></b>
		|
		<a href="login.php?logout=1">logout</a>
	</div>
<?php endif; ?>
