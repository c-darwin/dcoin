<?php

// Формируем контент для подписи
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `user_id`,
					 `race`,
					 `country`,
					 `geolocation`,
					 `host`,
					 `face_coords`,
					 `profile_coords`,
					 `video_url_id`,
					 `video_type`
		FROM `'.DB_PREFIX.MY_PREFIX.'my_table
		');
$row = $db->fetchArray($res);
if (!$row['video_url_id'])
	$row['video_url_id'] = 'null';
if (!$row['video_type'])
	$row['video_type'] = 'null';

$tpl['data'] = $row;
$tpl['data']['type'] = 'new_miner';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;
$tpl['data']['face_hash'] = hash('sha256', hash_file('sha256', ABSPATH. 'public/'.$_SESSION['user_id'].'_user_face.jpg'));
$tpl['data']['profile_hash'] = hash('sha256', hash_file('sha256', ABSPATH. 'public/'.$_SESSION['user_id'].'_user_profile.jpg'));
$x = explode(', ', $tpl['data']['geolocation']);
$tpl['data']['latitude'] = $x[0];
$tpl['data']['longitude'] = $x[1];
// проверим, есть ли не обработанные ключи в локальной табле
$node_public_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `public_key`
		FROM `".DB_PREFIX.MY_PREFIX."my_node_keys`
		WHERE `block_id` = 0
		", 'fetch_one');

if ( !$node_public_key ) {

	//  сгенерим ключ для нода
	require_once( ABSPATH . 'phpseclib/Math/BigInteger.php');
	require_once( ABSPATH . 'phpseclib/Crypt/Random.php');
	require_once( ABSPATH . 'phpseclib/Crypt/Hash.php');
	require_once( ABSPATH . 'phpseclib/Crypt/RSA.php');

	$rsa = new Crypt_RSA();

	extract($rsa->createKey(1024));

	$publickey = clear_public_key($publickey);

	$tpl['data']['node_public_key'] = $publickey;
	//print $tpl['data']['node_public_key'];
	//exit;

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			INSERT INTO  `".DB_PREFIX.MY_PREFIX."my_node_keys` (
				`public_key`,
				`private_key`
			)
			VALUES (
				0x{$publickey},
				'{$privatekey}'
			)");
}
else {
	list(, $tpl['data']['node_public_key']) = unpack( "H*", $node_public_key );
}

require_once( ABSPATH . 'templates/upgrade_5.tpl' );

?>