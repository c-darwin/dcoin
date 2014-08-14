<div class="container">

	<ul class="nav nav-tabs">
		<li><a href="#" onclick="fc_navigate('install_step_0')">Step 0</a></li>
		<li><a href="#" onclick="fc_navigate('install_step_1')">Step 1</a></li>
		<li><a href="#" onclick="fc_navigate('install_step_2')">Step 2</a></li>
		<li><a href="#" onclick="fc_navigate('install_step_2_1')">Step 3</a></li>
		<li><a href="#" onclick="fc_navigate('install_step_3')">Step 4</a></li>
		<li><a href="#" onclick="fc_navigate('install_step_4')">Step 5</a></li>
		<li><a href="#" onclick="fc_navigate('install_step_5')">Step 6</a></li>
		<li class="active"><a href="#" onclick="fc_navigate('install_step_6')">Step 7</a></li>
	</ul>

	<?php echo $lng['install_compete']?> <br><br>

	<a href="#" id="show_login" role="button"><?php echo $lng['login_to_your_account']?></a>
	<?php
	require_once( ABSPATH . 'templates/modal.tpl' );
	echo str_ireplace('myModal', 'myModalLogin', $modal);
	?>

	<script>
		$('#myModal').remove();
		$('#show_login').bind('click', function () {
			$('#myModalLogin').modal({ backdrop: 'static' });
		});
	</script>
</div>
