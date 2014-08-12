
	<script>
		$('#save').bind('click', function () {
			$.post( 'ajax/save_interface.php', {
						'show_sign_data' : $('#show_sign_data').val()
					} ,
					function () { 
						fc_navigate ('interface', {'alert': '<?php echo $lng['saved']?>'} );
					});
		});

		$("#main_div select").addClass( "form-control" );
		$("#main_div input").addClass( "form-control" );
		$("#main_div button").addClass( "btn-outline btn-primary" );
	</script>

	<div id="main_div">
  <h1 class="page-header"><?php echo $lng['interface']?></h1>
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