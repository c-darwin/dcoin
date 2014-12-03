<script>
	$('#save').bind('click', function () {

		<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#main_data").css("display", "none");	$("#sign").css("display", "block");' ?>

		$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#credit_id").val());
		doSign();
		<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
	});

	$('#send_to_net').bind('click', function () {

		$.post( 'ajax/save_queue.php', {
				'type' : '<?php echo $tpl['data']['type']?>',
				'time' : '<?php echo $tpl['data']['time']?>',
				'user_id' : '<?php echo $tpl['data']['user_id']?>',
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
	<h1 class="page-header"><?php echo $lng['removal_of_the_loan']?></h1>
	<ol class="breadcrumb">
		<li><a href="#wallets_list"><?php echo $lng['wallets']?></a></li>
		<li><a href="#credits"><?php echo $lng['credits']?></a></li>
		<li class="active"><?php echo $lng['removal_of_the_loan']?></li>
	</ol>

	<div id="main_data">

		<div class="form-horizontal">

			<div class="form-group">
				<label class="col-md-4 control-label" for="singlebutton"><?php echo $lng['del_credit_confirm']?></label>
				<div class="col-md-4">
					<button type="button" class="btn btn-danger" id="save"><i class="fa fa-trash-o fa-lg"></i> <?php echo $lng['delete']?></button>
				</div>
			</div>

		</div>

		<input type="hidden" id="credit_id" value="<?php echo $tpl['credit_id']?>">


	</div>

</div>

<?php require_once( 'signatures.tpl' );?>