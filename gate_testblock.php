<?php

define( 'DC', TRUE);

define( 'ABSPATH', dirname(__FILE__) . '/' );

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


/**
- проверяем, находится ли отправитель на одном с нами уровне
- получаем  block_id, user_id, mrkl_root, signature
- если хэш блока меньше того, что есть у нас в табле testblock, то смотртим, есть ли такой же хэш тр-ий,
- если отличается, то загружаем блок от отправителя по адресу http://host/get_testblock.php.
- если не отличается, то просто обновляет хэш блока у себя
 *
 */



/*
 * Пробуем работать без локов
 * */

// пришли данные в post запросе от кого-то (другой нод или простой юзер)
$new_testblock_binary = $_POST['data'];
//print_R($_POST);
$new_testblock['block_id'] = ParseData::binary_dec_string_shift($new_testblock_binary, 4);
$new_testblock['time'] = ParseData::binary_dec_string_shift($new_testblock_binary, 4);
$new_testblock['user_id'] = ParseData::binary_dec_string_shift($new_testblock_binary, 5);
list(, $new_testblock['mrkl_root']) = unpack( "H*", ( string_shift ( $new_testblock_binary, 32 ) ) );
$sign_size = ParseData::decode_length($new_testblock_binary);
$new_testblock['signature'] =  ParseData::string_shift ( $new_testblock_binary, $sign_size ) ;
$new_testblock['signature_hex'] = bin2hex($new_testblock['signature']);

debug_print("new_testblock: ".print_r_hex($new_testblock), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

if ( !check_input_data ($new_testblock['block_id'] , 'int') )
	die('[error] gate_tetblock.php 1 block_id');
if ( !check_input_data ($new_testblock['user_id'] , 'int') )
	die('[error] gate_tetblock.php user_id');
if ( !check_input_data ($new_testblock['time'] , 'int') )
	die('[error] gate_tetblock.php time');
if ( !check_input_data ($new_testblock['mrkl_root'] , 'sha256') )
	die('[error] gate_tetblock.php mrkl_root');
//if ( !check_input_data ($new_testblock['signature'] , 'sha256') )
//	die('error signature');

main_lock();

$testBlock = new testblock($db, true);

if (!isset($testBlock->block_info)) {
	main_unlock();
	die('block_info error');
}

// получим id майнеров, которые на нашем уровне
$nodes_ids = $testBlock->getOurLevelNodes();

// временно для теста выключим
// проверим, верный ли ID блока
if ( $new_testblock['block_id'] != $testBlock->block_info['block_id']+1 ){
	main_unlock();
	die("error {$new_testblock['block_id']}!={$testBlock->block_info['block_id']}+1");
}

/*
 * Проблема одновременных попыток локнуть
 * */

// проверим, есть ли такой майнер
$miner_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `miner_id`
		FROM `".DB_PREFIX."miners_data`
		WHERE `user_id` = {$new_testblock['user_id']}
		LIMIT 1
		", 'fetch_one');
if (!$miner_id) {
	debug_print("[error] user_id", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	main_unlock();
	die('[error] user_id');
}
// проверим, точно ли отправитель с нашего уровня
if (!in_array($miner_id, $nodes_ids)) {
	debug_print("[error] user_id", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	main_unlock();
	die('[error] user_id');
}
// получим допустимую погрешность во времени генерации блока
$max_error_time = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `value`
		FROM `".DB_PREFIX."variables`
		WHERE `name` = 'error_time'
		",	'fetch_one');

// исключим тех, кто сгенерил блок слишком рано.
if ( $testBlock->block_info['time'] + $testBlock->getGenSleep() - $new_testblock['time'] > $max_error_time  ) {
	debug_print("[error] gate_testblock.php block time ({$testBlock->block_info['time']} + {$testBlock->getGenSleep()} - {$new_testblock['time']})", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	main_unlock();
	die("[error] gate_testblock.php block time ({$testBlock->block_info['time']} + {$testBlock->getGenSleep()} - {$new_testblock['time']})");
}

// исключим тех, кто сгенерил блок с бегущими часами
if ( $new_testblock['time'] > time() ) {
	debug_print("[error] gate_testblock.php block time", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	main_unlock();
	die("[error] gate_testblock.php block time");
}

// получим хэш заголовка
$new_header_hash = hash( 'sha256', hash( 'sha256', "{$new_testblock['user_id']},{$new_testblock['block_id']},{$testBlock->block_info['user_id']}" ) );

//testblock_lock();
$my_testblock = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `block_id`,
					 `user_id`,
					 LOWER(HEX(`mrkl_root`)) as `mrkl_root`,
					 LOWER(HEX(`signature`)) as `signature`
		FROM `".DB_PREFIX."testblock`
		WHERE `status` = 'active'
		", 'fetch_array' );
//testblock_unlock();
// получим хэш заголовка
$my_header_hash = hash( 'sha256', hash( 'sha256', "{$my_testblock['user_id']},{$my_testblock['block_id']},{$testBlock->block_info['user_id']}" ) );

// у кого меньше хэш, тот и круче
if ($new_header_hash > $my_header_hash) {
	debug_print("I have less header_hash", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	main_unlock();
	die ('I have less header_hash');
}
/* т.к. на данном этапе в большинстве случаев наш текущий блок будет заменен,
 * то нужно перстать его рассылать другим нодам и дождаться окончания проверки
 */

//testblock_lock();
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		UPDATE `".DB_PREFIX."testblock`
		SET `status` = 'pending'
		");
//testblock_unlock();

debug_print("new_testblock:".$new_testblock, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
debug_print("my_testblock:".$my_testblock, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

// если отличается, то загружаем недостающии тр-ии от отправителя
if ($new_testblock['mrkl_root'] != $my_testblock['mrkl_root']) {

	debug_print("new_testblock['mrkl_root'] != my_testblock['mrkl_root']", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$tr_array = array();
	$send_data = '';
	// получим все имеющиеся у нас тр-ии, которые еще не попали в блоки
	//main_lock();
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT  LOWER(HEX(`hash`)) as `hash`,
						 `data`
			FROM `".DB_PREFIX."transactions`
			");
	while ( $row = $db->fetchArray( $res ) ) {
		$tr_array[$row['hash']] = $row['data'];
		$send_data .= $row['hash'];
	}
	//main_unlock();

	// получим хост отправителя
	$host = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `host`
			FROM `".DB_PREFIX."miners_data`
			WHERE `user_id` = {$new_testblock['user_id']}
			", 'fetch_one' );

	// шлем набор хэшей тр-ий, которые есть у нас
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 10);
	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_URL, "{$host}/get_testblock_transactions.php");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, 'data='.urlencode($send_data));
	$binary_data = curl_exec($ch);
	curl_close($ch);
	/*
	в ответ получаем:
	BLOCK_ID   				       4
	TIME       					       4
	USER_ID                         5
	SIGN                               от 128 до 512 байт. Подпись от TYPE, BLOCK_ID, PREV_BLOCK_HASH, TIME, USER_ID, LEVEL, MRKL_ROOT
	Размер всех тр-ий, размер 1 тр-ии, тело тр-ии.
	Хэши три-ий (порядок тр-ий)
	*/
	debug_print('$send_data='.$send_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	debug_print('$binary_data='.$binary_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// Разбираем полученные бинарные данные.
	$new_testblock['block_id'] = ParseData::binary_dec_string_shift($binary_data, 4);
	$new_testblock['time'] = ParseData::binary_dec_string_shift($binary_data, 4);
	$new_testblock['user_id'] = ParseData::binary_dec_string_shift($binary_data, 5);
	$sign_size = ParseData::decode_length($binary_data);
	$new_testblock['signature'] =  ParseData::string_shift ( $binary_data, $sign_size ) ;
	$new_testblock['signature_hex'] = bin2hex($new_testblock['signature']);

	debug_print($new_testblock, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// недостающие тр-ии
	$length = ParseData::decode_length ( $binary_data ); // размер всех тр-ий
	$tr_binary = ParseData::string_shift ( $binary_data, $length );
	do {
		// берем по одной тр-ии
		$length = ParseData::decode_length ( $tr_binary );
		print '$length='.$length."\n";
		if ($length==0)
			break;
		$tr = ParseData::string_shift ( $tr_binary, $length );
		$tr_array[md5($tr)] = $tr;

	} while (true);

	// порядок тр-ий
	$order_array= array();
	do {
		if ($binary_data)
			$order_array[] = bin2hex(ParseData::string_shift ( $binary_data, 16 ) );
	} while ($binary_data);
	$order_array = array_flip($order_array);


	debug_print('$tr_array:'.print_r_hex($tr_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print('$order_array:'.print_r_hex($order_array), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// сортируем и наши и полученные транзакции
	$transactions = '';
	//$merkle_array = array();
	foreach ( $order_array as $md5 => $id ) {
		$ordered_transactions[$id] = $tr_array[$md5];
		$transactions .= ParseData::encode_length_plus_data( $tr_array[$md5] );
		//$merkle_array[] = hash('sha256', hash('sha256', $tr_array[$md5]));
	}

	debug_print('strlen($transactions)='.strlen($transactions), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print('$ordered_transactions:'.print_r_hex($ordered_transactions), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// формируем блок, который далее будем тщательно проверять
	/*
	Заголовок (от 143 до 527 байт )
	TYPE (0-блок, 1-тр-я)     1
	BLOCK_ID   				       4
	TIME       					       4
	USER_ID                         5
	LEVEL                              1
	SIGN                               от 128 до 512 байт. Подпись от TYPE, BLOCK_ID, PREV_BLOCK_HASH, TIME, USER_ID, LEVEL, MRKL_ROOT
	Далее - тело блока (Тр-ии)
	*/
	//$merkle_root = $testBlock->merkle_tree_root($merkle_array);
	//$merkle_root_binary = pack( "H*", $merkle_root);
	$new_block_id_binary = dec_binary( $new_testblock['block_id'], 4 );
	//$prev_block_hash_binary = $testBlock->block_info['hash'];
	$time_binary = dec_binary( $new_testblock['time'], 4 );
	$user_id_binary = dec_binary( $new_testblock['user_id'], 5 );
	$level_binary = dec_binary( $testBlock->level, 1 );

	$new_block_header = dec_binary (0, 1) . // 0 - это блок
		$new_block_id_binary .
		$time_binary .
		$user_id_binary .
		$level_binary . // $level пишем, чтобы при расчете времени ожидания в следующем блоке не пришлось узнавать, какой был max_miner_id
		ParseData::encode_length_plus_data($new_testblock['signature']);

	$new_block = $new_block_header .	$transactions;
	list(, $new_block_hex) = unpack( "H*", $new_block );

	//testblock_lock();

	// и передаем блок для обратотки через скрипт queue_parser_testblock.php
	// т.к. есть запросы к log_time_, а их можно выполнять только по очереди
	$file = save_tmp_644 ('FQT', "{$new_header_hash}\t{$new_block_hex}");
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					LOAD DATA LOCAL INFILE  '{$file}'
					REPLACE INTO TABLE `".DB_PREFIX."queue_testblock`
					FIELDS TERMINATED BY '\t'
					(@head_hash, @data)
					SET `head_hash` = UNHEX(@head_hash),
						   `data` = UNHEX(@data)
					");
	unlink($file);
	//debug_print($db->printsql()."\nAffectedRows=".$db->getAffectedRows() , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	//main_unlock();

}
else {

	//testblock_lock();

	// если всё нормально, то пишем в таблу testblock новые данные
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."testblock`
			SET
					`time` = {$new_testblock['time']},
					`user_id` = {$new_testblock['user_id']},
					`header_hash`= 0x{$new_header_hash},
					`signature` = 0x{$new_testblock['signature_hex']}
				" );

	//debug_print($db->printsql()."\nAffectedRows=".$db->getAffectedRows() , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	
	//testblock_unlock();

}

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		UPDATE `".DB_PREFIX."testblock`
		SET `status` = 'active'
		");
//debug_print($db->printsql(), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

main_unlock();

/*
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		UPDATE `".DB_PREFIX."testblock_trigger`
		SET  `lock` = 0,
				`lock_time` = 0
		");
print $db->printsql()."\n";
*/
?>