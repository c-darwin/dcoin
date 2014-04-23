<?php

$ip = $_SERVER['REMOTE_ADDR'];
if (substr($ip, 0, 7)!='192.168' && substr($ip, 0, 3)!='127' && substr($ip, 0, 3)!='10.' )
	die('error ip '.$ip);

$host = $_REQUEST['node_host'];
if (!preg_match('/^https?:\/\/[0-9a-z\_\.\-\/:]{1,100}[\/]$/iD', $host))
	die('error host');

$block_id = $_REQUEST['id'];
if (!preg_match('/^[0-9]{1,10}$/iD', $block_id))
	die('error block_id');

$url = "{$host}get_block.php?id={$block_id}";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
$answer = curl_exec($ch);
curl_close($ch);
print $answer;

?>