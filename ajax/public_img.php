<?php
define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

header("Access-Control-Allow-Origin: *");
$img = $_GET['img'];
if ( !preg_match ("/^[a-z0-9_]+\.jpg$/D", $img))
	die('incorrect img');

if (file_exists(ABSPATH . 'public/'.$img)) {
	die('ok');
}
else {
	die('no');
}

?>