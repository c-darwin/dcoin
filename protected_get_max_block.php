<?php

$ip = $_SERVER['REMOTE_ADDR'];
if (substr($ip, 0, 7)!='192.168' )
	die('error ip '.$ip);

$host = filter_var($_REQUEST['node_host'], FILTER_SANITIZE_URL);
if (!preg_match('/^https?:\/\/[0-9a-z\_\.\-\/:]{1,100}[\/]$/iD', $host))
	die('error host');

$url = "{$host}get_max_block.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
$answer = curl_exec($ch);
curl_close($ch);
print $answer;

?>