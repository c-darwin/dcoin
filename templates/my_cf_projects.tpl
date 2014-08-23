<script>

$('#save').bind('click', function () {

	$("#change_host").css("display", "none");
	$("#sign").css("display", "block");
	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#currency_id").val()+','+$("#amount").val()+','+$("#end_time").val()+','+$("#latitude").val()+','+$("#longitude").val()+','+$("#category_id").val());
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
});

$('#send_to_net').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'currency_id' : $('#currency_id').val(),
			'amount' : $('#amount').val(),
			'end_time' : $('#end_time').val(),
			'latitude' : $('#latitude').val(),
			'longitude' : $('#longitude').val(),
			'category_id' : $('#category_id').val(),
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
		}, function (data) {
				fc_navigate ('new_cf_project', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			}
	);
} );

$('#new_cf_project').bind('click', function () {

	$('#page-wrapper').spin();
	$( "#dc_content" ).load( "content.php", { tpl_name: "new_cf_project" }, function() {
		$.getScript("https://maps.googleapis.com/maps/api/js?sensor=false&callback=initialize", function(){
			$('#page-wrapper').spin(false);
		});
	});

});


</script>
<style>
.mlng li{padding-right:5px; padding-left: 0px}
</style>
<link href="css2/cf.css" rel="stylesheet">

	<h1 class="page-header"><?php echo $lng['my_cf_projects_title']?></h1>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

	<button type="button" class="btn btn-primary" data-toggle="button"  id="new_cf_project"><?php echo $lng['add_new_project']?></button><br><br>

	<div>

		<?php
		if ($tpl['projects'])
		foreach ($tpl['projects'] as $project_id=>$data) {
			?>
			<div class="well project-card" style="float:left; margin-right:20px">
				<a href="#" onclick="fc_navigate('cf_page_preview', {'only_project_id':<?php echo $data['id']?><?php echo $data['lang_id']?", 'lang_id':{$data['lang_id']}":""?>})"><img src="<?php echo $data['blurb_img']?>" style="width:200px; height:310px"></a>
			<ul class="list-inline mlng" style="margin-left:0px; margin-top:5px; padding-left: 0px">
			<?php
			foreach ($data['lang'] as $data_id=>$lang_id)
				echo "<li><a href=\"#\" onclick=\"fc_navigate('add_cf_project_data', {'id':'{$data_id}'})\">{$tpl['cf_lng'][$lang_id]}</a></li> ";
			?>
			</ul>
			<p><a href="#" onclick="fc_navigate('add_cf_project_data', {'project_id':'<?php echo $project_id?>'})"><?php echo $lng['add_description']?></a></p>
			<p><?php echo $lng['currency']?>: <?php echo $data['project_currency_name']?></p>
			<p><?php echo $lng['project_id']?>: <?php echo $data['id']?></p>
			<p><?php echo $lng['category']?>: <?php echo $lng['cf_category'][$data['category_id']]?> <a href="#" onclick="fc_navigate('cf_project_change_category', {'project_id':'<?php echo $project_id?>'})"<i class="fa  fa-pencil fa-fw"></i></a></p>
			<div>
				<div class="card-location" style="margin-top:10px;font-size: 13px; color: #828587;"><i class="fa  fa-map-marker  fa-fw"></i> <?php echo "{$data['country']},{$data['city']}"?></div>
				<div class="progress" style="height:5px; margin-top:10px; margin-bottom:10px"><div class="progress-bar progress-bar-success" style="width: <?php echo $data['pct']?>%;"></div></div>
				<div class="card-bottom">
					<div style="float:left; overflow:auto; padding-right:10px"><h5><?php echo $data['pct']?>%</h5>funded</div>
					<div style="float:left; overflow:auto; padding-right:10px"><h5><?php echo $data['funding_amount']?> D<?php echo $tpl['currency_list'][$data['currency_id']]?> </h5>pledged</div>
					<div style="float:left; overflow:auto;"><h5><?php echo $data['days']?></h5>days to go</div>
				</div>
			</div>
			<div class="clearfix"></div>
			<p style="margin-top:5px; margin-bottom: 0px"><a href="#" onclick="fc_navigate('del_cf_project', {'del_id':<?php echo $project_id?>})"><?php echo $lng['delete_project']?></a></p>
			</div>
			<?php
			}
			?>


	</div>

	<?php require_once( 'signatures.tpl' );?>
