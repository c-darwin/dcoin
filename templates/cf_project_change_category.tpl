<script>

$('#category_id').on('change', function() {
	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']},{$tpl['project_id']}"; ?>,'+this.value);
	console.log($("#for-signature").val());
	doSign();
});


$('#send_to_net').bind('click', function () {

	$.post( 'ajax/save_queue.php', {
			'type' : '<?php echo $tpl['data']['type']?>',
			'time' : '<?php echo $tpl['data']['time']?>',
			'user_id' : '<?php echo $tpl['data']['user_id']?>',
			'project_id' : <?php echo $tpl['project_id']?>,
			'category_id' : $('#category_id').val(),
			'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
			}, function (data) {
				fc_navigate ('my_cf_projects', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			} );
} );

</script>
<h1 class="page-header"><?php echo $lng['cf_project_change_category_title']?></h1>
<ol class="breadcrumb">
	<li><a href="#">CrowdFunding</a></li>
	<li><a href="#"onclick="fc_navigate('my_cf_projects')"><?php echo $lng['my_projects']?></a></li>
	<li class="active"><?php echo $lng['cf_project_change_category_title'].' '.$tpl['project_currency_name']?></li>
</ol>


<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

	<form class="form-horizontal">
		<fieldset>
		<div class="form-group">
			<label class="col-md-4 control-label" for="category_id"><?php echo $lng['category']?></label>
			<div class="col-md-4">
				<select id="category_id" name="category_id" class="form-control">
					<?php
						foreach ($lng['cf_category'] as $id=>$name) {
							$sel = '';
							if ($id==$tpl['category_id'])
								$sel = 'selected';
							echo "<option value='{$id}' {$sel}>{$name}</option>";
						}
					?>
				</select>
				<span class="help-block"><?php echo $lng['category_for_your_project']?></span>
			</div>
		</div>
		</fieldset>
	</form>

	<div class="form-group">
		<label class="col-md-4 control-label" for="singlebutton"></label>
		<div class="col-md-4">
			<button type="button" class="btn btn-outline btn-primary" id="send_to_net"><?php echo $lng['send_to_net']?></button>
		</div>
	</div>

    <div id="sign" style="display: none">
	
		<label><?php echo $lng['data']?></label>
		<textarea id="for-signature" style="width:500px;" rows="4"><?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']},{$tpl['category_id']}"; ?></textarea>
	    <?php
	for ($i=1; $i<=$count_sign; $i++) {
		echo "<label>{$lng['sign']} ".(($i>1)?$i:'')."</label><textarea id=\"signature{$i}\" style=\"width:500px;\" rows=\"4\"></textarea>";
	    }
	    ?>
	    <br>
		<button class="btn" id="send_to_net"><?php echo $lng['send_to_net']?></button>

    </div>

	<input type="hidden" id="user_id" value="<?php echo $_SESSION['user_id']?>">
	<input type="hidden" id="time" value="<?php echo time()?>">
	<script>
		doSign();
	</script>