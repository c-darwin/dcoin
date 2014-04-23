<?php

/*
 * принимаем зашифрованный список тр-ий, которые есть у отправителя
 * Вызывается скриптом disseminator.php
 * Блоки не качаем тут, т.к. может быть цепочка блоков, а их качать долго
 * тр-ии качаем тут, т.к. они мелкие и точно скачаются за 60 сек
 * */

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

$ip = $_SERVER['REMOTE_ADDR'];

/*
  * Защита от случайного ддоса
 * */
//ddos_protection ($ip);
/***/
//ob_start();


/*
 * Пробуем работать без локов
 * */

$encrypted_data = $_POST['data'];

// извлечем ключ, декодируем его и декодируем им данные
$binary_data = decrypt_data ($encrypted_data, $db);
if (substr($binary_data, 0, 7)=='[error]')
	die($binary_data);
/*
 * структура данных:
 * user_id - 4 байта
 * type - 1 байт. 0 - блок, 1 - список тр-ий
 * {если type==1}:
 * <любое кол-во следующих наборов>
 * high_rate - 1 байт
 * tx_hash - 16 байт
 * </>
 * {если type==0}:
 * block_id - 3 байта
 * hash - 32 байт
 * head_hash - 32 байт
 * <любое кол-во следующих наборов>
 * high_rate - 1 байт
 * tx_hash - 16 байт
 * </>
 * */

debug_print('$binary_data='.$binary_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

//main_lock();
$block_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `block_id`
		FROM `".DB_PREFIX."info_block`
		", 'fetch_one' );
//main_unlock();

// user_id отправителя, чтобы знать у кого брать данные, когда они будут скачиваться другим скриптом
$new_data['user_id'] = binary_dec(string_shift($binary_data, 4));
// данные могут быть отправлены юзером, который уже не майнер
$miner_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `miner_id`
				FROM `".DB_PREFIX."miners_data`
				WHERE `user_id` = {$new_data['user_id'] } AND
							 `miner_id`>0
				LIMIT 1
				", 'fetch_one');
if ( !$miner_id )
	die('error miner_id');

// если 0 - значит вначале идет инфа о блоке, если 1 - значит сразу идет набор хэшей тр-ий
$new_data['type'] = binary_dec(string_shift($binary_data, 1));
if ($new_data['type'] == 0) {

	// ID блока, чтобы не скачать старый блок
	$new_data['block_id'] = binary_dec(string_shift($binary_data, 3));

	// нет смысла принимать старые блоки
	if ( $new_data['block_id'] >= $block_id ) {

		// Это хэш для соревнования, у кого меньше хэш
		list(, $new_data['hash'] ) = unpack( "H*", ( string_shift ( $binary_data, 32 ) ) );

		// Для доп. соревнования, если head_hash равны (шалит кто-то из майнеров и позже будет за такое забанен)
		list(, $new_data['head_hash'] ) = unpack( "H*", ( string_shift ( $binary_data, 32 ) ) );

		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT IGNORE INTO `".DB_PREFIX."queue_blocks` (
					`hash`,
					`head_hash`,
					`user_id`,
					`block_id`
				) VALUES (
					0x{$new_data['hash']},
					0x{$new_data['head_hash']},
					{$new_data['user_id']},
					{$new_data['block_id']}
				)");

		//debug_print($db->printsql(), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	}
}

debug_print($new_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

$variables = ParseData::get_variables($db, array('max_tx_size'));

$need_tx='';
// Разбираем список транзакций
do {

	// 1 - это админские тр-ии, 0 - юзерские
	$new_data['high_rate'] = binary_dec(string_shift($binary_data, 1));

	list(, $new_data['tx_hash'] ) = unpack( "H*", ( string_shift ( $binary_data, 16 ) ) );

	// тр-ий нету
	if (!$new_data['tx_hash']) {
		debug_print('!$new_data[tx_hash]', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		exit;
	}

	// проверим, нет ли у нас такой тр-ии
	$exists = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT count(`hash`)
			FROM `".DB_PREFIX."log_transactions`
			WHERE `hash` = 0x{$new_data['tx_hash']}
			", 'fetch_one' );
	if ($exists) {
		debug_print('!exists! continue', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		continue;
	}

	$need_tx.=hextobin($new_data['tx_hash']);

} while ($binary_data);

if (!$need_tx)
	exit;

// получился список нужных нам тр-ий, теперь его пошлем тому ноду, у которого они есть

$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `host`,
						 `node_public_key`
			FROM `".DB_PREFIX."miners_data`
			WHERE `user_id` = {$new_data['user_id']}
			", 'fetch_array' );
debug_print($data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
$host = $data['host'];
$node_public_key = $data['node_public_key'];
debug_print($new_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

// шифруем данные. ключ $key будем использовать для расшифровки ответа
$encrypted_data = encrypt_data ($need_tx, $node_public_key, $db, $my_key);

debug_print('$encrypted_data='.$encrypted_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

$url = "{$host}/get_tx.php";
debug_print($url, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
// загружаем сами тр-ии
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'data='.urlencode($encrypted_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$encrypted_tx_set = curl_exec($ch);
curl_close($ch);

debug_print('$encrypted_tx_set='.$encrypted_tx_set, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
debug_print('$my_key='.$my_key, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

$aes = new Crypt_AES();
$aes->setKey($my_key);
// теперь в $binary_tx будут обычные тр-ии
$binary_tx = $aes->decrypt($encrypted_tx_set);
unset($aes);

debug_print('$binary_tx='.$binary_tx, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

// разберем полученные тр-ии
do {
	$tx_size = ParseData::decode_length($binary_tx);
	$tx_binary_data =ParseData::string_shift ( $binary_tx, $tx_size ) ;

	debug_print('$tx_binary_data='.$tx_binary_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	list(, $tx_hex ) = unpack( "H*", $tx_binary_data );

	// проверим размер
	if ( strlen($tx_binary_data) > $variables['max_tx_size'] ) {
		debug_print('strlen($binary_tx) > $variables[max_tx_size]', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		die ("error tx size");
	}

	// точно ли выдали то
	/*if ( md5($tx_binary_data) != $new_data['tx_hash'] ) {
		debug_print("error tx_hash (".md5($tx_binary_data)."!={$new_data['tx_hash']})", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		die ("error tx_hash (".md5($tx_binary_data)."!={$new_data['tx_hash']})");
	}*/

	// временно для тестов
	$new_data['high_rate'] = 0;

	$tx_hash = md5($tx_binary_data);

	// заливаем тр-ию в БД
	$file = save_tmp_644 ('FTB', "{$tx_hash}\t{$new_data['high_rate']}\t$tx_hex");
	debug_print("hash={$tx_hash}\ndata={$tx_hex}" , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			LOAD DATA LOCAL INFILE  '{$file}'
			IGNORE INTO TABLE `".DB_PREFIX."queue_tx`
			FIELDS TERMINATED BY '\t'
			(@hash, `high_rate`, @data)
			SET  `hash` = UNHEX(@hash),
				    `data` = UNHEX(@data)
			");
	unlink($file);

} while ($binary_tx);


?>