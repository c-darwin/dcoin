<script>
	$(document).ready(function() {
		$( "#progress_bar" ).load( "ajax/progress_bar.php");
	});
</script>

<script>

var	json_data = '';

$('#save').bind('click', function () {

	var data = '';
	$("input[type=text],input[type=hidden]", $("#change_commission")).each(function(){
		if ($(this).attr('name')=='currency_id')
			data=data+'"'+$(this).val()+'":';
		if ($(this).attr('name')=='pct')
			data=data+'['+$(this).val()+',';
		if ($(this).attr('name')=='min')
			data=data+''+$(this).val()+',';
		if ($(this).attr('name')=='max')
			data=data+''+$(this).val()+'],';
	} );
	json_data = '{'+data.substr(0, data.length-1)+'}';

	$("#change_commission").css("display", "none");
	$("#sign").css("display", "block");
	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+json_data);
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
});

$('#send_to_net').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'commission' : json_data,
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
		}, function (data) {
				fc_navigate ('<?php echo $tpl['navigate']?>', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			}
	);

} );

$("#main_div input").width( 60 );
$("#main_div button").addClass( "btn-outline btn-primary" );

if ( $('#key').text().length < 256 ) {
	$('#myModal').modal({ backdrop: 'static' });
}

$('#show_list').bind('click', function (e) {

	$("#commission_div").css("display", "block");
	e.preventDefault();
	e.stopPropagation();
});
</script>
<style>
	.input-group-addon{width: 100px}
</style>
<div id="main_div">
<h1 class="page-header"><?php echo $lng['change_commission_title']?></h1>
<ol class="breadcrumb">
	<li><a href="#mining_menu"><?php echo $lng['mining'] ?></a></li>
	<li class="active"><?php echo $lng['change_commission_title'] ?></li>
</ol>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

	<div id="change_commission">

		<button type="submit" class="btn" id="save"><?php echo $lng['send_to_net']?></button><br><br>
		<a href="#" id="show_list"><?php echo $lng['change']?></a>

		<table class="table" style="width: 500px; display: none" id="commission_div">
			<tr><th><?php echo $lng['currency']?></th><th style="text-align: center">%</th><th style="text-align: center"><?php echo $lng['min']?></th><th style="text-align: center"><?php echo $lng['max']?></th></tr>
		<?php
		 foreach($tpl['commission'] as $currency_id=>$data) {

			print "<tr><td>{$tpl['currency_list'][$currency_id]}<input type='hidden' name='currency_id' value='{$currency_id}'></td>";
			 print '<td><div class="input-group"><input id="pct" name="pct" class="form-control" value='.$data[0].' type="text"><span class="input-group-addon">max: '.$tpl['config']['commission'][$currency_id][0].'</span></div></td>';
			 print '<td><div class="input-group"><input id="min" name="min" class="form-control" value='.$data[1].' type="text"><span class="input-group-addon">max: '.$tpl['config']['commission'][$currency_id][1].'</span></div></td>';
			print " <td><input class='form-control' type='text' name='max' value='{$data[2]}'></td></tr>";

		}
		 ?>
		</table>

	</div>

	<?php require_once( 'signatures.tpl' );?>

</div>