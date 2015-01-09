<script>

	var arbitrator_enc_text = [];
	var seller_enc_text = '';

	$('#next').bind('click', function () {

		$.post( 'ajax/encrypt_comment.php', {
			'to_id' : {<?php echo "'0':{$tpl['order']['arbitrator0']},'1':{$tpl['order']['arbitrator1']},'2':{$tpl['order']['arbitrator2']},'3':{$tpl['order']['arbitrator3']},'4':{$tpl['order']['arbitrator4']}"?>},
			'type' : 'arbitration_arbitrators',
			'comment' :  $("#comment").val()
		}, function (arbitrator_enc_text_) {

			arbitrator_enc_text = arbitrator_enc_text_;

				$.post( 'ajax/encrypt_comment.php', {
					'to_id' : <?php echo $tpl['order']['seller']?>,
					'type' : 'arbitration_seller',
					'comment' :  $("#comment").val()
				}, function (seller_enc_text_) {

					seller_enc_text = seller_enc_text_;

					<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#main_data").css("display", "none");	$("#sign").css("display", "block");' ?>

					$("#for-signature").val( '<?php echo "{$tpl['data']['credit_part_type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']},{$tpl['order']['id']}"; ?>,'+arbitrator_enc_text[0]+','+arbitrator_enc_text[1]+','+arbitrator_enc_text[2]+','+arbitrator_enc_text[3]+','+arbitrator_enc_text[4]+','+seller_enc_text);
					console.log($("#for-signature").val());
					doSign();
					<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>

				});
		}, 'JSON');

	});

	$('#send_to_net').bind('click', function () {

		$.post( 'ajax/save_queue.php', {
				'type' : '<?php echo $tpl['data']['credit_part_type']?>',
				'time' : '<?php echo $tpl['data']['time']?>',
				'user_id' : '<?php echo $tpl['data']['user_id']?>',
				'order_id' : '<?php echo $tpl['order']['id']?>',
				'arbitrator_enc_text' : arbitrator_enc_text,
				'seller_enc_text' : seller_enc_text,
				'signature1': $('#signature1').val(),
				'signature2': $('#signature2').val(),
				'signature3': $('#signature3').val()
			}, function (data) {
				fc_navigate ('arbitration_buyer', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			}
		);
	});

</script>
<div id="main_div">
	<h1 class="page-header"><?php echo $lng['arbitration']?></h1>
	<ol class="breadcrumb">
		<li><a href="#wallets_list"><?php echo $lng['wallets']?></a></li>
		<li><a href="#arbitration"><?php echo $lng['arbitration']?></a></li>
		<li class="active"><?php echo $lng['i_buyer']?></li>
	</ol>

	<div id="main_data">
		<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

			<h3>Money back</h3>
			<table class="table" style="max-width: 600px">
				<tr><td>ID</td><td><?php echo $tpl['order']['id']?></td></tr>
				<tr><td><?php echo $lng['amount']?></td><td><?php echo $tpl['order']['amount']?></td></tr>
				<tr><td><?php echo $lng['seller']?></td><td><?php echo $tpl['order']['seller']?></td></tr>
				<tr><td><?php echo $lng['your_email_for_arbitrator']?></td><td><input type="text" class="form-control" id="comment"></td></tr>
			</table>
			<button type="button" class="btn btn-outline btn-primary" id="next"><?php echo $lng['send_to_net']?></button>
	</div>

</div>

<?php require_once( 'signatures.tpl' );?>
<script src="js/unixtime.js"></script>