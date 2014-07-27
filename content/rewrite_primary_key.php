<?php
/*
 * Если пользователь поднял свою ноду, указал ключ, но ключ уже кто-то успел занять, то нужно записать новый ключ
 * */
if (!defined('DC')) die("!defined('DC')");

require_once( ABSPATH . 'templates/rewrite_primary_key.tpl' );

?>