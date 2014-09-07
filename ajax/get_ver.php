<?php

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
header("Access-Control-Allow-Origin: *");
print 'v'.file_get_contents(ABSPATH . 'version');

?>