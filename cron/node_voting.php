<?php
define( 'DC', true );
/*
 * Если наш miner_id есть среди тех, кто должен скачать фото нового майнера к себе, то качаем
 */

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );
require_once( ABSPATH . 'includes/fns-main.php' );

require_once( ABSPATH . 'phpseclib/Math/BigInteger.php');
require_once( ABSPATH . 'phpseclib/Crypt/Random.php');
require_once( ABSPATH . 'phpseclib/Crypt/Hash.php');
require_once( ABSPATH . 'phpseclib/Crypt/RSA.php');

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

do {
	debug_print("START", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// отметимся в БД, что мы живы.
	upd_deamon_time($db);

	// проверим, не нужно нам выйти, т.к. обновилась версия скрипта
	if (check_deamon_restart($db))
		exit;

	$my_miner_id = get_my_miner_id($db);

	main_lock();
	// берем данные, которые находятся на голосовании нодов
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `".DB_PREFIX."miners_data`.`user_id`,
						 `host`,
						 `face_hash`,
						 `profile_hash`,
						 `photo_block_id`,
						 `photo_max_miner_id`,
						 `miners_keepers`,
						 `id` as vote_id,
						 `miner_id`
			FROM `".DB_PREFIX."votes_miners`
			LEFT JOIN `".DB_PREFIX."miners_data`
					 ON `".DB_PREFIX."votes_miners`.`user_id` = `".DB_PREFIX."miners_data`.`user_id`
			WHERE `cron_checked_time` < ".(time()-CRON_CHECKED_TIME_SEC)." AND
						 `votes_end` = 0 AND
						 `type` = 'node_voting'
			");
	//$copy = false;
	while ($row = $db->fetchArray($res)) {

		// отметимся в БД, что мы живы
		upd_deamon_time($db);

		// проверим, не нужно нам выйти, т.к. обновилась версия скрипта
		if (check_deamon_restart($db))
			exit;

		debug_print($row, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$miners_ids = ParseData::get_miners_keepers($row['photo_block_id'], $row['photo_max_miner_id'], $row['miners_keepers']);
		debug_print($miners_ids, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print('$my_miner_id='.$my_miner_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// нет ли нас среди тех, кто должен скачать фото к себе и проголосовать
		if (array_key_exists($my_miner_id, $miners_ids)) {

			// За 1 вызов скрипта делаем только 1 операцию копирования фоток, т.к. работаем по крону
			//$copy = true;

			$my_user_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `user_id`
						FROM `".DB_PREFIX."my_table`
						", 'fetch_one');

			$node_private_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `private_key`
						FROM `".DB_PREFIX."my_node_keys`
						WHERE `block_id` = (SELECT max(`block_id`) FROM `".DB_PREFIX."my_node_keys` )
						", 'fetch_one');

			debug_print('копируем фото  к себе', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			// копируем фото  к себе
			$profile_path = ABSPATH."public/profile_{$row['user_id']}.jpg";
			$face_path = ABSPATH."public/face_{$row['user_id']}.jpg";
			ParseData::download_and_save( "{$row['host']}/public/user_profile.jpg", $profile_path);
			ParseData::download_and_save( "{$row['host']}/public/user_face.jpg", $face_path);

			// хэши скопированных фото
			$profile_hash = hash('sha256', hash_file('sha256', $profile_path));
			$face_hash = hash('sha256', hash_file('sha256', $face_path));

			debug_print('$profile_hash='.$profile_hash."\n".'$face_hash='.$face_hash, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			// проверяем хэш. Если сходится, то голосуем за, если нет - против
			if ( $profile_hash == $row['profile_hash'] && $face_hash == $row['face_hash']  ) {
				$vote = 1;
				debug_print('VOTE = YES', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			}
			else {
				$vote = 0; // если хэш не сходится, то удаляем только что скаченное фото
				unlink($profile_path);
				unlink($face_path);
				debug_print('VOTE = NO', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			}

			$time = time();

			// подписываем нашим нод-ключем данные транзакции
			$data_for_sign = ParseData::findType('votes_node_new_miner').",{$time},{$my_user_id},{$row['vote_id']},{$vote}";
			$rsa = new Crypt_RSA();
			$rsa->loadKey($node_private_key);
			$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
			$signature = $rsa->sign($data_for_sign);
			debug_print('$data_for_sign='.$data_for_sign, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			// создаем новую транзакцию - подверждение, что фото скопировано и проверено.
			$data = dec_binary (30, 1) .
				dec_binary ($time, 4) .
				ParseData::encode_length_plus_data($my_user_id) .
				ParseData::encode_length_plus_data($row['vote_id']) .
				ParseData::encode_length_plus_data($vote) .
				ParseData::encode_length_plus_data($signature);

			insert_tx($data, $db);

		}

		// отмечаем, чтобы больше не брать эту строку
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."votes_miners`
				SET `cron_checked_time` = ".time()."
				WHERE `id` = {$row['vote_id']}
				");

	}
	main_unlock();

	sleep(1);

} while (true);

?>