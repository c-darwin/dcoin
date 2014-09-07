<script>
	$('#save').bind('click', function () {

		<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#main_data").css("display", "none");	$("#sign").css("display", "block");' ?>

		$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#to_user_id").val()+','+$("#credit_id").val());
		doSign();
		<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
	});

	$('#send_to_net').bind('click', function () {

		$.post( 'ajax/save_queue.php', {
				'type' : '<?php echo $tpl['data']['type']?>',
				'time' : '<?php echo $tpl['data']['time']?>',
				'user_id' : '<?php echo $tpl['data']['user_id']?>',
				'to_user_id' : $('#to_user_id').val(),
				'credit_id' : $('#credit_id').val(),
				'signature1': $('#signature1').val(),
				'signature2': $('#signature2').val(),
				'signature3': $('#signature3').val()
			}, function (data) {
				fc_navigate ('credits', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			}
		);
	});

</script>
<div id="main_div">
	<h1 class="page-header">Передача кредита</h1>
	<ol class="breadcrumb">
		<li><a href="#"onclick="fc_navigate('wallets_list')"><?php echo $lng['wallets']?></a></li>
		<li><a href="#"onclick="fc_navigate('credits')"><?php echo $lng['credits']?></a></li>
		<li class="active">Передача кредита</li>
	</ol>

	<div id="main_data">
	<div class="form-horizontal" role="form">
		<div class="form-group">
			<div class="col-md-6">
				<div class="form-group row">
					<label for="to_user_id" class="col-md-1 control-label">User_id</label>
					<div class="col-md-3">
						<input type="text" class="form-control" id="to_user_id">
					</div>
					<div class="col-md-3">
						<button type='button' class='btn btn-default' id="save">Передать</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	</div>

</div>

<?php require_once( 'signatures.tpl' );?>