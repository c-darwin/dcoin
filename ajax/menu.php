<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('');
$user_id = $_SESSION['user_id'];
	
define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
if (file_exists(ABSPATH . 'db_config.php')) {
	require_once( ABSPATH . 'db_config.php' );
	require_once( ABSPATH . 'includes/autoload.php' );
	$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
}

$lang = get_lang();

if ( isset($db) && get_community_users($db) )
	define('COMMUNITY', true);

if ( defined('COMMUNITY') ) {
	$pool_admin_user_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `pool_admin_user_id`
				FROM `".DB_PREFIX."config`
				", 'fetch_one' );
	if ( (int)$_SESSION['user_id'] === (int)$pool_admin_user_id ) {
		define('POOL_ADMIN', true);
	}
}

if ($user_id>0 && $user_id!='wait') {
	$tpl = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `name`,
						 `avatar`
			FROM `".DB_PREFIX."users`
			WHERE `user_id`= {$user_id}
			", 'fetch_array');
}
if (!@$tpl['name'])
	$tpl['name'] = 'Noname';
if (!@$tpl['avatar'])
	$tpl['avatar'] = 'img/noavatar.png';

require_once( ABSPATH . 'lang/'.$lang.'.php' );
$tpl['ver'] = file_get_contents(ABSPATH.'version');
require_once( ABSPATH . 'templates/menu2.tpl' );

?>