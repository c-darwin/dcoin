<script>

	$('#next').bind('click', function () {

		<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#main_data").css("display", "none");	$("#sign").css("display", "block");' ?>

		$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']},{$tpl['order_id']},{$tpl['amount']}"; ?>');
		doSign();
		<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>

	});

	$('#send_to_net').bind('click', function () {

		$.post( 'ajax/save_queue.php', {
				'type' : '<?php echo $tpl['data']['type']?>',
				'time' : '<?php echo $tpl['data']['time']?>',
				'user_id' : '<?php echo $tpl['data']['user_id']?>',
				'order_id' : '<?php echo $tpl['order_id']?>',
				'amount' : '<?php echo $tpl['amount']?>',
				'signature1': $('#signature1').val(),
				'signature2': $('#signature2').val(),
				'signature3': $('#signature3').val()
			}, function (data) {
				fc_navigate ('<?php echo $tpl['redirect']?>', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			}
		);
	});

</script>
<div id="main_div">
	<h1 class="page-header"><?php echo $lng['arbitration']?></h1>
	<ol class="breadcrumb">
		<li><a href="#wallets_list"><?php echo $lng['wallets']?></a></li>
		<li><a href="#arbitration"><?php echo $lng['arbitration']?></a></li>
		<?php echo $tpl['li']?>
		<li class="active">Money back</li>
	</ol>

	<div id="main_data">
		<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

			<h3>Money back</h3>
			<table class="table" style="max-width: 600px">
				<tr><td>ID</td><td><?php echo $tpl['order_id']?></td></tr>
				<tr><td><?php echo $lng['amount']?></td><td><?php echo $tpl['amount']?></td></tr>
			</table>
			<button type="button" class="btn btn-outline btn-primary" id="next"><?php echo $lng['send_to_net']?></button>
	</div>

</div>

<?php require_once( 'signatures.tpl' );?>
<script src="js/unixtime.js"></script>