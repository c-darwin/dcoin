<?php

/*
 * Получаем тр-ии, которые есть у юзера, в ответ выдаем те, что недостают и
 * их порядок следования, чтобы получить валидный блок
 */

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

$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."testblock`
			LIMIT 1
			", 'fetch_array' );

$response_binary_data = dec_binary ($data['block_id'], 4) .
	dec_binary ($data['time'], 4) .
	dec_binary ($data['user_id'], 4) .
	encode_length( strlen($data['signature']) ) . $data['signature']
;

// разбираем присланные данные
$binary_data = $_POST['data'];
$add_sql = '';
if ($binary_data) {
	$tr_array = array();
	// получим хэши тр-ий, которые надо исключить
	do {

		list(, $tr ) = unpack( "H*",  ParseData::string_shift ($binary_data, 16) );
		// проверим
		if ( !check_input_data ($tr , 'md5') )
			die('error md5 ('.$tr.')');
		$add_sql.=$tr.',';

	} while ($binary_data);
	$add_sql = substr($add_sql, 0, -1);
	$add_sql = "WHERE `id` NOT IN ({$add_sql})";
}

// сами тр-ии
$transactions = '';
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `data`
		FROM `".DB_PREFIX."transactions_testblock`
		{$add_sql}
		" );
while ( $row = $db->fetchArray( $res ) ) {
	$length = encode_length( strlen( $row['data'] ) ) ;
	$transactions .= $length . $row['data'];
}
$response_binary_data .= encode_length( strlen( $transactions ) ) . $transactions;

// порядок тр-ий
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `hash`
		FROM `".DB_PREFIX."transactions_testblock`
		ORDER BY `id` ASC
		" );
while ( $row = $db->fetchArray( $res ) ) {
	$response_binary_data .=  $row['hash'];
}

print $response_binary_data;

?>