<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['ver'] = file_get_contents(ABSPATH.'version');

require_once( ABSPATH . 'templates/pool_tech_works.tpl' );

?>