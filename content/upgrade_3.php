<?php

// Формируем контент для подписи
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, 'SELECT host FROM `'.DB_PREFIX.'my_table' );
$row = $db->fetchArray($res);
$tpl['data'] = $row;

require_once( ABSPATH . 'templates/upgrade_3.tpl' );

?>