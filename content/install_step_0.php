<?php
if (!defined('DC')) die("!defined('DC')");

if (is_file( ABSPATH . 'db_config.php' ))
	require_once( ABSPATH . 'db_config.php' );
if (defined('DB_HOST') && defined('DB_USER') && defined('DB_NAME'))
	$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if ($db) {
	require_once(ABSPATH . 'cron/daemons_inc.php');
	foreach ($daemons as $script_name) {
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				INSERT INTO `".DB_PREFIX."daemons` (
					`script`,
					`restart`
				)
				VALUES (
					'{$script_name}',
					1
				) ON DUPLICATE KEY UPDATE restart=1
				");
	}
}

unset($_SESSION['user_id']);
unset($_SESSION['restricted']);

require_once( ABSPATH . 'templates/install_step_0.tpl' );

?>