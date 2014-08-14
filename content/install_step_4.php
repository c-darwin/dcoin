<?php
if (!defined('DC')) die("!defined('DC')");


if ($_SESSION['install_progress'] < 3)
	die('access denied');

// проверим, не попытка ли это обойти предыдущие шаги
$progress = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `progress`
			FROM `".DB_PREFIX."install`
			", 'fetch_one');
if ($progress < 3)
	die('$progress error');


if (!get_community_users($db)) {
	$_SESSION['install_progress'] = 4;
	require_once( ABSPATH . 'templates/install_step_4.tpl' );
}
else {
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		UPDATE `".DB_PREFIX."install`
		SET`progress` = 'complete'
		");

	$_SESSION['install_progress'] = 6;
	require_once( ABSPATH . 'templates/install_step_6.tpl' );
}
?>