<?php

/*
 * Прием тр-ий от простых юзеров, а не нодов
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
//ddos_protection($ip);

$encrypted_data = $_POST['data'];

debug_print('$encrypted_data='.$encrypted_data, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

// извлечем ключ, декодируем его и декодируем им данные
$binary_tx = decrypt_data ($encrypted_data, $db);
if (substr($binary_tx, 0, 7)=='[error]')
	die($binary_tx);

debug_print('$binary_tx='.$binary_tx, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

list(, $tx_hex ) = unpack( "H*", $binary_tx );
$tx_hash = md5($binary_tx);

$variables = ParseData::get_variables($db, array('max_tx_size'));

// проверим размер
if ( strlen($binary_tx) > $variables['max_tx_size'] ) {
	die ("error tx size");
}

$block_data['type'] = ParseData::binary_dec_string_shift( $binary_tx, 1);
$block_data['time'] = ParseData::binary_dec_string_shift( $binary_tx, 4 );
$size = ParseData::decode_length($binary_tx);
$block_data['user_id'] = ParseData::string_shift ( $binary_tx, $size ) ;

if ($block_data['user_id']==1)
	$high_rate=1;
else
	$high_rate=0;

// заливаем тр-ию в БД
$data = "{$tx_hash}\t{$high_rate}\t{$tx_hex}";
debug_print("data={$data}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
$file = save_tmp_644 ('FTB', $data );

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			LOAD DATA LOCAL INFILE  '{$file}'
			IGNORE INTO TABLE `".DB_PREFIX."queue_tx`
			FIELDS TERMINATED BY '\t'
			(@hash, `high_rate`, @data)
			SET  `hash` = UNHEX(@hash),
				    `data` = UNHEX(@data)
			");
unlink($file);

//debug_print($db->printsql()."\ngetAffectedRows=".$db->getAffectedRows(),  __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

?>