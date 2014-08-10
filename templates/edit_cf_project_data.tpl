<!-- container -->
<div class="container">

<script>

$('#save').bind('click', function () {

	$("#change_host").css("display", "none");
	$("#sign").css("display", "block");
	if (!$("#description_img").val()) $("#description_img").val(0);
	if (!$("#picture").val()) $("#picture").val(0);
	if (!$("#video_type").val()) $("#video_type").val(0);
	if (!$("#video_url_id").val()) $("#video_url_id").val(0);
	if (!$("#news_img").val()) $("#news_img").val(0);
	if (!$("#links").val()) $("#links").val(0);

	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#project_id").val()+','+$("#lang").val()+','+$("#blurb_img").val()+','+$("#description_img").val()+','+$("#picture").val()+','+$("#video_type").val()+','+$("#video_url_id").val()+','+$("#news_img").val()+','+$("#links").val());
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
});

$('#send_to_net').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'project_id' : $('#project_id').val(),
			'lang' : $('#lang').val(),
			'blurb_img' : $('#blurb_img').val(),
			'description_img' : $('#description_img').val(),
			'picture' : $('#picture').val(),
			'video_type' : $('#video_type').val(),
			'video_url_id' : $('#video_url_id').val(),
			'news_img' : $('#news_img').val(),
			'links' : $('#links').val(),
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
		}, function (data) {
				fc_navigate ('add_cf_project_data', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
		}
	);
});

</script>

	<legend><h2><?php echo $lng['new_cf_project_title']?></h2></legend>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	
	<div id="change_host">

		<form>
			<fieldset>
				<input type="text" placeholder="project_id" id="project_id" value=""><br>
				<input type="text" placeholder="lang" id="lang" value=""><br>
				<input type="text" placeholder="blurb_img" id="blurb_img" value=""><br>
				<input type="text" placeholder="description_img" id="description_img" value=""><br>
				<input type="text" placeholder="picture" id="picture" value=""><br>
				<input type="text" placeholder="video_type" id="video_type" value=""><br>
				<input type="text" placeholder="video_url_id" id="video_url_id" value=""><br>
				<input type="text" placeholder="news_img" id="news_img" value=""><br>
				<input type="text" placeholder="links" id="links" value=""><br>
				<button type="submit" class="btn" id="save"><?php echo $lng['next']?></button>
			</fieldset>
		</form>

		<p><span class="label label-important"><?php echo $lng['limits']?></span> <?php echo $tpl['limits_text']?></p>

	</div>

	<?php require_once( 'signatures.tpl' );?>


</div>
<!-- /container -->