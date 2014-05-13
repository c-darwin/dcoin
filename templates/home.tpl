<script>
	var i=0;
	function doPoll() {
		$.post('ajax/my_notice.php', function(data) {
			poll_time = Date.now();
			i++;
			$('#main_status').html(data.main_status);
			$('#account_status').text(data.account_status);
			$('#cur_block_id').text(data.cur_block_id);
			$('#connections').text(data.connections);
			$('#time_last_block').text(data.time_last_block);
			if (data.alert == 1 && i%2 == 0)
				$('#bar_alert').css("display", "block");
			else if (data.alert == 1 && i%2 != 0)
				$('#bar_alert').css("display", "none");

			setTimeout(doPoll,1000);

		}, 'json' );
	}

	if (Date.now() - poll_time > 10000)
		doPoll();
</script>

<!-- container -->
<div class="container">


	<legend><h2>Home</h2></legend>
	<div id="message"></div>
	
	<div id="generate">

		<div id="main_status"><?php echo $tpl['my_notice']['main_status']?></div>

		<p><?php echo $lng['account_status']?>: <span id="account_status"><?php echo $tpl['my_notice']['account_status'];?></span> <?php echo !empty($_SESSION['restricted'])?'restricted':'' ?> <?php echo defined('POOL_ADMIN')?'(Pool admin)':'' ?></p>

		<?php echo $lng['home_text']?>
		<br>
		<p><?php echo $tpl['script_version']?></p>
		<p>Status: <?php echo $tpl['demons_status']?></p>
		<p><?php echo $lng['number_of_blocks']?>: <span id="cur_block_id"><?php echo $tpl['my_notice']['number_of_blocks']?></span></p>
		<p>Connections: <span id="connections"><?php echo $tpl['my_notice']['connections']?></span></p>
		<p>User_id: <span id="user_id"><?php echo $user_id?></span></p>
		<p><?php echo $lng['time_last_block']?>: <span id="time_last_block"><?php echo $tpl['my_notice']['time_last_block']?></span></p>

		<?php if (@$_SESSION['ADMIN']==1) {?>
		<button type="button" class="btn" data-toggle="button"  onclick="$.post('admin/content.php', { tpl_name: 'index', parameters: '' },
	              function(data) {
	              $('.fc_content').html( data );
	              }, 'html');" style="margin-left:30px">admin</button>
		<?php } ?>
	</div>

	<?php require_once( 'signatures.tpl' );?>

    
</div>
<!-- /container -->