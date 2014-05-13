<?php
/*
 * Вызывается скриптом gate_hashes.php
 * Выдает тело тр-ии
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

$encrypted_data = $_REQUEST['data'];

//debug_print("encrypted_data={$encrypted_data}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

$binary_tx_hashes = decrypt_data ($encrypted_data, $db, $decrypted_key);
if (substr($binary_tx_hashes, 0, 7)=='[error]')
	die($binary_tx_hashes);

//debug_print("binary_tx_hashes={$binary_tx_hashes}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

$binary_tx='';
// Разбираем список транзакций
do {
	list(, $tx_hash ) = unpack( "H*", ( string_shift ( $binary_tx_hashes, 16 ) ) );
	if (!$tx_hash)
		continue;
	$tx = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `data`
			FROM `".DB_PREFIX."transactions`
			WHERE `hash` = 0x{$tx_hash}
			", 'fetch_one' );
	if ($tx)
		$binary_tx.=ParseData::encode_length_plus_data($tx);
} while ($binary_tx_hashes);

// шифруем тр-ии
$aes = new Crypt_AES();
$aes->setKey($decrypted_key);
$encrypted_data = $aes->encrypt($binary_tx);
unset($aes);

//debug_print("decrypted_key={$decrypted_key}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
//debug_print("encrypted_data={$encrypted_data}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

print $encrypted_data;



?>