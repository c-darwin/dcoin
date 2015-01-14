<script>
var	json_data = '';

$('#save').bind('click', function () {

	var data = '';
	$("tr", $("#conditions_div")).each(function() {

		var id = $(this).attr('id');
		if (typeof id != "undefined") {
			if ( $('#check_'+id).is(":checked") ) {

				var pct = $('#pct_'+id).val();
				var pct0 = pct.split(".");
				if (typeof(pct0[1])!=='undefined') {
					if (pct0[1].length < 2)
						var pct = $('#pct_' + id).val() + '0';
				}
				else {
					var pct = $('#pct_' + id).val() + '.00';
				}
				data=data+'"'+id+'":';
				data=data+'["'+$('#min_amount_'+id).val()+'",';
				data=data+'"'+$('#max_amount_'+id).val()+'",';
				data=data+'"'+$('#min_commission_'+id).val()+'",';
				data=data+'"'+$('#max_commission_'+id).val()+'",';
				data=data+'"'+pct+'"],';
			}
		}
	} );
	json_data = '{'+data.substr(0, data.length-1)+'}';
	console.log(json_data);

	$("#change_commission").css("display", "none");
	$("#sign").css("display", "block");
	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+json_data+','+$('#url').val());
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
});

$('#send_to_net').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'conditions' : json_data,
			'url' : $('#url').val(),
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
		}, function (data) {
			fc_navigate ('arbitration_arbitrator', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
		}
	);

} );

</script>
<style>
	.input-group-addon{width: 100px}
	th{text-align: center;}
</style>
<div id="main_div">
	<h1 class="page-header"><?php echo $lng['arbitration']?></h1>
	<ol class="breadcrumb">
		<li><a href="#wallets_list"><?php echo $lng['wallets']?></a></li>
		<li><a href="#arbitration"><?php echo $lng['arbitration']?></a></li>
		<li><a href="#arbitration_arbitrator"><?php echo $lng['i_arbitrator']?></a></li>
		<li class="active"><?php echo $lng['my_conditions']?></li>
	</ol>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

	<div id="change_commission" style="<?php echo $tpl['pending_tx']?'display:none':'display:block'?>">

		<table class="table" style="width: 600px;" id="conditions_div">

			<tr><th colspan="1" rowspan="2" style="vertical-align: middle;"><?php echo $lng['currency']?></th><th colspan="3" rowspan="1"><?php echo $lng['commission']?></th><th colspan="2" rowspan="1"><?php echo $lng['considered_transaction_amount']?></th></tr>

			<tr><th>%</th><th><?php echo $lng['min']?></th><th><?php echo $lng['max']?></th><th><?php echo $lng['min']?></th><th><?php echo $lng['max']?></th></tr>

			<?php
			 foreach($tpl['commission'] as $currency_id=>$data) {
				 $checked = $tpl['conditions'][$currency_id]?'checked':'';
				 echo "<tr id='{$currency_id}'><td><input type='checkbox' style='display:inline-block' name='check' id='check_{$currency_id}' {$checked}> d{$tpl['currency_list'][$currency_id]}</td>";
				 $pct = $tpl['conditions'][$currency_id][4]?$tpl['conditions'][$currency_id][4]:'0.1';
				 $min_commission = $tpl['conditions'][$currency_id][2]?$tpl['conditions'][$currency_id][2]:'0.01';
				 $max_commission = $tpl['conditions'][$currency_id][3]?$tpl['conditions'][$currency_id][3]:'0';
				 $min_amount = $tpl['conditions'][$currency_id][0]?$tpl['conditions'][$currency_id][0]:'0.01';
				 $max_amount = $tpl['conditions'][$currency_id][1]?$tpl['conditions'][$currency_id][1]:'0';
				 echo "<td><input id='pct_{$currency_id}' class='form-control' value='{$pct}' type='text'></td>";
				 echo "<td><input id='min_commission_{$currency_id}' class='form-control' value='{$min_commission}' type='text'></td>";
				 echo "<td><input id='max_commission_{$currency_id}' class='form-control' type='text' value='{$max_commission}'></td>";
				 echo "<td><input id='min_amount_{$currency_id}' class='form-control' type='text' value='{$min_amount}'></td>";
				 echo "<td><input id='max_amount_{$currency_id}' class='form-control' type='text' value='{$max_amount}'></td>";
				 echo "</tr>";
			}
			 ?>
		</table><br>
		<strong>Url</strong><br>
		<input id='url' class='form-control' type='text' value="0"><Br>

		<button class="btn btn-outline btn-primary" id="save"><?php echo $lng['send_to_net']?></button>
		<br><br>

	</div>

	<div id="pending" style="<?php echo !$tpl['pending_tx']?'display:none':'display:block'?>">
		<div class="alert alert-success">
			<?php echo $lng['being_processed']?>
		</div>
	</div>

	<?php require_once( 'signatures.tpl' );?>

</div>