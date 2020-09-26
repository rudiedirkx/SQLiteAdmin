<?php

require_once('inc.config.php');

if ( !empty($_GET['logout']) ) {
	$_SESSION[S_NAME] = array();
	header('Location: ./');
	exit;
}

else if ( isset($_POST['username'], $_POST['password'], $_POST['goto']) ) {
	setcookie('sa_user', $_POST['username']);

	$u = $master->select_one('users', 'id', "username = '" . $master->escape($_POST['username']) . "' AND password = '" . sha1($_POST['password']) . "'");
	if ( $u ) {
		$_SESSION[S_NAME] = array(
			'user_id' => (int)$u,
			'logouttime' => time()+86400,
		);

		header('Location: '.( $_POST['goto'] ? $_POST['goto'] : 'aliases.php'));
		exit;
	}

	header('Location: login.php?wrong=1');
	exit;
}

?>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<form method="post" action>
	<input type="hidden" name="goto" value="<?php echo isset($_GET['goto']) ? $_GET['goto'] : ''; ?>" />
	<p>Username: <input name="username" value="<?= @$_COOKIE['sa_user'] ?>" required autofocus /></p>
	<p>Password: <input type="password" name="password" required /></p>
	<p><input type="submit" value="Jack in!" /></p>
</form>
