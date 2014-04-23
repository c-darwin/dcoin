<?php

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(30);

require_once( ABSPATH . 'includes/fns-main.php' );
//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$start = $_REQUEST['start'];
if ( !check_input_data ($start, 'bigint') )
	die ('error start');

print '<table><tr><td>block_id</td><td>hash</td><td>time</td><td>time</td><td>user_id</td><td>level</td><td>transactions</td><td>sign</td></tr>';
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `data`,
					 `hash`
		FROM `".DB_PREFIX."block_chain`
		ORDER BY `id` ASC
		LIMIT {$start}, 100
		");
$bin_to_hex_array = array('sign', 'public_key', 'encrypted_message', 'comment', 'bin_public_keys');
while ( $row = $db->fetchArray( $res ) ) {
	$hash = substr(bin2hex($row['hash']), 0, 8);
	$binary_data = $row['data'];
	$parsedata = new ParseData($binary_data, $db);
	$parsedata->ParseData_tmp();
	$block_data = $parsedata->block_data;
	$tx_array = $parsedata->tx_array;
	$block_data['sign'] = bin2hex($block_data['sign']);
	print "<tr><td>{$block_data['block_id']}</td><td>{$hash}</td><td>{$block_data['time']}</td><td>".date('d-m-Y H:i:s', $block_data['time'])."</td><td>{$block_data['user_id']}</td><td>{$block_data['level']}</td><td>";
	if ($tx_array) {
		print "<div style=\"width: 500px; height: 400px; overflow: auto; background-color: #f2dede\"><pre>";
		for ($i=0; $i<sizeof($tx_array); $i++) {
			foreach ($tx_array[$i] as $k=>$v) {
				if (in_array($k, $bin_to_hex_array))
					$tx_array[$i][$k] = bin2hex($v);
				if ($k=='file')
					$tx_array[$i][$k] = 'file size: '.strlen($v);
				if ($k=='code')
					$tx_array[$i][$k] = ParseData::dsha256($v);

			}
		}
		print_R($tx_array);
		print "</pre></div>";
	}
	print "</td>";
	print "<td><div style=\"width: 300px; height: 40px; overflow: auto; background-color: #f2dede\">{$block_data['sign']}</div></td>";
	print "</tr>";

}
print '</table>';

?>