<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');
$user_id = intval($_SESSION['user_id']);

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$email = filter_var($_REQUEST['email'], FILTER_SANITIZE_EMAIL);
if(!filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL))
	die(json_encode(array('error'=>'incorrect email')));

if(empty($_POST['e']) || empty($_POST['n']))
	die(json_encode(array('error'=>$lng['pool_error'])));

$lang = get_lang();
require_once( ABSPATH . 'lang/'.$lang.'.php' );

$community = get_community_users($db);
// если мест в пуле нет, то просто запишем юзера в очередь
$pool_max_users = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `pool_max_users`
			FROM `".DB_PREFIX."config`
			", 'fetch_one' );
if (sizeof($community) >= $pool_max_users) {
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,  "
			INSERT IGNORE INTO `".DB_PREFIX."pool_waiting_list` (
				`email`,
				`time`,
				`user_id`
			)
			VALUES (
					'{$email}',
					".time().",
					{$user_id}
			)");
	die(json_encode(array('error'=>$lng['pool_is_full'])));
}

// регистрируем юзера в пуле
// вначале убедитмся, что такой user_id у нас уже не зареган
$community = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `user_id`
		FROM `".DB_PREFIX."community`
		WHERE `user_id` = {$user_id}
		", 'fetch_one' );
if ($community) {
	die(json_encode(array('error'=>$lng['pool_user_id_is_busy'])));
}

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,  "
		INSERT IGNORE INTO `".DB_PREFIX."community` (
			`user_id`
		)
		VALUES (
			{$user_id}
		)");

$rsa = new Crypt_RSA();
$key = array();
$key['e'] = new Math_BigInteger($_POST['e'], 16);
$key['n'] = new Math_BigInteger($_POST['n'], 16);
$rsa->setPublicKey($key, CRYPT_RSA_PUBLIC_FORMAT_RAW);
$PublicKey = clear_public_key($rsa->getPublicKey());

// если таблы my для этого юзера уже есть в БД, то они перезапишутся.
$mysqli_link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
$db_name = DB_NAME;
$prefix = DB_PREFIX;
include ABSPATH.'schema.php';
mysqli_query($mysqli_link, 'SET NAMES "utf8" ');
pool_add_users ("{$user_id};{$PublicKey}\n", $my_queries, $mysqli_link, DB_PREFIX, false);

define('MY_PREFIX', $user_id.'_');
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		UPDATE `".DB_PREFIX.MY_PREFIX."my_table`
		SET `email` = '{$email}'
		");
print json_encode(array('success'=>$lng['pool_sign_up_success']));
?>