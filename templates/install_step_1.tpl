<!-- container -->
<div class="container">

	<ul class="nav nav-tabs">
		<li><a href="#" onclick="fc_navigate('install_step_0')">Step 0</a></li>
		<li class="active"><a href="#" onclick="fc_navigate('install_step_1')">Step 1</a></li>
	</ul>

	<?php echo $lng['install_needed']?>:<br>
	PHP >5.2.4 - <?php echo $tpl['php_version'] ?><br>
	Mysql >5.0 - (<?php echo $lng['install_mysql_ver']?>)<br>
	PHP-curl - <?php echo $tpl['php_curl'] ?><br>
	PHP-gd2 - <?php echo $tpl['php_gd'] ?><br>
	PHP-zip - <?php echo $tpl['php_zip'] ?><br>
	PHP-json - <?php echo $tpl['php_json'] ?><br>
	PHP-mcrypt - <?php echo $tpl['php_mcrypt'] ?><br>
	PHP-mysqli - <?php echo $tpl['php_mysqli'] ?><br>
	32-bit PHP - <?php echo $tpl['php_32'] ?><br>

		<button class="btn btn-outline btn-primary" onclick="fc_navigate('install_step_2')" <?php echo ($tpl['php_version']=='ok' && $tpl['php_curl']=='ok' && $tpl['php_gd']=='ok'  && $tpl['php_zip']=='ok'  && $tpl['php_json']=='ok' && $tpl['php_mcrypt']=='ok' && $tpl['php_mysqli']=='ok' && $tpl['php_32']=='ok')?'':'disabled="disabled"'; ?>><?php echo $lng['next']?></button>


</div>