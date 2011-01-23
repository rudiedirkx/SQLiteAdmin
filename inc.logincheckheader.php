
<?php if ( logincheck() ): ?>
<div style="padding-bottom:10px;">Logged in as: <b><?php echo $g_objUser->username; ?></b> | <a href="login.php?logout=1">logout</a></div>
<?php endif; ?>
