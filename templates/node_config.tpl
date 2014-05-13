<!-- container -->
<div class="container">

	<script>
		$('#save').bind('click', function () {
			$.post( 'ajax/save_node_config.php', {
						'in_connections_ip_limit' : $('#in_connections_ip_limit').val(),
						'in_connections' : $('#in_connections').val(),
						'out_connections' : $('#out_connections').val(),
						'config_ini' : $('#config_ini').val()
					} ,
					function () { 
						fc_navigate ('node_config', {'alert': '<?php echo $lng['saved']?>'} );
					});
		});
	</script>

  <legend><h2><?php echo $lng['node_config_title']?></h2></legend>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

    <div id="new">
		<label><?php echo $lng['in_connections_ip_limit']?></label>
		<input id="in_connections_ip_limit" class="input" type="text" value="<?php echo $tpl['data']['in_connections_ip_limit']?>">
		<label><?php echo $lng['in_connections']?></label>
		<input id="in_connections" class="input" type="text" value="<?php echo $tpl['data']['in_connections']?>">
		<label><?php echo $lng['out_connections']?></label>
		<input id="out_connections" class="input" type="text" value="<?php echo $tpl['data']['out_connections']?>">
		<br>

	    <textarea style="width: 300px; height: 150px" id="config_ini"><?php echo $tpl['config_ini']?></textarea>
	    <br>

		<button class="btn" id="save"><?php echo $lng['save']?></button>

    </div>
     
    

</div>
<!-- /container -->