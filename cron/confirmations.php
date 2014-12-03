<?php
if (!$argv) die('browser');

define( 'DC', true );

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );
require_once( ABSPATH . 'includes/errors.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

// наш последний блок-1
$block_id = get_block_id($db)-1;
$hash = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `hash`
		FROM `".DB_PREFIX."block_chain`
		WHERE `id`= {$block_id}
		", 'fetch_one');

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `host`,
					 `user_id`
		FROM `".DB_PREFIX."miners_data`
		WHERE `miner_id`> 0
		GROUP BY `host`
		ORDER BY RAND()
		LIMIT ".COUNT_CONFIRMED_NODES."
		");
$i=0;
while ($row = $db->fetchArray($res)) {
	$urls[$i]['url'] = $row['host'].'tools/check_node.php?block_id='.$block_id;
	$urls[$i]['user_id'] = $row['user_id'];
	$i++;
}

$result = m_curl ($urls, '', '', '', 10, true, false);
debug_print("result=".print_r_hex($result), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);


$status = array();
foreach ($result as $user_id=>$answer) {
	if ($answer!=$hash)
		@$status[0]++;
	else
		@$status[1]++;
}

if (isset($status[1])) {
	$db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		INSERT INTO `" . DB_PREFIX . "confirmations` (
			`block_id`,
			`good`,
			`bad`,
			`time`
		)
		VALUES (
			{$block_id},
			{$status[1]},
			{$status[0]},
			" . time() . "
		)
		ON DUPLICATE KEY UPDATE  `good` = {$status[1]}, `bad` = {$status[0]}, `time` = " . time() . "
		");
}

?>
