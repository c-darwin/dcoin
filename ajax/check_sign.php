<?php

define( 'DC', TRUE);
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!check_input_data($_POST['sign'], 'hex_sign'))
	json_encode(array('result'=>'bad hex_sign'));
if ( !preg_match ("/^[0-9a-z]{1,2048}$/D", $_POST['e']) )
	json_encode(array('result'=>'bad e'));
if ( !preg_match ("/^[0-9a-z]{1,2048}$/D", $_POST['n']) )
	json_encode(array('result'=>'bad n'));

$sign = hextobin($_POST['sign']);

$tables_array = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SHOW TABLES
			", 'array');

$community = get_community_users($db);
$result = 0;
if ($community) {

	// в цикле проверяем, кому подойдет присланная подпись
	for ($i=0; $i<sizeof($community); $i++) {

		$my_prefix = $community[$i].'_';

		if (!in_array("{$my_prefix}my_keys", $tables_array))
			continue;

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
			$hash = md5($_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']);
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
			else
				$_SESSION['public_key'] = get_user_public_key2($my_user_id);

			if ($my_user_id==get_admin_user_id($db))
				$_SESSION['ADMIN'] = 1;

			/*// для авто-выбрасывания
			if (check_change_key($my_user_id) > 0)
				$_SESSION['key_changed'] = 1;
			else
				$_SESSION['key_changed'] = 0;*/

			print json_encode(array('result'=>1));
			exit;
		}
	}

	// если дошли досюда, значит ни один ключ не подошел и даем возможность войти в ограниченном режиме

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
			session_start();
			$_SESSION['user_id'] = $user_id;
			$_SESSION['public_key'] = get_user_public_key2($user_id);

			// возможно в табле my_keys старые данные, но если эта табла есть, то нужно добавить туда ключ
			if (in_array("{$user_id}_my_keys", $tables_array)) {
				$cur_block_id = get_block_id($db);
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX.$user_id."_my_keys` (
					`public_key`,
					`status`,
					`block_id`
				)
				VALUES (
					0x{$PublicKey},
					'approved',
					{$cur_block_id}
				)");
				unset($_SESSION['restricted']);
			}
			else {
				$_SESSION['restricted'] = 1;
			}

			/*if (check_change_key($user_id) > 0)
				$_SESSION['key_changed'] = 1;
			else
				$_SESSION['key_changed'] = 0;*/
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

	// Если ключ еще не успели установить
	if (!$public_key) {

		// пока не собрана цепочка блоков не даем ввести ключ
		$data = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
				SELECT `block_id`,
							 `time`
				FROM `" . DB_PREFIX . "info_block`
				", 'fetch_array');
		if ( (time()-$data['time']) < 3600*2 ) {

			$rsa = new Crypt_RSA();
			$key = array();
			$key['e'] = new Math_BigInteger($_POST['e'], 16);
			$key['n'] = new Math_BigInteger($_POST['n'], 16);
			$rsa->setPublicKey($key, CRYPT_RSA_PUBLIC_FORMAT_RAW);
			$PublicKey = clear_public_key($rsa->getPublicKey());
			// проверим, есть ли такой ключ
			$user_id =$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `user_id`
				FROM `".DB_PREFIX."users`
				WHERE `public_key_0` = 0x{$PublicKey}
				", 'fetch_one' );
			if ($user_id) {
				$db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
						INSERT INTO `" . DB_PREFIX . "my_keys`(
							`public_key`,
							`status`
						)
						VALUES (
							0x{$PublicKey},
							'approved'
						)");
				$db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
						UPDATE `" . DB_PREFIX . "my_table`
						SET `user_id`={$user_id},
							   `status` = 'user'
						");
			}
			else {
				$error = 1;
			}
		}
		else {
			$error = 1;
		}
	}
	else {
		$ini_array = parse_ini_file(ABSPATH . "config.ini", true);
		if ($ini_array['main']['sign_hash'] == 'ip')
			$hash = md5($_SERVER['REMOTE_ADDR']);
		else
			$hash = md5($_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']);
		$for_sign =$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `data`
					FROM `".DB_PREFIX."authorization`
					WHERE `hash` = 0x{$hash}
					", 'fetch_one' );

		$error = ParseData::checkSign($public_key, $for_sign, $sign, true);
	}
	if ($error) {
		$result = 0;
	}
	else {
		session_start();

		define('MY_PREFIX', '');
		$my_user_id = get_my_user_id($db);

		unset($_SESSION['restricted']); // убираем ограниченный режим

		$_SESSION['user_id'] = $my_user_id;
		if (!$_SESSION['user_id'])
			$_SESSION['user_id'] = 'wait';
		else
			$_SESSION['public_key'] = get_user_public_key2($my_user_id);

		if ($my_user_id==get_admin_user_id($db))
			$_SESSION['ADMIN'] = 1;

		/*if (check_change_key($my_user_id) > 0)
			$_SESSION['key_changed'] = 1;
		else
			$_SESSION['key_changed'] = 0;*/

		print json_encode(array('result'=>1));
		exit;
	}
}

print json_encode(array('result'=>0));

?>