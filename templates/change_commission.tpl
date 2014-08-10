
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
				fc_navigate ('change_commission', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			}
	);

} );

</script>

	<h1 class="page-header"><?php echo $lng['change_commission_title']?></h1>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

	<div id="change_commission">

		<button type="submit" class="btn" id="save"><?php echo $lng['save']?></button><br><br>

		<table>
			<tr><th><?php echo $lng['currency']?></th><th>%</th><th><?php echo $lng['min']?></th><th><?php echo $lng['max']?></th></tr>
		<?php
		 foreach($tpl['commission'] as $currency_id=>$data) {

			print "<tr><td>{$tpl['currency_list'][$currency_id]}<input type='hidden' name='currency_id' value='{$currency_id}'></td><td><input class='span1' type='text' name='pct' value='{$data[0]}'></td><td><input class='span1' type='text' name='min' value='{$data[1]}'></td><td><input class='span1' type='text' name='max' value='{$data[2]}'></td></tr>";

		}
		 ?>
		</table>

		<p><span class="label label-important"><?php echo $lng['limits']?></span> <?php echo $tpl['limits_text']?></p>

	</div>

	<?php require_once( 'signatures.tpl' );?>

