<?php
if (!defined('DC')) die("!defined('DC')");

if ($_POST['php_path']) {

	$php_path = clear_comment($_POST['php_path'], $db);
	$php_path = str_replace('\\', '\\\\', $php_path);

	$exists_config = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."config`
			", 'num_rows');
	if ($exists_config){
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE`".DB_PREFIX."config`
				SET `php_path` = '{$php_path}'
				");
	}
	else {
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			INSERT INTO `".DB_PREFIX."config` (
				`php_path`
			)
			VALUES (
				'{$php_path}'
			)");
	}

}

$tpl['php_path'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `php_path`
		FROM `".DB_PREFIX."config`
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