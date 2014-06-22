<!-- container -->
<div class="container">
<script>
	$('#next').bind('click', function () {
		$.post( 'content.php', {
			'tpl_name' : 'install_step_2_1',
			'mysql_host' : $('#mysql_host').val(),
			'mysql_port' : $('#mysql_port').val(),
			'mysql_prefix' : $('#mysql_prefix').val(),
			'mysql_db_name' : $('#mysql_db_name').val(),
			'mysql_username' : $('#mysql_username').val(),
			'mysql_password' : $('#mysql_password').val(),
			'pool_data' : $('#pool_data').val(),
			'pool_admin_user_id' : $('#pool_admin_user_id').val()
		}, function (data) { $('.fc_content').html( data ); }, 'html' );
	} );
</script>

	<ul class="nav nav-tabs">
		<li><a href="#" onclick="fc_navigate('install_step_0')">Step 0</a></li>
		<li><a href="#" onclick="fc_navigate('install_step_1')">Step 1</a></li>
		<li class="active"><a href="#" onclick="fc_navigate('install_step_2')">Step 2</a></li>
	</ul>

	<strong><?php echo $lng['install_mysql_setting']?></strong>
	<?php
	if (isset($tpl['error']))
		for ($i=0; $i<sizeof($tpl['error']); $i++)
			echo "<p style=\"color:#ff0000\">{$tpl['error'][$i]}</p>";
	?>
<table>
	<tr><td>host</td><td><input type="text" id="mysql_host" value="<?php echo $tpl['mysql_host']?>"></td></tr>
	<tr><td>port</td><td><input type="text" id="mysql_port" value="<?php echo $tpl['mysql_port']?>"></td></tr>
	<!--<tr><td>prefix</td><td><input type="text" id="mysql_prefix" value="<?php echo $tpl['mysql_prefix']?>"></td></tr>-->
	<tr><td>db_name</td><td><input type="text" id="mysql_db_name" value="<?php echo $tpl['mysql_db_name']?>"></td></tr>
	<tr><td>username</td><td><input type="text" id="mysql_username" value="<?php echo $tpl['mysql_username']?>"></td></tr>
	<tr><td>password</td><td><input type="password" id="mysql_password" value=""></td></tr>
	<tr><td>pool_data (<?php echo $lng['if_present'] ?>)</td><td><textarea id="pool_data"></textarea></td></tr>
	<tr><td>pool_admin_user_id (<?php echo $lng['if_present'] ?>)</td><td><input type="text" id="pool_admin_user_id" value=""></td></tr>
	<tr><td colspan="2"><button id="next" class="btn btn-success"><?php echo $lng['next']?></button></td></tr>




</table>
<?php echo $lng['install_mysql_warning']?>

</div>