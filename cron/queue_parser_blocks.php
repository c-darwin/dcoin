<?php
if (!$argv) die('browser');

/*
 * Берем блоки (queue_blocks) и обрабатываем
 * */


define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );
require_once( ABSPATH . 'includes/errors.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

/* Берем блок. Если блок имеет лучший хэш, то ищем, в каком блоке у нас пошла вилка
 * Если вилка пошла менее чем variables->rollback_blocks блоков назад, то
 *  - получаем всю цепочку блоков,
 *  - откатываем фронтальные данные от наших блоков,
 *  - заносим фронт. данные из новой цепочки
 *  - если нет ошибок, то откатываем наши данные из блоков
 *  - и заносим новые данные
 *  - если где-то есть ошибки, то откатываемся к нашим прежним данным
 * Если вилка была давно, то ничего не трогаем, и оставлеяем скрипту blocks_collection.php
 * Ограничение variables->rollback_blocks нужно для защиты от подставных блоков
 *
 * */

do {

	debug_print("START", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// отметимся в БД, что мы живы.
	upd_deamon_time($db);

	// проверим, не нужно нам выйти, т.к. обновилась версия скрипта
	if (check_deamon_restart($db))
		exit;

	$blocks = array();

	//print 'memory='.memory_get_usage()."\n---------------------------------\n";

	// ждем, пока разлочится и лочим сами, чтобы не попасть в тот момент, когда данные из блока уже занесены в БД, а info_block еще не успел обновиться
	main_lock();
	$prev_block_data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."info_block`
			", 'fetch_array' );
	debug_print('info_block:'.print_r_hex($prev_block_data), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$new_block_data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."queue_blocks`
			LIMIT 1
			", 'fetch_array' );
	//print '$new_block_data:';
	//print_r($new_block_data);
	if (!$new_block_data) {
		main_unlock();
		debug_print("continue", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		sleep(1);
		continue;
	}

	$new_block_data['head_hash_hex'] = bin2hex($new_block_data['head_hash']);
	$prev_block_data['head_hash_hex'] = bin2hex($prev_block_data['head_hash']);
	$new_block_data['hash_hex'] = bin2hex($new_block_data['hash']);
	$prev_block_data['hash_hex'] = bin2hex($prev_block_data['hash']);

	$variables = ParseData::get_variables ($db,  array('rollback_blocks_1', 'max_block_size', 'max_tx_size', 'max_tx_count') );

	/*
	 * Базовая проверка
	 */

	// проверим, укладывается ли блок в лимит rollback_blocks_1
	if ( $new_block_data['block_id'] > $prev_block_data['block_id'] + $variables['rollback_blocks_1'] ) {
		main_unlock();
		//print "new_block_data['block_id'] > prev_block_data['block_id]} + variables['rollback_blocks_1']\n";
		delete_queue_block();
		continue;
	}

	// проверим не старый ли блок в очереди
	if ( $new_block_data['block_id'] < $prev_block_data['block_id'] ){
		main_unlock();
		//print "new_block_data['block_id'] < prev_block_data['block_id']\n";
		delete_queue_block();
		continue;
	}


	if ($new_block_data['block_id']==$prev_block_data['block_id']) {

		debug_print($new_block_data['block_id'].'=='.$prev_block_data['block_id'], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// сравним хэши
		if ($new_block_data['head_hash_hex'] <= $prev_block_data['head_hash_hex']) {

			//print '$new_block_data[head_hash_hex] <= $prev_block_data[head_hash_hex] '."\n";
			// если это тотже блок и его генерил тот же юзер, то могут быть равные head_hash
			if ($new_block_data['head_hash_hex'] == $prev_block_data['head_hash_hex']) {
				//print '$new_block_data[head_hash_hex] == $prev_block_data[head_hash_hex] '."\n";
				// в этом случае проверяем вторые хэши. Если новый блок имеет больший хэш, то нам он не нужен
				// или если тот же хэш, значит блоки одинаковые
				if ($new_block_data['hash_hex'] >= $prev_block_data['hash_hex']) {
					main_unlock();
					debug_print("new_block_data['hash_hex'] >= prev_block_data['hash_hex']", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
					delete_queue_block();
					continue;
				}
			}
		}
		else {
			main_unlock();
			debug_print("{$new_block_data['head_hash_hex']} > {$prev_block_data['head_hash_hex']}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			delete_queue_block();
			continue;
		}
	}
	debug_print($new_block_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	/*
	 * Загрузка блоков для детальной проверки
	 */

	//main_lock();
	$host = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `host`
			FROM `".DB_PREFIX."miners_data`
			WHERE `user_id` = {$new_block_data['user_id']}
			", 'fetch_one' );
	//print '$host='.$host."\n";

	$block_id = $new_block_data['block_id'];

	$result = get_blocks($block_id, $host, $new_block_data['user_id'], 'rollback_blocks_1');
	if ($result) {
		debug_print("[error] ".$result, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		nodes_ban ($db, $new_block_data['user_id'], $result."\n".__FILE__.', '.__LINE__.', '. __FUNCTION__.', '.__CLASS__.', '. __METHOD__);
		main_unlock();
		delete_queue_block();
		continue;
	}

	debug_print("-------------------------HAPPY END ---------------------------", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// снимаем блокировку на любые добавления данных из блоков/тр-ий
	main_unlock();
	sleep(1);

} while (true);

?>