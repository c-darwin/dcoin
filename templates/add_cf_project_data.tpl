<script>

$('#save').bind('click', function () {

	<?php echo !defined('SHOW_SIGN_DATA')?'':'$("#change_host").css("display", "none");	$("#sign").css("display", "block");' ?>

	if (!$("#blurb_img").val()) $("#blurb_img").val(0);
	if (!$("#head_img").val()) $("#head_img").val(0);
	if (!$("#description_img").val()) $("#description_img").val(0);
	if (!$("#picture").val()) $("#picture").val(0);
	if (!$("#video_type").val()) $("#video_type").val(0);
	if (!$("#video_url_id").val()) $("#video_url_id").val(0);
	if (!$("#news_img").val()) $("#news_img").val(0);
	if (!$("#links").val()) $("#links").val(0);
	if($("#hide").is(':checked'))
		var hide = '1';
	else
		var hide = '0';

	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"; ?>,'+$("#project_id").val()+','+$("#lang_id").val()+','+$("#blurb_img").val()+','+$("#head_img").val()+','+$("#description_img").val()+','+$("#picture").val()+','+$("#video_type").val()+','+$("#video_url_id").val()+','+$("#news_img").val()+','+$("#links").val()+','+hide);
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
});

$('#send_to_net').bind('click', function () {

	if($("#hide").is(':checked'))
		var hide = '1';
	else
		var hide = '0';
	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'project_id' : $('#project_id').val(),
			'lang_id' : $('#lang_id').val(),
			'blurb_img' : $('#blurb_img').val(),
			'head_img' : $('#head_img').val(),
			'description_img' : $('#description_img').val(),
			'picture' : $('#picture').val(),
			'video_type' : $('#video_type').val(),
			'video_url_id' : $('#video_url_id').val(),
			'news_img' : $('#news_img').val(),
			'links' : $('#links').val(),
			'hide' : hide,
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
		}, function (data) {
				fc_navigate ('my_cf_projects', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
		}
	);
});

</script>

<h1 class="page-header"><?php echo $tpl['id']?$lng['edit_cf_project_data_title']:$lng['new_cf_project_data_title']?></h1>
<ol class="breadcrumb">
	<li><a href="#">CrowdFunding</a></li>
	<li><a href="#"onclick="fc_navigate('my_cf_projects')"><?php echo $lng['my_cf_projects_title']?></a></li>
	<li class="active"><?php echo $tpl['id']?$lng['edit_cf_project_data_title']:$lng['new_cf_project_data_title']?> <?php echo $tpl['cf_currency_name']?></li>
</ol>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>
	
	<div id="change_host">

		<form class="form-horizontal" target="_blank" method="post" action="content.php">
			<fieldset>

				<input type="hidden" name="project_id" id="project_id" value="<?php echo $tpl['project_id']?>"><br>
				<input type="hidden" name="tpl_name" value="cf_page_preview"><br>

				<div class="form-group">
					<label class="col-md-4 control-label" for="lang_id"><?php echo $lng['language']?></label>
					<div class="col-md-4">
						<?php
						if (isset($tpl['cf_data']['lang_id']))
							echo "<p class=\"form-control-static\">{$tpl['cf_lng'][$tpl['cf_data']['lang_id']]}</p><input type=\"hidden\" id=\"lang_id\" name=\"lang_id\" value=\"{$tpl['cf_data']['lang_id']}\">";
						else {
						?>
							<select id="lang_id" name="lang_id" class="form-control">
								<?php
								foreach ($tpl['cf_lng'] as $id=>$name)
								echo "<option value='{$id}'>{$name}</option>";
								?>
							</select>
							<span class="help-block"><?php echo $lng['language_description']?></span>
							<?php
						}
						?>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-4 control-label" for="hide"><?php echo $lng['cf_hide']?></label>
					<div class="col-md-4">
						<input id="hide" name="hide" class="form-control" type="checkbox" <?php echo (@$tpl['cf_data']['hide'])?'checked':'' ?>>
						<span class="help-block"><?php echo $lng['cf_hide_info']?></span>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-4 control-label" for="blurb_img"><?php echo $lng['image_directory']?></label>
					<div class="col-md-4">
						<input id="blurb_img" name="blurb_img" class="form-control" type="text" maxlength="50" value="<?php echo @$tpl['cf_data']['blurb_img']?>">
						<span class="help-block"><?php echo $lng['cf_resolution1'].$lng['cf_url_size']?></span>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-4 control-label" for="blurb_img"><?php echo $lng['page_header']?></label>
					<div class="col-md-4">
						<input id="head_img" name="head_img" class="form-control" type="text" maxlength="50" value="<?php echo @$tpl['cf_data']['head_img']?>">
						<span class="help-block"><?php echo $lng['cf_resolution2'].$lng['cf_url_size']?></span>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-4 control-label" for="picture"><?php echo $lng['video_or_picture']?></label>
					<div class="col-md-4">

							<div class="input-group">
								<div class="input-group-addon">http://youtube.com/watch?v=</div>
								<input type="hidden"  id="video_type" name="video_type" value="youtube">
								<input style="min-width: 110px" class="form-control" type="text" id="video_url_id" name="video_url_id" placeholder="" maxlength="20" value="<?php echo @$tpl['cf_data']['video_url_id']?>">
							</div>

						<?php echo $lng['or_picture']?>
						<input id="picture" name="picture" class="form-control" type="text" maxlength="50" value="<?php echo @$tpl['cf_data']['picture']?>">
						<span class="help-block"><?php echo $lng['if_you_have_a_video'].$lng['cf_url_size']?></span>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-4 control-label" for="description_img"><?php echo $lng['picture_description']?></label>
					<div class="col-md-4">
						<input id="description_img" name="description_img" class="form-control" type="text" maxlength="50" value="<?php echo @$tpl['cf_data']['description_img']?>">
						<span class="help-block"><?php echo $lng['cf_width_height1'].$lng['cf_url_size']?></span>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-4 control-label" for="news_img"><?php echo $lng['image_news']?></label>
					<div class="col-md-4">
						<input id="news_img" name="news_img" class="form-control" type="text" maxlength="50" value="<?php echo @$tpl['cf_data']['news_img']?>">
						<span class="help-block"><?php echo $lng['cf_width_height1'].$lng['cf_url_size']?></span>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-4 control-label" for="links"><?php echo $lng['links_to_pictures']?></label>
					<div class="col-md-4">
						<textarea id="links" name="links" class="form-control" maxlength="512"><?php echo @$tpl['cf_data']['links']?></textarea>
						<span class="help-block"><?php echo $lng['links_format']?></span>
					</div>
				</div>


				<div class="form-group">
					<label class="col-md-4 control-label" for="singlebutton"></label>
					<div class="col-md-4">
						<button type="submit" class="btn btn-outline btn-primary" id="next"><?php echo $lng['preview']?></button>
						<button type="button" class="btn btn-outline btn-primary" id="save"><?php echo $lng['send_to_net']?></button>
					</div>
				</div>


			</fieldset>
		</form>

		<div class="alert alert-info">
			<strong><?php echo $lng['limits']?>:</strong> <?php echo $lng['cf_data_limits']?>
		</div>

		<input type="hidden" placeholder="video_type" id="video_type" value="">
		<input type="hidden" placeholder="video_url_id" id="video_url_id" value="">


	</div>

	<?php require_once( 'signatures.tpl' );?>
