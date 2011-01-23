<?php

require_once('inc.config.php');

if ( !empty($_GET['logout']) ) {
	$_SESSION[S_NAME] = array();
	header('Location: ./');
	exit;
}

else if ( isset($_GET['username'], $_GET['password'], $_GET['goto']) ) {
	if ( false !== ($u=$master->select_one('users', 'id', "username = '".$master->escape($_GET['username'])."' AND password = '".$master->escape($_GET['password'])."'")) ) {
		$_SESSION[S_NAME] = array(
			'user_id' => (int)$u,
			'logouttime' => time()+3600
		);
		header('Location: '.( $_GET['goto'] ? $_GET['goto'] : 'aliases.php'));
	}
	else {
		header('Location: login.php?wrong=1');
	}
	exit;
}

?>
<form method="get" action="">

	<input type="hidden" name="goto" value="<?php echo isset($_GET['goto']) ? $_GET['goto'] : ''; ?>" />

	<p><input type="text" name="username" autofocus /> / <input type="password" name="password" /></p>

	<p><input type="submit" value="Jack in!" /></p>

</form>
