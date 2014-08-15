<!-- container -->
<div class="container">
<script>
	$('#next').bind('click', function () {
		$.post( 'content.php', {
			'tpl_name' : 'install_step_3',
			'php_path' : $('#php_path').val()
		}, function (data) { $('#dc_content').html( data ); }, 'html' );
	} );
</script>

	<ul class="nav nav-tabs">
		<li><a href="#" onclick="fc_navigate('install_step_0')">Step 0</a></li>
		<li><a href="#" onclick="fc_navigate('install_step_1')">Step 1</a></li>
		<li><a href="#" onclick="fc_navigate('install_step_2')">Step 2</a></li>
		<li class="active"><a href="#" onclick="fc_navigate('install_step_2_1')">Step 3</a></li>
	</ul>

	<strong><?php echo $lng['install_php_path']?></strong>
	<?php
	if (isset($tpl['error']))
		for ($i=0; $i<sizeof($tpl['error']); $i++)
			echo "<p style=\"color:#ff0000\">{$tpl['error'][$i]}</p>";
	?>
<table>
<tr><td>php_path</td><td><input class="form-control" type="text" id="php_path" value="<?php echo $tpl['php_path']?>"></td></tr>
<tr><td colspan="2"><button id="next" class="btn btn-outline btn-primary"><?php echo $lng['next']?></button></td></tr>
</table>

</div>