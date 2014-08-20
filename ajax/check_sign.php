<?php

define( 'DC', TRUE);
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );
require_once( ABSPATH . 'phpseclib/Math/BigInteger.php');
require_once( ABSPATH . 'phpseclib/Crypt/Random.php');
require_once( ABSPATH . 'phpseclib/Crypt/Hash.php');
require_once( ABSPATH . 'phpseclib/Crypt/RSA.php');
require_once( ABSPATH . 'phpseclib/Crypt/AES.php');

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!check_input_data($_POST['sign'], 'hex_sign'))
	json_encode(array('result'=>'bad hex_sign'));
if ( !preg_match ("/^[0-9a-z]{1,2048}$/D", $_POST['e']) )
	json_encode(array('result'=>'bad e'));
if ( !preg_match ("/^[0-9a-z]{1,2048}$/D", $_POST['n']) )
	json_encode(array('result'=>'bad n'));

$sign = hextobin($_POST['sign']);

$community = get_community_users($db);
$result = 0;
if ($community) {

	// в цикле проверяем, кому подойдет присланная подпись
	for ($i=0; $i<sizeof($community); $i++) {

		$my_prefix = $community[$i].'_';

		// получим открытый ключ юзера
		$public_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `public_key`
				FROM `".DB_PREFIX."{$my_prefix}my_keys`
				WHERE `block_id` = (SELECT max(`block_id`) FROM `".DB_PREFIX."{$my_prefix}my_keys` )
				", 'fetch_one' );

		$ini_array = parse_ini_file(ABSPATH . "config.ini", true);
		//print ($ini_array['main']['sign_hash'])."\n";
		if ($ini_array['main']['sign_hash'] == 'ip')
			$hash = md5($_SERVER['REMOTE_ADDR']);
		else
			$hash = md5($_SERVER['HTTP_USER_AGENT']);
		//print $hash."\n";

		$for_sign = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `data`
				FROM `".DB_PREFIX."authorization`
				WHERE `hash` = 0x{$hash}
				", 'fetch_one' );
		$error = ParseData::checkSign($public_key, $for_sign, $sign, true);

		// нашли совпадение
		if (!$error) {

			session_start();

			define('MY_PREFIX', $my_prefix);
			$my_user_id = $community[$i];

			unset($_SESSION['restricted']); // убираем ограниченный режим

			$_SESSION['user_id'] = $my_user_id;
			if (!$_SESSION['user_id'])
				$_SESSION['user_id'] = 'wait';

			if ($my_user_id==1)
				$_SESSION['ADMIN'] = 1;

			print json_encode(array('result'=>1));
			exit;
		}
	}

	// если дошли досюда, значит ни один ключ не подошел и даем возможность войти в ограниченном режиме

/*
	$pool_max_users = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `pool_max_users`
			FROM `".DB_PREFIX."config`
			", 'fetch_one' );
	if (sizeof($community)>=$pool_max_users)
		die(json_encode(array('result'=>'not_available')));
*/
	$rsa = new Crypt_RSA();
	$key = array();
	$key['e'] = new Math_BigInteger($_POST['e'], 16);
	$key['n'] = new Math_BigInteger($_POST['n'], 16);
	$rsa->setPublicKey($key, CRYPT_RSA_PUBLIC_FORMAT_RAW);
	$PublicKey = clear_public_key($rsa->getPublicKey());
	$PublicKey_bin = hextobin($PublicKey);
	debug_print('>$PublicKey='.$PublicKey, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	$user_id =$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `user_id`
				FROM `".DB_PREFIX."users`
				WHERE `public_key_0` = 0x{$PublicKey}
				", 'fetch_one' );
	if ($user_id) {
		$for_sign = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `data`
				FROM `".DB_PREFIX."authorization`
				WHERE `hash` = 0x{$hash}
				", 'fetch_one' );
		$error = ParseData::checkSign($PublicKey_bin, $for_sign, $sign, true);
		if (!$error) {
			// если юзер смог подписать наш хэш, значит у него актуальный праймари ключ
			// и если у нас еще есть места, то создаем для него таблицы и даем войти в его новый акк
			/*
			$mysqli_link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
			$db_name = DB_NAME;
			$prefix = DB_PREFIX;
			include ABSPATH.'schema.php';
			mysqli_query($mysqli_link, 'SET NAMES "utf8" ');
			pool_add_users ("{$user_id};{$PublicKey}\n", $my_queries, $mysqli_link, DB_PREFIX, false);
			*/
			session_start();
			$_SESSION['user_id'] = $user_id;
			$_SESSION['restricted'] = 1;
			print json_encode(array('result'=>1));
			exit;
		}
	}
}
else {
	// получим открытый ключ юзера
	$public_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `public_key`
			FROM `".DB_PREFIX."my_keys`
			WHERE `block_id` = (SELECT max(`block_id`) FROM `".DB_PREFIX."my_keys` )
			", 'fetch_one' );

	$ini_array = parse_ini_file(ABSPATH . "config.ini", true);
	if ($ini_array['main']['sign_hash'] == 'ip')
		$hash = md5($_SERVER['REMOTE_ADDR']);
	else
		$hash = md5($_SERVER['HTTP_USER_AGENT']);
	$for_sign =$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `data`
				FROM `".DB_PREFIX."authorization`
				WHERE `hash` = 0x{$hash}
				", 'fetch_one' );

	$error = ParseData::checkSign($public_key, $for_sign, $sign, true);
	if ($error)
		$result = 0;
	else {
		session_start();

		define('MY_PREFIX', '');
		$my_user_id = get_my_user_id($db);

		unset($_SESSION['restricted']); // убираем ограниченный режим

		$_SESSION['user_id'] = $my_user_id;
		if (!$_SESSION['user_id'])
			$_SESSION['user_id'] = 'wait';

		if ($my_user_id==1)
			$_SESSION['ADMIN'] = 1;

		print json_encode(array('result'=>1));
		exit;
	}
}

print json_encode(array('result'=>0));

?>