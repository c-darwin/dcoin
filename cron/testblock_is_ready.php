<?php
/**
 * Демон, который отсчитывает время, которые необходимо ждать после того,
 * как началось одноуровневое соревнование, у кого хэш меньше.
 * Когда время прошло, то берется блок из таблы testblock и заносится в
 * queue и queue_front для занесение данных к себе и отправки другим
 *
 */

define( 'DC', true );
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

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

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

do {

	debug_print("=>START", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// отметимся в БД, что мы живы.
	upd_deamon_time($db);

	// проверим, не нужно нам выйти, т.к. обновилась версия скрипта
	if (check_deamon_restart($db))
		exit;

	if (get_my_local_gate_ip($db)) {
		sleep(5);
		continue;
	}

	// сколько нужно спать
	$testBlock = new testblock($db);

	$my_miner_id = $testBlock->miner_id;
	$my_user_id = $testBlock->user_id;

	if (!$my_miner_id){
		unset($testBlock);
		debug_print("continue", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		//ob_save();
		sleep(1);
		continue;
	}

	$sleep = $testBlock->getIsReadySleep();
	$prev_head_hash = $testBlock->prev_block['head_hash'];

	debug_print('prev_block:'.print_r_hex($testBlock->prev_block), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	unset($testBlock);

	// Если случится откат или придет новый блок, то testblock станет неактуален
	debug_print('$sleep='.$sleep."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$start_sleep = time();
	for ($i=0; $i<$sleep; $i++) {
		main_lock();
		debug_print('head_hash start '.microtime(true), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$new_head_hash = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SELECT `head_hash` FROM `".DB_PREFIX."info_block` ", 'fetch_one');
		debug_print('head_hash end  '.microtime(true), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		main_unlock();
		list(, $new_head_hash ) = unpack( "H*",   $new_head_hash);
		if ($new_head_hash!=$prev_head_hash) {
			//ob_save();
			debug_print('continue 2', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			upd_deamon_time ($db);
			sleep(1);
			continue 2;
		}
		debug_print($i.'='.time()."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		//ob_save();
		upd_deamon_time ($db);
		if(time()-$start_sleep>$sleep)
			break;
		sleep(1); // спим 1 сек. общее время = $sleep
	}


	/*
	Заголовок
	TYPE (0-блок, 1-тр-я)       FF (256)
	BLOCK_ID   				       FF FF FF FF (4 294 967 295)
	TIME       					       FF FF FF FF (4 294 967 295)
	USER_ID                          FF FF FF FF FF (1 099 511 627 775)
	LEVEL                              FF (256)
	SIGN                               от 128 байта до 512 байт. Подпись от TYPE, BLOCK_ID, PREV_BLOCK_HASH, TIME, USER_ID, LEVEL, MRKL_ROOT
	Далее - тело блока (Тр-ии)
	*/

	// блокируем изменения данных в тестблоке
	// также, нужно блокировать main, т.к. изменение в info_block и block_chain ведут к изменению подписи в testblock
	//main_lock();

	testblock_lock();

	// за промежуток в main_unlock и main_lock мог прийти новый блок
	$testBlock = new testblock($db, true);
	// на всякий случай убедимся, что блок не изменился
	if ( $testBlock->prev_block['head_hash']!=$prev_head_hash) {
		main_unlock();
		unset($testBlock);
		debug_print("continue", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		sleep(1);
		continue;
	}

	// составим блок. заголовок + тело + подпись
	$testblock_data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."testblock`
			WHERE `status` = 'active'
			", 'fetch_array' );
	debug_print(print_r_hex($testblock_data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	debug_print("testBlock->prev_block['block_id']={$testBlock->prev_block['block_id']} // testblock_data['block_id'] = {$testblock_data['block_id']} ", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);


	if (!$testblock_data) {
		//main_unlock();
		testblock_unlock();
		print 'null $testblock_data'."\n";
		unset($testBlock);
		sleep(1);
		print ">>continue\n";
		//ob_save();
		continue;
	}
	// получим транзакции
	$testblock_data['tx'] = '';
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."transactions_testblock`
				ORDER BY `id` ASC
				");
	while ( $row = $db->fetchArray( $res ) ) {
		debug_print($row, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$testblock_data['tx'] .= ParseData::encode_length_plus_data( $row['data'] );
	}
	// на время тестов
	$_tmp_variables = ParseData::get_variables ($db,  array('rollback_blocks_1', 'max_block_size', 'max_tx_size', 'max_tx_count') );
	$_tmp_mrkl_root = ParseData::getMrklroot($testblock_data['tx'], $_tmp_variables);
	debug_print($_tmp_mrkl_root, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// в промежутке межде тем, как блок был сгенерирован и запуском данного скрипта может измениться текущий блок
	// поэтому нужно проверять подпись блока из тестблока

	$prev_block_hash = bin2hex($db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `hash`
			FROM `".DB_PREFIX."info_block`
			", 'fetch_one' ));
	$node_public_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `node_public_key`
			FROM `".DB_PREFIX."miners_data`
			WHERE `user_id`={$testblock_data['user_id']}
			", 'fetch_one' );
	$for_sign = "0,{$testblock_data['block_id']},{$prev_block_hash},{$testblock_data['time']},{$testblock_data['user_id']},{$testblock_data['level']},".bin2hex($testblock_data['mrkl_root']);
	debug_print("checkSign for_sign = {$for_sign} ", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	debug_print("node_public_key = {$node_public_key} ", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// проверяем подпись
	$error = ParseData::checkSign ($node_public_key, $for_sign, $testblock_data['signature'], true);
	if ($error) {

		debug_print("(error) checkSign ".$error, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		rollback_transactions_testblock($db, true);
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "TRUNCATE TABLE `".DB_PREFIX."testblock`");

		//main_unlock();
		testblock_unlock();
		unset($testBlock);
		print ">>continue error checkSign\n";
		//ob_save();
		sleep(1);
		continue;
	}

	// БАГ
	if ($testblock_data['block_id']==$testBlock->prev_block['block_id']) {

		debug_print("[BUG] testblock_data['block_id']==testBlock->prev_block['block_id']", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		rollback_transactions_testblock($db, true);
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "TRUNCATE TABLE `".DB_PREFIX."testblock`");

		testblock_unlock();
		unset($testBlock);
		print ">>continue\n";
		//ob_save();
		sleep(1);
		continue;
	}

	// готовыим заголовок
	$new_block_id_binary = dec_binary( $testblock_data['block_id'], 4 );
	$time_binary = dec_binary( $testblock_data['time'], 4 );
	$user_id_binary = dec_binary( $testblock_data['user_id'], 5 );
	$level_binary = dec_binary( $testblock_data['level'], 1 );
	$prev_block_hash_binary = $testBlock->prev_block['hash'];
	$merkle_root_binary = $testblock_data['mrkl_root'];



	// заголовок
	$block_header = dec_binary (0, 1) . // 0 - это блок
		$new_block_id_binary .
		$time_binary .
		$user_id_binary .
		$level_binary .
		ParseData::encode_length_plus_data($testblock_data['signature']);

	// сам блок
	$block = $block_header . $testblock_data['tx'];
	//list(, $block_hex) = unpack( "H*", $block);

	// теперь нужно разнести блок по таблицам и после этого мы будем его слать всем нодам скриптом disseminator.php
	debug_print("ParseData_front", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	$parsedata = new ParseData($block, $db);
	$parsedata->ParseData_front();


	// и можно удалять данные о тестблоке, т.к. они перешел в нормальный блок
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "TRUNCATE TABLE `".DB_PREFIX."transactions_testblock`");
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "TRUNCATE TABLE `".DB_PREFIX."testblock`");

	// между testblock_generator и testbock_is_ready
	rollback_transactions($db);

	// снимаем блокировку с тестблока и main
	//main_unlock();
	testblock_unlock();

	unset($testBlock);
	unset($parsedata);

	//ob_save();

	print ">HappY END\n";

	unset($block, $merkle_root_binary, $testblock_data, $for_sign);

	sleep(1);

} while (true);

?>
