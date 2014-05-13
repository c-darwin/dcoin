<?php

/*
 * Генерим код, который юзер должен подписать своим ключем, доказав тем самым, что именно он хочет войти в аккаунт
 * */

define( 'DC', TRUE);
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$login_code = strval(rand(9999999, getrandmax()));
$ini_array = parse_ini_file(ABSPATH . "config.ini", true);
if ($ini_array['main']['sign_hash'] == 'ip')
	$hash = md5($_SERVER['REMOTE_ADDR']);
else
	$hash = md5($_SERVER['HTTP_USER_AGENT']);

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		INSERT INTO  `".DB_PREFIX."authorization` (
			`hash`,
			`data`
		)
		VALUES (
			0x{$hash},
			'{$login_code}'
		)
		ON DUPLICATE KEY UPDATE `data` = '{$login_code}'
		");
echo json_encode($login_code);

	
?>