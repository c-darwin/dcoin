<?php
if (!defined('DC')) die("!defined('DC')");

if ($_POST['php_path']) {
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."my_table`
			SET `php_path` = '".str_replace('\\', '\\\\', $_POST['php_path'])."'
			");
}

$tpl['php_path'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `php_path`
		FROM `".DB_PREFIX."my_table`
		", 'fetch_one');

if (OS=='WIN'){
	$lng['install_chmod'] = str_ireplace('[dir]', ABSPATH, $lng['install_chmod_win']);
	$lng['install_create_cron'] = $lng['install_create_cron_win'];
}
else {
	$lng['install_chmod'] = str_ireplace('[dir]', ABSPATH, $lng['install_chmod_nix']);
	$lng['install_create_cron'] = $lng['install_create_cron_nix'];
}

require_once( ABSPATH . 'templates/install_step_3.tpl' );

?>