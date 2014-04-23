<?php
if (!defined('DC')) die("!defined('DC')");

// инвайты показываем только, если юзер уже майнер
$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, 'SELECT `user_id`, `miner_id` FROM `'.DB_PREFIX.'my_table` ', 'fetch_array' );

if ($data['miner_id']) {

	$tpl['my_invites'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `invite`,
						 `used_user_id`
			FROM `".DB_PREFIX."my_invites`
			", 'list', array('invite', 'used_user_id') );


	$hashed_invites = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `public_hash`,
						 `user_id`
			FROM `".DB_PREFIX."invites`
			WHERE `owner_user_id` = {$data['user_id']}
			", 'list', array('public_hash', 'user_id') );

	foreach ($tpl['my_invites'] as $invite=>$used_user_id) {

		// если появился новенький юзер, взявший наш инвайт
		$new_used_user_id =  $hashed_invites[hextobin(hash('sha256', $invite))];
		if ( !$used_user_id &&  $new_used_user_id) {
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					UPDATE `".DB_PREFIX."my_invites`
					SET `used_user_id` = {$new_used_user_id}
					WHERE `invite` = '{$invite}'
					");
			$tpl['my_invites'][$invite] = $new_used_user_id;
		}

	}

}

require_once( ABSPATH . 'templates/invites.tpl' );

?>