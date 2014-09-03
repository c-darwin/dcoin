<?php
define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );
$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$lang = get_lang();

// получаем кол-во DC на кошельках
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `currency_id`,
						   sum(`amount`) as sum_amount
			FROM `".DB_PREFIX."wallets`
			GROUP BY `currency_id`
			");
while ( $row = $db->fetchArray( $res ) ) {
	$sum_wallets[$row['currency_id']] = $row['sum_amount'];
}

// получаем кол-во TDC на обещанных суммах
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `currency_id`,
						   sum(`tdc_amount`) as sum_amount
			FROM `".DB_PREFIX."promised_amount`
			GROUP BY `currency_id`
			");
while ( $row = $db->fetchArray( $res ) ) {
	if (!isset($sum_wallets[$row['currency_id']]))
		$sum_wallets[$row['currency_id']] = $row['sum_amount'];
	else
		$sum_wallets[$row['currency_id']] += $row['sum_amount'];
}

$need_array = array(72=>'3 000 000 000 000', 58=>'31 000 000 000 000');

header("Access-Control-Allow-Origin: *");

$currency_list = get_currency_list($db);
foreach ($sum_wallets as $currency_id=>$sum) {
	if ($currency_id==1 || $sum<1 || $currency_id>=1000)
		continue;
	echo "<tr><td>D{$currency_list[$currency_id]}</td><td>{$sum}</td><td>{$need_array[$currency_id]}</td></tr>";
}


?>