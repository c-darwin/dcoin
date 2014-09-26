<?php
spl_autoload_register ('autoload');
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'includes/const.php' );
function autoload ($className) {
	$className = str_replace('_', '/', $className);
	$fileName = ABSPATH . 'includes/'.$className . '.php';
	require_once( $fileName) ;
}
?>