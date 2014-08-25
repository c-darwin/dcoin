<?php
if (!defined('DC')) die("!defined('DC')");

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT *
		FROM `'.DB_PREFIX.MY_PREFIX.'my_comments`
		WHERE `comment` != "null"
		');
while ($row = $db->fetchArray($res)) {
	$tpl['my_comments'][] = $row;
}

require_once( ABSPATH . 'templates/vote_for_me.tpl' );

?>