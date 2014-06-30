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

$start = @$_REQUEST['start'];
$block_id = @$_REQUEST['block_id'];
if ( !empty($start) && !check_input_data ($start, 'bigint'))
	die ('bad input data');
if ( !empty($block_id) && !check_input_data ($block_id, 'bigint'))
	die ('bad input data');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
	<title>Block explorer</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<link href="../css/bootstrap.css" rel="stylesheet">
<style type="text/css">
	body {
		padding: 10px 10px 10px 10px;
	}
</style>
<link href="../css/bootstrap-responsive.css" rel="stylesheet">
<body>
<?php
if ($start || (!$start && !$block_id)) {
	if (!$start && !$block_id) {
		print '<h1>Latest Blocks</h1>';
		$sql = "SELECT `data`,  `hash`
				FROM `".DB_PREFIX."block_chain`
				ORDER BY `id` DESC
				LIMIT 15";
	}
	else {
		$sql = "SELECT `data`,  `hash`
				FROM `".DB_PREFIX."block_chain`
				ORDER BY `id` ASC
				LIMIT ".($start-1).", 100";
	}
	print '<table class="table"><tr><th>Block</th><th>Hash</th><th>Time</th><th>User id</th><th>Level</th><th>Transactions</th></tr>';
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, $sql);
	$bin_to_hex_array = array('sign', 'public_key', 'encrypted_message', 'comment', 'bin_public_keys');
	while ( $row = $db->fetchArray( $res ) ) {
		//$hash = substr(bin2hex($row['hash']), 0, 8);
		$hash = bin2hex($row['hash']);
		$binary_data = $row['data'];
		$parsedata = new ParseData($binary_data, $db);
		$parsedata->ParseData_tmp();
		$block_data = $parsedata->block_data;
		$tx_array = $parsedata->tx_array;
		$block_data['sign'] = bin2hex($block_data['sign']);
		print "<tr><td><a href='block_explorer.php?block_id={$block_data['block_id']}'>{$block_data['block_id']}</a></td><td>{$hash}</td><td>".date('d-m-Y H:i:s', $block_data['time'])."</td><td>{$block_data['user_id']}</td><td>{$block_data['level']}</td><td>";
		if ($tx_array) {
			print sizeof($tx_array);
			/*
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
			print "</pre></div>";*/
		}
		else
			print '0';
		print "</td>";
		//print "<td><div style=\"width: 300px; height: 40px; overflow: auto; background-color: #f2dede\">{$block_data['sign']}</div></td>";
		print "</tr>";

	}
	print '</table>';
}
else if ($block_id) {
	print '<table class="table">';
	$row = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `data`,
						 `hash`
			FROM `".DB_PREFIX."block_chain`
			WHERE `id` = {$block_id}
			LIMIT 1
			", 'fetch_array');
	$bin_to_hex_array = array('sign', 'public_key', 'encrypted_message', 'comment', 'bin_public_keys');
		//$hash = substr(bin2hex($row['hash']), 0, 8);
		$hash = bin2hex($row['hash']);
		$binary_data = $row['data'];
		$parsedata = new ParseData($binary_data, $db);
		$parsedata->ParseData_tmp();
		$block_data = $parsedata->block_data;
		$tx_array = $parsedata->tx_array;
		$block_data['sign'] = bin2hex($block_data['sign']);
		print "<tr><td><strong>Block_id</strong></strong></td><td>{$block_data['block_id']}</td></tr>";
		print "<tr><td><strong>Hash</strong></td><td>{$hash}</td></tr>";
		print "<tr><td><strong>Time</strong></td><td>".date('d-m-Y H:i:s', $block_data['time'])." / {$block_data['time']}</td></tr>";
		print "<tr><td><strong>User_id</strong></td><td>{$block_data['user_id']}</td></tr>";
		print "<tr><td><strong>Level</strong></td><td>{$block_data['level']}</td></tr>";
		print "<tr><td><strong>Sign</strong></td><td>".chunk_split($block_data['sign'], 130)."</td></tr>";
		if ($tx_array) {
			//print sizeof($tx_array);
			print "<tr><td><strong>Transactions</strong></td><td><div><pre>";
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
			print "</pre></div></td></tr>";
		}
		//else
		//	print '0';
		//print "</td>";
		//print "<td><div style=\"width: 300px; height: 40px; overflow: auto; background-color: #f2dede\">{$block_data['sign']}</div></td>";
		//print "</tr>";
	print '</table>';
}
?>
</body>
</html>