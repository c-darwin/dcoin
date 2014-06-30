<!-- container -->
<div class="container">

	<script>
		$('#save').bind('click', function () {
			$.post( 'ajax/save_interface.php', {
						'show_sign_data' : $('#show_sign_data').val()
					} ,
					function () { 
						fc_navigate ('interface', {'alert': '<?php echo $lng['saved']?>'} );
					});
		});
	</script>

  <legend><h2><?php echo $lng['interface']?></h2></legend>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

    <div>
	    <div class="control-group">
		    <label class="control-label" for="inputEmail">show_sign_data</label>
		    <div class="controls">
			    <select id="show_sign_data" class="input-xlarge">
				    <option value="1" <?php echo ($tpl['show_sign_data'])?'selected="selected"':''?>>Yes</option>
				    <option value="0" <?php echo (!$tpl['show_sign_data'])?'selected="selected"':''?>>No</option>
			    </select>
		    </div>
	    </div>
		<button class="btn" id="save"><?php echo $lng['save']?></button>
		<br><br>


    </div>

</div>
<!-- /container -->