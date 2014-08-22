<?php
define( 'DC', true );
/**
 * Делаем по 1 запросу каждому ноду, с кем установлена связь, получаем номер макс. блока.
 * В цикле запрашиваем блоки до этого числа.
 * 
 */
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
		
$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$error = false;

do {

	$hosts = array();

	debug_print("START", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// отметимся в БД, что мы живы.
	upd_deamon_time($db);
	// проверим, не нужно нам выйти, т.к. обновилась версия скрипта
	if (check_deamon_restart($db))
		exit;

	// если это первый запуск во время инсталяции, то нужно дождаться, пока юзер загрузит свой ключ
	// т.к. возможно этот ключ уже есть в блоках и нужно обновить внутренние таблицы
	$collective = get_community_users($db);
	if (!$collective && !get_user_public_key($db)) {
		sleep(1);
		continue;
	}

	main_lock();

	// если это первый запуск во время инсталяции
	$current_block_id = get_block_id($db);
	if (!$current_block_id) {

		/*
		// это обработка локальной базы блоков
		if (file_exists(ABSPATH . 'localblocks')) {
			debug_print('localblocks', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			$i=0;
			do {
				$i++;
				debug_print(ABSPATH.'tools/blocks/'.$i, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				if (!file_exists(ABSPATH.'tools/blocks/'.$i))
					break;
				$new_block = file_get_contents(ABSPATH.'tools/blocks/'.$i);
				$parsedata = new ParseData($new_block, $db);
				$error = $parsedata->ParseDataFull();
				if ($error) {
					print $error;
					break;
				}
				$parsedata->insert_into_blockchain();
				upd_deamon_time($db);
				if (check_deamon_restart($db)) {
					main_unlock();
					exit;
				}
			} while (true);
		}*/

		if (file_exists(ABSPATH . 'blockchain')) {
			$fp = fopen(ABSPATH . 'blockchain', 'r');
			do {
				$data_size = binary_dec(fread($fp, 5));
				if ($data_size) {
					$data_binary = fread($fp, $data_size);
					$block_id = binary_dec(string_shift($data_binary, 5));
					$data_length = ParseData::decode_length($data_binary);
					$block_data_binary = string_shift($data_binary, $data_length);

					$parsedata = new ParseData($block_data_binary, $db);
					$error = $parsedata->ParseDataFull();
					if ($error) {
						print $error;
						break;
					}
					$parsedata->insert_into_blockchain();
					upd_deamon_time($db);
					if (check_deamon_restart($db)) {
						main_unlock();
						exit;
					}

					// ненужный тут размер в конце блока данных
					fread($fp, 5);
				}
			} while ($data_size);
			fclose($fp);
		}

		$new_block = file_get_contents(ABSPATH . '1block.bin');
		$parsedata = new ParseData($new_block, $db);
		$parsedata->current_version = trim(file_get_contents(ABSPATH . 'version'));
		$error = $parsedata->ParseDataFull();
		$parsedata->insert_into_blockchain();

		/*$version = file_get_contents( ABSPATH . 'version' );
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				UPDATE `".DB_PREFIX."info_block`
				SET `current_version` = '{$version}'
				");*/

		main_unlock();
		sleep(1);
		continue;
	}
	main_unlock();

	$my_config = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `local_gate_ip`,
						  `static_node_user_id`
			FROM `".DB_PREFIX."config`
			", 'fetch_array' );

	if ($my_config['local_gate_ip']) {
		$hosts[0]['host'] = $my_config['local_gate_ip'];
		$hosts[0]['user_id'] = $my_config['static_node_user_id'];
		$node_host = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `host`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$my_config['static_node_user_id']}
				", 'fetch_one');
		$get_max_block_script_name = 'protected_get_max_block.php?node_host='.$node_host;
		$get_block_script_name = 'protected_get_block.php';
		$add_node_host = '&node_host='.$node_host;
	}
	else {
		// получим список нодов, с кем установлено рукопожатие
		$hosts = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT *
				FROM `".DB_PREFIX."nodes_connection`
		", 'all_data');
		$get_max_block_script_name = 'get_max_block.php';
		$get_block_script_name = 'get_block.php';
		$add_node_host = '';
	}

	debug_print($hosts, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	if (!$hosts) {
		sleep(1);
		continue;
	}

	$max_block_id = 1;
	// получим максимальный номер блока
	for ($i=0; $i<sizeof($hosts); $i++) {

		$url = "{$hosts[$i]['host']}/{$get_max_block_script_name}";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		debug_print('$url='.$url, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		$id = intval(curl_exec($ch));
		if ($id > $max_block_id || $i==0) {
			$max_block_id = $id;
			$max_block_id_host = $hosts[$i]['host'];
			$max_block_id_user_id = $hosts[$i]['user_id'];
		}
		curl_close($ch);
		debug_print('$max_block_id='.$max_block_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		upd_deamon_time($db);
		if (check_deamon_restart($db)){
			exit;
		}
	}
	
	// получим наш текущий имеющийся номер блока
	// ждем, пока разлочится и лочим сами, чтобы не попасть в тот момент, когда данные из блока уже занесены в БД, а info_block еще не успел обновиться
	main_lock();
	$current_block_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,
			"SELECT `block_id` FROM `".DB_PREFIX."info_block`
			" , 'fetch_one');

	if (!$current_block_id)
		$current_block_id = 0;

	debug_print('$current_block_id='.$current_block_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$LOG_MARKER =  "[{$current_block_id}]";

	if ($max_block_id<=$current_block_id) {
		debug_print('continue $max_block_id<=$current_block_id) '.$max_block_id.'<='.$current_block_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		main_unlock();
		sleep(1);
		continue;
	}

	// в цикле собираем блоки, пока не дойдем до максимального
	for ($block_id = $current_block_id + 1 ; $block_id < $max_block_id + 1; $block_id ++) {

		// отметимся в БД, что мы живы.
		upd_deamon_time($db);
		// отметимся, чтобы не спровоцировать очистку таблиц
		upd_main_lock($db);
		// проверим, не нужно нам выйти, т.к. обновилась версия скрипта
		if (check_deamon_restart($db)){
			main_unlock();
			exit;
		}

		$variables = ParseData::get_variables ($db,  array('rollback_blocks_1', 'rollback_blocks_2', 'max_block_size', 'max_tx_size', 'max_tx_count') );

		$ch = curl_init();
		$url = "{$max_block_id_host}/{$get_block_script_name}?id={$block_id}{$add_node_host}";
		debug_print($url, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		$binary_block = curl_exec($ch);
		curl_close($ch);
		debug_print('$block_data:'.$binary_block, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		upd_deamon_time($db);

		if (!$binary_block) {
			// баним на 1 час хост, который дал нам пустой блок, хотя должен был дать все до максимального
			// для тестов убрал, потом вставить.
			//nodes_ban ($db, $max_block_id_user_id, substr($binary_block, 0, 512)."\n".__FILE__.', '.__LINE__.', '. __FUNCTION__.', '.__CLASS__.', '. __METHOD__);
			main_unlock();
			continue 2;
		}

		$binary_block_full = $binary_block;

		ParseData::string_shift($binary_block, 1); // уберем 1-й байт - тип (блок/тр-я)
		// распарсим заголовок блока
		$block_data = parse_block_header ($binary_block);
		debug_print('$block_data:'.print_r_hex($block_data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print('$block_id='.$block_id, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// если существуют глючная цепочка, тот тут мы её проигнорируем
		$bad_blocks = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				SELECT `bad_blocks` FROM `".DB_PREFIX."config`
				" , 'fetch_one');
		$bad_blocks = json_decode($bad_blocks, true);
		debug_print('$bad_blocks='.print_r_hex($bad_blocks), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		if (@$bad_blocks[$block_data['block_id']] == bin2hex($block_data['sign'])) {
			nodes_ban ($db, $max_block_id_user_id, "bad_block = {$block_data['block_id']}=>{$bad_blocks[$block_data['block_id']]}\n".__FILE__.', '.__LINE__.', '. __FUNCTION__.', '.__CLASS__.', '. __METHOD__);
			main_unlock();
			continue 2;
		}


		// размер блока не может быть более чем max_block_size
		if ($current_block_id>1)
			if ( strlen($binary_block) > $variables['max_block_size'] ) {
				nodes_ban ($db, $max_block_id_user_id, "strlen(binary_block) > {$variables['max_block_size']}\n". __FILE__.', '.__LINE__.', '. __FUNCTION__.', '.__CLASS__.', '. __METHOD__);
				main_unlock();
				continue 2;
			}

		if ($block_data['block_id']!=$block_id) {
			debug_print('continue $block_data[block_id]!=$block_id', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			nodes_ban ($db, $max_block_id_user_id, "{$block_data['block_id']}!={$block_id}\n".__FILE__.', '.__LINE__.', '. __FUNCTION__.', '.__CLASS__.', '. __METHOD__);
			main_unlock();
			continue 2;
		}

		// нам нужен хэш предыдущего блока, чтобы проверить подпись
		if ($block_id>1) {
			$prev_block_hash = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `hash`
					FROM `".DB_PREFIX."block_chain`
					WHERE `id` = ".($block_id-1)."
					", 'fetch_one');
			//debug_print($db->printsql(), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			list(, $prev_block_hash ) = unpack( "H*",   $prev_block_hash);
		}
		else
			$prev_block_hash = 0;

		debug_print( '$prev_block_hash='.$prev_block_hash, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		if ($block_id == 1)
			$first = true;
		else
			$first = false;
		// нам нужен меркель-рут текущего блока
		//print '$binary_block='.$binary_block."\n";
		debug_print( $variables, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		$mrkl_root = ParseData::getMrklroot($binary_block, $variables, $first);
		debug_print( '$mrkl_root='.$mrkl_root, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		if (substr($mrkl_root, 0, 7) == '[error]') {
			nodes_ban ($db, $max_block_id_user_id, __FILE__.', '.__LINE__.', '. __FUNCTION__.', '.__CLASS__.', '. __METHOD__);
			main_unlock();
			continue 2;
		}

		// публичный ключ того, кто этот блок сгенерил
		$node_public_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `node_public_key`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$block_data['user_id']}
				", 'fetch_one');


		//print '$node_public_key='.$node_public_key."\n";
		// SIGN от 128 байта до 512 байт. Подпись от TYPE, BLOCK_ID, PREV_BLOCK_HASH, TIME, USER_ID, LEVEL, MRKL_ROOT
		$for_sign = "0,{$block_data['block_id']},{$prev_block_hash},{$block_data['time']},{$block_data['user_id']},{$block_data['level']},{$mrkl_root}";
		// проверяем подпись
		if (!$first)
			$error = ParseData::checkSign ($node_public_key, $for_sign, $block_data['sign'], true);
		// качаем предыдущие блоки до тех пор, пока отличается хэш предудущего.
		// другими словами, пока подпись с $prev_block_hash будет неверной, т.е. пока что-то есть в $error
		if ($error) {

			debug_print( "error block_collection checkSign={$error}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			//main_unlock();
			if ($block_id < 1) {
				debug_print( '[error] $block_id < 1 continue 2', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
				main_unlock();
				continue 2;
			}
			// нужно привести данные в нашей БД в соответствие с данными у того, у кого качаем более свежий блок
			$LOG_MARKER =  "download prev blocks get_old_blocks({$block_data['user_id']}, ".($block_id-1).", {$max_block_id_host}, {$max_block_id_user_id})";
			$result = get_old_blocks($block_data['user_id'], $block_id-1, $max_block_id_host, $max_block_id_user_id, $get_block_script_name, $add_node_host);
			debug_print( $result, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			//main_lock();
			if ($result) {
				nodes_ban ($db, $max_block_id_user_id, 'block_id='.$block_id."\n".$result."\n".__FILE__.', '.__LINE__.', '. __FUNCTION__.', '.__CLASS__.', '. __METHOD__);
				main_unlock();
				continue 2;
			}
		}
		else {

			debug_print( "===========Вилка найдена=============\nСошлись на блоке {$block_id}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

			//main_lock();

			// получим наши транзакции в 1 бинарнике, просто для удобства
			$transactions = '';
			$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `data`
					FROM `".DB_PREFIX."transactions`
					WHERE `verified` = 1 AND
								 `used` = 0
					");
			while ( $row = $db->fetchArray( $res ) ) {
				$transactions .= ParseData::encode_length_plus_data( $row['data'] );
			}

			if ($transactions) {
				// отмечаем, что эти тр-ии теперь нужно проверять по новой
				$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."transactions`
					SET  `verified` = 0
					WHERE `verified` = 1 AND
								 `used` = 0
				");
				//debug_print($db->printsql(), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

				$LOG_MARKER =  "Вилка на {$block_id}. Откатываем по фронту все свежие тр-ии. [{$current_block_id}]";
				// откатываем по фронту все свежие тр-ии
				$parsedata = new ParseData($transactions, $db);
				$parsedata->ParseDataRollbackFront();
				unset($parsedata);
			}

			$LOG_MARKER =  "Вилка на {$block_id}";
			rollback_transactions_testblock($db, true);

			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					TRUNCATE TABLE `".DB_PREFIX."testblock`
					");

		}

		//print '$binary_block_full='.$binary_block_full."\n";
		// теперь у нас в таблицах всё тоже самое, что у нода, у которого качаем блок
		// и можем этот блок проверить и занести в нашу БД
		$LOG_MARKER =  "new block_id = ".($block_id);
		$parsedata = new ParseData($binary_block_full, $db);
		$error = $parsedata->ParseDataFull();
		debug_print("parsedata->block_data ".print_r_hex($parsedata->block_data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		if (!$error) $parsedata->insert_into_blockchain();
		unset($parsedata);

		//main_unlock();

		// начинаем всё с начала уже с другими нодами. Но у нас уже могут быть новые блоки до $block_id, взятые от нода, которого с в итоге мы баним
		if ($error) {
			//$block_id--;
			nodes_ban ($db, $max_block_id_user_id, '$block_id='.$block_id."\n".$error."\n". __FILE__.', '.__LINE__.', '. __FUNCTION__.', '.__CLASS__.', '. __METHOD__);
			debug_print("[[error]] ## пробуем взять этот же блок у другого нода ParseDataFull error={$error}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			//ob_save();
			main_unlock();
			sleep(1);
			continue 2;
		}
	}


	main_unlock();

	// спим 1 минуту
	sleep(10);
	unset($binary_block_full, $binary_block, $transactions, $mrkl_root, $for_sign, $block_data);

} while (true);

?>