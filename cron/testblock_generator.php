<?php

/**
 * Генерим блок, если наша очередь
 *
 */

define( 'DC', true );
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );

require_once( ABSPATH . 'phpseclib/Math/BigInteger.php');
require_once( ABSPATH . 'phpseclib/Crypt/Random.php');
require_once( ABSPATH . 'phpseclib/Crypt/Hash.php');
require_once( ABSPATH . 'phpseclib/Crypt/RSA.php');
require_once( ABSPATH . 'phpseclib/Crypt/AES.php');
if (!defined('PARSEDATA'))
	die('!PARSEDATA');


$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);



#######################################################
## Нужно проверить, наша ли очередь генерить блок.
#######################################################

/* Например, нод 99 генерит блок 1000 на уровне 1 в 00:00
 * Уже известно, что на уровне 1 для генарции 1001 блока будет нод 55.
 * Нод 55 в 00:35 получил блок 1000, он узнает, что должен генерить блок 1001.
 * Ждет 25 сек и генерит блок 1001.
 * Если нод 55 мертв, то на уровне 2 есть нод 56 и 57, которые получили 1000-й блок
 * в 00:20, значит им нужно ждать 120-20=100 сек.
 * В 02:02 56 и 57 закончат генерить блок 1001, у них будет +5 секунд чтобы определить, чей блок лучше
 * В 02:07 testblock_is_ready.php на 56 и 57 начнет отправлять нодам на других уровнях блок 1001
 * */

// работаем в бесконечном цикле со слипом 0,1 сек
do {

	debug_print("=================================>>> START", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	main_lock();
	//main_lock();
	$block_id = get_block_id($db);
	$new_block_id = $block_id+1;
	// если в testblock уже есть такой блок, то пропускаем
	$testblock_id = get_testblock_id($db);


	debug_print("new_block_id={$new_block_id} // testblock_id={$testblock_id}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// отметимся в БД, что мы живы.
	upd_deamon_time($db);

	// проверим, не нужно ли нам выйти, т.к. обновилась версия скрипта
	if (check_deamon_restart($db)){
		main_unlock();
		exit;
	}

	if (get_my_local_gate_ip($db)) {
		main_unlock();
		sleep(5);
		continue;
	}

	//main_unlock();
	if ($testblock_id==$new_block_id) {
		debug_print("block_id+1 == testblock_id", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		main_unlock();
		//ob_save();
		sleep(1);
		continue;
	}


	$testBlock = new testblock($db, true);


	$my_miner_id = $testBlock->miner_id;
	$my_user_id = $testBlock->user_id;

	debug_print($testBlock->miner_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print($testBlock->user_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	if (!$my_miner_id){
		unset($testBlock);
		debug_print("continue", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		main_unlock();
		sleep(1);
		continue;
	}

	$sleep = $testBlock->getGenSleep();

	$block_id = $testBlock->prev_block['block_id'];
	$prev_head_hash = $testBlock->prev_block['head_hash'];

	print '$testBlock->prev_block=';
	debug_print($testBlock->prev_block, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// сколько прошло сек с момента генерации прошлого блока
	$diff = time() - $testBlock->prev_block['time'];
	debug_print('$diff='.$diff, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// вычитаем уже прошедшее время
	$sleep = ($sleep > $diff) ? ($sleep - $diff) : 0;

	// Если случится откат или придет новый блок, то генератор блоков нужно запускать с начала, т.к. изменится max_miner_id.
	debug_print('$sleep='.$sleep, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	$start_sleep = time();

	unset($testBlock);

	main_unlock();

	for ($i=0; $i<$sleep; $i++) {
		main_lock();
		debug_print("i={$i}\nsleep={$sleep}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$new_head_hash = bin2hex($db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `head_hash` FROM `".DB_PREFIX."info_block` ",
				'fetch_one'));
		debug_print("new_head_hash={$new_head_hash}\nprev_head_hash={$prev_head_hash}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		main_unlock();
		if ($new_head_hash!=$prev_head_hash) {
			debug_print($new_head_hash.'!='.$prev_head_hash, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			continue 2;
		}
		upd_deamon_time ($db);
		// из-за задержек с main_lock время уже прошло и выходим раньше, чем закончится цикл
		if(time() - $start_sleep>$sleep) {
			debug_print('break', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			break;
		}
		sleep(1); // спим 1 сек. общее время = $sleep
	}

	/*
	 *  Закончили спать, теперь генерим блок
	 * Но, всё, что было до main_unlock может стать недействительным, т.е. надо обновить данные
	 * */

	main_lock();

	// т.к. за промежуток в main_unlock и main_lock мог прийти новый блок и sleep_time могло увеличиться, то нужно проверить время
	$testBlock = new testblock($db, true);
	// сколько прошло сек с момента генерации прошлого блока
	$diff = time() - $testBlock->prev_block['time'];
	debug_print('$diff='.$diff, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	// вычитаем уже прошедшее время
	$sleep = ($sleep > $diff) ? ($sleep - $diff) : 0;
	debug_print('$sleep='.$sleep, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	// если нужно доспать, то просто вернемся в начало и доспим нужное время. И на всякий случай убедимся, что блок не изменился
	if ($sleep>0 || $testBlock->prev_block['head_hash']!=$prev_head_hash) {
		debug_print("continue", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		main_unlock();
		unset($testBlock);
		sleep(1);
		continue;
	}

	debug_print('Закончили спать, теперь генерим блок', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$block_id = $testBlock->prev_block['block_id'];
	if (!$block_id) {
		debug_print("continue", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		main_unlock();
		unset($testBlock);
		sleep(1);
		continue;
	}

	$my_user_id = $testBlock->user_id;
	$new_block_id = $block_id + 1;
	$new_block_id_binary = dec_binary( $new_block_id, 4 );
	$user_id_binary = dec_binary( $testBlock->cur_user_id, 5 );
	$level_binary = dec_binary( $testBlock->level, 1 );
	if (get_community_users($db))
		$my_prefix = $testBlock->user_id.'_';
	else
		$my_prefix = '';
	$node_private_key = get_node_private_key($db, $my_prefix);
	$prev_head_hash = $testBlock->prev_block['head_hash'];

	#####################################
	##		 Формируем блок
	#####################################
	print '$new_block_id='.$new_block_id."\n";
	print '$cur_user_id='.$testBlock->cur_user_id."\n";
	if (!$testBlock->cur_user_id) {
		debug_print("continue", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		main_unlock();
		unset($testBlock);
		sleep(1);
		continue;
	}

	debug_print("memory", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);



	if ($testBlock->prev_block['block_id']>=$new_block_id) {
		debug_print("continue", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		main_unlock();
		unset($testBlock);
		//ob_save();
		sleep(1);
		continue;
	}

	// отакатим transactions_testblock
	rollback_transactions_testblock($db, true);

	debug_print("testBlock->prev_block['block_id']={$testBlock->prev_block['block_id']} // new_block_id = {$new_block_id} ", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$time = time();

	// переведем тр-ии в `verified` = 1
	all_tx_parser();

	$transactions = '';
	$mrkl_array = array();
	// берем все данные из очереди. Они уже были проверены ранее, и можно их не проверять, а просто брать
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."transactions`
			WHERE `used`=0 AND
						 `verified` = 1
			");
	$used_transactions = '';
	$max_user_id = 0;

	// т.к. queue_parser_testblock.php пишет в таблы testblock и transactions_testblock нужно локать эти таблы
	while ( $row = $db->fetchArray( $res ) ) {

		debug_print($row, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		// читаем первый байт, чтобы узнать какого типа транзакция
		$transaction_type = binary_dec(substr($row['data'], 0, 1));
		print 'data='.$row['data']."\n";
		print '$transaction_type = '.$transaction_type."\n";

		// пишем в блок размер транзакции и её саму
		$length = encode_length( strlen( $row['data'] ) ) ;
		print '$length='.$length."\n";
		$transactions .= $length . $row['data'];

		// заодно получим хэши для общего хэша тр-ий
		$mrkl_array[] = hash('sha256', hash('sha256', $row['data']));

		// все тр-ии блока пишутся в отдельную таблу transaction_testblock.
		// чтобы другим нодам слать только недостающие тр-ии
		$md5 = md5($row['data']);
		list(, $data_hex ) = unpack( "H*",   $row['data']);
		$file = save_tmp_644 ('FTT', "{$md5}\t{$data_hex}\t{$row['type']}\t{$row['user_id']}\t{$row['third_var']}");
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				LOAD DATA LOCAL INFILE  '{$file}'
				IGNORE INTO TABLE `".DB_PREFIX."transactions_testblock`
				FIELDS TERMINATED BY '\t'
				(@hash, @data, `type`, `user_id`, `third_var`)
				SET `hash` = UNHEX(@hash),
					   `data` = UNHEX(@data)
				");
		unlink($file);
		//debug_print($db->printsql(), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print('AffectedRows='.$db->getAffectedRows(), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// после обработки транзакции удаляем из БД
		$used_transactions.='0x'.bin2hex($row['hash']).',';

	}

	debug_print("memory", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// если тр-ий нет
	if (!$mrkl_array) {
		print "TX NONE\n";
		$mrkl_array[] = 0;
	}

	debug_print($mrkl_array, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$mrkl_root = $testBlock->merkle_tree_root($mrkl_array);
	$mrkl_root_binary = pack( "H*", $mrkl_root);


	/*
	Заголовок
	TYPE (0-блок, 1-тр-я)     FF (256)
	BLOCK_ID   				       FF FF FF FF (4 294 967 295)
	TIME       					       FF FF FF FF (4 294 967 295)
	USER_ID                         FF FF FF FF FF (1 099 511 627 775)
	LEVEL                              FF (256)
	SIGN                               от 128 байта до 512 байт. Подпись от TYPE, BLOCK_ID, PREV_BLOCK_HASH, TIME, USER_ID, LEVEL, MRKL_ROOT
	Далее - тело блока (Тр-ии)
	*/

	// подписываем нашим нод-ключем заголовок блока
	$rsa = new Crypt_RSA();
	$rsa->loadKey($node_private_key);
	$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
	//$rsa->setHash('sha256');
	$for_sign = "0,{$new_block_id},{$testBlock->prev_block['hash']},{$time},{$my_user_id},{$testBlock->level},{$mrkl_root}";
	debug_print('$for_sign='.$for_sign, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	$signature = $rsa->sign( $for_sign );
	unset($rsa);
	list(, $signature_hex ) = unpack( "H*",   $signature);
	debug_print('$signature_hex = '.$signature_hex, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// хэш шапки блока. нужен для сравнивания с другими и у кого будет меньше - у того блок круче
	$header_hash = ParseData::dsha256("{$my_user_id},{$new_block_id},{$prev_head_hash}");
	debug_print("header_hash={$header_hash}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$data = "{$new_block_id}\t{$time}\t{$testBlock->level}\t{$my_user_id}\t{$header_hash}\t{$signature_hex}\t{$mrkl_root}";
	debug_print($data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	$file = save_tmp_644 ('FTB', $data);

	// для тестов получим что там есть
	$tmp_testblock_data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."testblock`
			", 'fetch_array' );

	debug_print($tmp_testblock_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	debug_print("LOAD DATA LOCAL INFILE  '{$file}' REPLACE INTO TABLE `".DB_PREFIX."testblock`", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	// т.к. эти данные создали мы сами, то пишем их сразу в таблицу проверенных данных, которые будут отправлены другим нодам
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			LOAD DATA LOCAL INFILE  '{$file}' REPLACE INTO TABLE `".DB_PREFIX."testblock`
			FIELDS TERMINATED BY '\t'
			(`block_id`,`time`,`level`,`user_id`, @header_hash, @signature, @mrkl_root)
			SET `header_hash` = UNHEX(@header_hash),
				   `signature` = UNHEX(@signature),
				   `mrkl_root` = UNHEX(@mrkl_root)
			");
	unlink($file);

	// иногда не вставлялось, т.к. уже что-то было в testblock . добавил REPLACE
	debug_print('AffectedRows='.$db->getAffectedRows(), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);


	/// #######################################
	// Отмечаем транзакции, которые попали в transactions_testblock
	// Пока для эксперимента
	// если не отмечать, то получается, что и в transactions_testblock и в transactions будут провернные тр-ии, которые откатятся дважды
	if ( $used_transactions ) {
		$used_transactions = substr($used_transactions, 0, -1);
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."transactions`
				SET `used`=1
				WHERE `hash` IN ({$used_transactions})
				");
        // для теста тупо удаляем, т.к. она уже есть в transactions_testblock
      /*  $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				DELETE FROM `".DB_PREFIX."transactions`
				WHERE `hash` IN ({$used_transactions})
				");*/
	}
	// ############################################

	main_unlock();

	debug_print('end<<<<<<<<<<<<<<<<<<<<<<<', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	unset($testBlock, $data, $for_sign, $mrkl_root_binary , $mrkl_root, $mrkl_array, $data_hex, $row, $transactions);

	//ob_save();

	sleep(1);

	// временно
	//exit;

} while (true);

?>