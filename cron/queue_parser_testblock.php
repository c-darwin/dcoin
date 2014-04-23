<?php

/*
 * Парсим и разносим данные из queue_testblock
 * */

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'includes/errors.php' );
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

do {

	debug_print("START", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// отметимся в БД, что мы живы.
	upd_deamon_time($db);

	// проверим, не нужно нам выйти, т.к. обновилась версия скрипта
	if (check_deamon_restart($db))
		exit;

	testblock_lock();

	$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."queue_testblock`
			ORDER BY `head_hash` ASC
			LIMIT 1
			", 'fetch_array');
	if (!$data) {
		testblock_unlock();
		sleep(1);
		continue;
	}
	debug_print($data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	$new_block = $data['data'];

	debug_print('strlen $new_block='.strlen($new_block), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print('hex $new_block='.bin2hex($new_block), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	list(, $new_header_hash) = unpack( "H*", $data['head_hash'] );
	$tx = ParseData::delete_header($new_block);

	debug_print('strlen $tx='.strlen($tx), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print('hex $tx='.bin2hex($tx), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// сразу можно удалять данные из таблы-очереди
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			DELETE FROM `".DB_PREFIX."queue_testblock`
			WHERE `head_hash` = 0x{$new_header_hash}
			");
	////debug_print($db->printsql(), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// прежде всего нужно проверить, а нет ли в этом блоке ошибок с несовметимыми тр-ми
	// при полной проверке, а не только фронтальной проблем с несовместимыми тр-ми не будет, т.к. там даные сразу пишутся в таблицы
	// а тут у нас данные пишутся только в log_time_
	// и сами тр-ии пишем в отдельную таблу
	if ($tx) {
		do {

			debug_print('$tx='.$tx, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			debug_print('$tx hex='.bin2hex($tx), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			$tx_size = ParseData::decode_length($tx);
			// отчекрыжим одну транзакцию от списка транзакций
			$tx_binary_data = ParseData::string_shift ( $tx, $tx_size ) ;

			// проверим, нет ли несовместимых тр-ий
			list($fatal_error, $wait_error) = clear_incompatible_tx($binary_tx, $type, $user_id, $to_user_id, $db, false);

			if ($fatal_error || $wait_error) {
				debug_print('[incompatible_tx] continue', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				testblock_unlock();
				sleep(1);
				continue 2;
			}
		} while ($tx);
	}

	// откатим тр-ии тестблока, но не удаляя их, т.к. далее еще можем их вернуть
	rollback_transactions_testblock($db);

	debug_print("ParseDataRollbackFront OK", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	//ob_save();

	// проверим блок, который получился с данными, которые прислал другой нод
	$parsedata = new ParseData($new_block, $db);
	$error = $parsedata->ParseData_gate();

	debug_print("ParseData_gate OK", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	if ($error) {

		unset($parsedata);
		debug_print('------------------[error]'.$error.'-------------------', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// т.к. мы откатили наши тр-ии из transactions_testblock, то теперь нужно обработать их по новой
		// получим наши транзакции в 1 бинарнике, просто для удобства
		$my_testblock['block_body'] = '';
		$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `data`
				FROM `".DB_PREFIX."transactions_testblock`
				ORDER BY `id` ASC
				");
		while ( $row = $db->fetchArray( $res ) ) {
			$my_testblock['block_body'] .= ParseData::encode_length_plus_data( $row['data'] );
		}

		if ($my_testblock['block_body']) {
			$parsedata = new ParseData(dec_binary (0, 1) . $my_testblock['block_body'], $db);
			$parsedata->ParseData_gate(true);
			unset($parsedata);
		}

		/*if (substr_count($error, '[limit_requests]')>0) {
			debug_print('----------------[error]'.$error.'-------------------', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			// если есть ошибки, то откатим фронтальные измненения от этого блока
			$parsedata = new ParseData($tx, $db);
			$parsedata->ParseDataRollbackFront();
		}
		else {
			debug_print('error wo rollback----------------[error]'.$error.'-------------------', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		}*/
	}
	else {

		//main_unlock();

		// т.к. testblock_generator.php пишет в таблы testblock и transactions_testblock нужно локать эти таблы
		//testblock_lock();

		// наши тр-ии уже не актуальны, т.к. мы их откатили
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				TRUNCATE TABLE `".DB_PREFIX."transactions_testblock`
				" );

		// если всё нормально, то пишем в таблу testblock новые тр-ии и новые данные по юзеру их сгенерившему
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."testblock`
				SET  `time` = {$parsedata->block_data['time']},
						`user_id` = {$parsedata->block_data['user_id']},
						`header_hash` = 0x{$new_header_hash},
						`signature` = 0x".bin2hex($parsedata->block_data['sign']).",
						`mrkl_root` = 0x{$parsedata->mrkl_root}
				");

		////debug_print($db->printsql()."\nAffectedRows=".$db->getAffectedRows() , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// и сами тр-ии пишем в отдельную таблу
		if ($tx) {
			do {

				debug_print('$tx='.$tx, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				debug_print('$tx hex='.bin2hex($tx), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

				$tx_size = ParseData::decode_length($tx);
				// отчекрыжим одну транзакцию от списка транзакций
				$tx_binary_data = ParseData::string_shift ( $tx, $tx_size ) ;

				// получим тип тр-ии и юзера
				// $type, $user_id, $to_user_id точно валидные, т.к. прошли фронт.проверку выше
				list($type, $user_id, $to_user_id) = get_tx_type_and_user_id($tx_binary_data);

				$md5 = md5($tx_binary_data);
				list(, $data_hex ) = unpack( "H*",   $tx_binary_data);
				$file = save_tmp_644 ('FTT', "{$md5}\t{$data_hex}\t{$type}\t{$user_id}\t{$to_user_id}");
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						LOAD DATA LOCAL INFILE  '{$file}'
						REPLACE INTO TABLE `".DB_PREFIX."transactions_testblock`
						FIELDS TERMINATED BY '\t'
						(@hash, @data, `type`, `user_id`, `third_var`)
						SET `hash` = UNHEX(@hash),
							   `data` = UNHEX(@data)
						");
				unlink($file);
				////debug_print($db->printsql()."\nAffectedRows=".$db->getAffectedRows() , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			} while ($tx);
		}

		// удаляем всё, где хэш больше нашего
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."queue_testblock`
				WHERE `head_hash` > 0x{$new_header_hash}
		");
		////debug_print($db->printsql()."\nAffectedRows=".$db->getAffectedRows() , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		#############################################

		// возможно нужно откатить и тр-ии с verified=1 и used=0 из transactions
		// т.к. в transactions может быть тр-ия на удаление банкноты
		// и в transactions_testblock только что была залита такая же тр-ия
		// выходит, что блок, который будет сгенерен на основе transactions будет ошибочным
		// или при откате transactions будет сделан вычет из log_time_....
		// и выйдет что попавшая в блок тр-я из transactions_testblock попала минуя запись  log_time_....

		############################################
		rollback_transactions($db);


	}

	unset($parsedata);
	testblock_unlock();

	sleep(1);

} while (true);

?>