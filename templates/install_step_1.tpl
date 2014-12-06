<style>
	#page-wrapper{
		margin: 0px 10% 0px 10%;
		border: 1px solid #E7E7E7;
		min-height: 550px;
	}
	#wrapper{height: 100%;}
	#dc_content{
		height: 550px;
		vertical-align: middle;
	}
</style>
<div style="max-width: 600px; margin: auto; margin-top: 50px">


	<ul class="nav nav-tabs" style="margin-bottom: 20px">
		<li><a href="#install_step_0">Step 0</a></li>
		<li class="active"><a href="#install_step_1">Step 1</a></li>
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