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

<script>
	$('#next').bind('click', function () {
		$.post( 'content.php', {
			'tpl_name' : 'install_step_3',
			'php_path' : $('#php_path').val()
		}, function (data) { $('#dc_content').html( data ); }, 'html' );
	} );
</script>

	<ul class="nav nav-tabs" style="margin-bottom: 20px">
		<li><a href="#install_step_0">Step 0</a></li>
		<li><a href="#install_step_1">Step 1</a></li>
		<li><a href="#install_step_2">Step 2</a></li>
		<li class="active"><a href="#install_step_2_1">Step 3</a></li>
	</ul>

	<strong><?php echo $lng['install_php_path']?></strong>
	<?php
	if (isset($tpl['error']))
		for ($i=0; $i<sizeof($tpl['error']); $i++)
			echo "<p style=\"color:#ff0000\">{$tpl['error'][$i]}</p>";
	?>

<input class="form-control" type="text" id="php_path" value="<?php echo $tpl['php_path']?>">
	<br>
<button id="next" class="btn btn-outline btn-primary"><?php echo $lng['next']?></button>

</div>