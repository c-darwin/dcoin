<script>
	$('#save').bind('click', function () {

		<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#main_data").css("display", "none");	$("#sign").css("display", "block");' ?>

		$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#to_user_id").val()+','+$("#amount").val()+','+$("#currency_id").val()+','+$("#pct").val());
		doSign();
		<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
	});

	$('#send_to_net').bind('click', function () {

		$.post( 'ajax/save_queue.php', {
				'type' : '<?php echo $tpl['data']['type']?>',
				'time' : '<?php echo $tpl['data']['time']?>',
				'user_id' : '<?php echo $tpl['data']['user_id']?>',
				'amount' : $('#amount').val(),
				'currency_id' : $('#currency_id').val(),
				'to_user_id' : $('#to_user_id').val(),
				'pct' : $('#pct').val(),
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
	<h1 class="page-header"><?php echo $lng['credit_creation']?></h1>
	<ol class="breadcrumb">
		<li><a href="#"onclick="fc_navigate('wallets_list')"><?php echo $lng['wallets']?></a></li>
		<li><a href="#"onclick="fc_navigate('credits')"><?php echo $lng['credits']?></a></li>
		<li class="active"><?php echo $lng['credit_creation']?></li>
	</ol>

	<div id="main_data">
		<div class="row" style="padding:0 15px">
			<div class="alert alert-danger">
				<?php echo $lng['new_credit_alert']?>
			</div>
		</div>
		<div class="form-horizontal">
				<div class="form-group">
					<label class="col-md-4 control-label" for="amount"><?php echo $lng['amount']?></label>
					<div class="col-md-4">
						<div class="input-group">
							<input style="min-width: 100px" id="amount" name="amount" class="form-control" type="text">
							<div class="input-group-btn">
								<select class="form-control" id="currency_id" style="min-width: 100px">
									<?php
									foreach ($tpl['currency_list'] as $id=>$name) {
										$sel = '';
										if ($id==72)
											$sel = 'selected';
										echo "<option value='{$id}' {$sel}>D{$name}</option>";
									}
									?>
								</select>
							</div>
						</div>
						<span class="help-block"><?php echo $lng['amount_of_loan']?></span>
					</div>
				</div>

			<div class="form-group">
				<label class="col-md-4 control-label" for="to_user_id"><?php echo $lng['to']?></label>
				<div class="col-md-4">
					<input style="min-width: 100px" id="to_user_id"  class="form-control" type="text">
					<span class="help-block"><?php echo $lng['creditor_user_id']?></span>
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-4 control-label" for="pct"><?php echo $lng['credit_pct']?></label>
				<div class="col-md-4">
					<input style="min-width: 100px" id="pct" class="form-control" type="text">
					<span class="help-block"><?php echo $lng['credit_part']?></span>
				</div>
			</div>

				<div class="form-group">
					<label class="col-md-4 control-label" for="singlebutton"></label>
					<div class="col-md-4">
						<button type="button" class="btn btn-outline btn-primary" id="save"><?php echo $lng['send_to_net']?></button>
					</div>
				</div>

		</div>


	</div>

</div>

<?php require_once( 'signatures.tpl' );?>