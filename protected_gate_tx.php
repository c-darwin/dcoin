<?php
/*
 * Пересылаем тр-ию, полученную по локальной сети, конечному ноду, указанному в первых 100 байтах тр-ии
 * */
function string_shift (&$string, $index = 1) {

	$substr = substr($string, 0, $index);
	$string = substr($string, $index);
	return $substr;
}

function decode_length (&$string) {

	$length = ord(string_shift($string));
	if ( $length & 0x80 ) {
		$length&= 0x7F;
		$temp = string_shift($string, $length);
		list(, $length) = unpack('N', substr(str_pad($temp, 4, chr(0), STR_PAD_LEFT), -4));
	}
	return $length;
}

$ip = $_SERVER['REMOTE_ADDR'];
if (substr($ip, 0, 7)!='192.168' && substr($ip, 0, 3)!='127' && substr($ip, 0, 3)!='10.' )
	die('error ip '.$ip);

$encrypted_data = $_POST['data'];
$size = decode_length($encrypted_data);
$host = string_shift($encrypted_data, $size);

if (!preg_match('/^https?:\/\/[0-9a-z\_\.\-\/:]{1,100}[\/]$/iD', $host))
	die('error host '.$host);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $host.'gate_tx.php');
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 600);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'data='.urlencode($encrypted_data));
curl_exec($ch);
curl_close($ch);

?>