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

		$('#start').bind('click', function () {
			$('#wait').text('<?php echo $lng['please_wait']?>');
			$.post( 'ajax/start_daemons.php', { } ,
					function () {
						fc_navigate ('node_config', {'alert': 'complete'} );
						$('#status').text('ON');
					});
		});

		$('#stop').bind('click', function () {
			$('#wait').text('<?php echo $lng['please_wait']?>');
			$.post( 'ajax/stop_daemons.php', { } ,
					function () {
						fc_navigate ('node_config', {'alert': 'complete'} );
						$('#status').text('OFF');
					});
		});

		$('#single_mode').bind('click', function () {
			$.post( 'ajax/switch_pool_mode.php', { } ,
					function () {
						fc_navigate ('node_config', {'alert': 'complete'} );
						$('#mode').text('Single');
					});
		});

		$('#pool_mode').bind('click', function () {
			$.post( 'ajax/switch_pool_mode.php', { } ,
					function () {
						fc_navigate ('node_config', {'alert': 'complete'} );
						$('#mode').text('Pool');
					});
		});


		$('#full').bind('click', function () {
			$('#wait').text('<?php echo $lng['please_wait']?>');
			$.post( 'ajax/clear_db.php', { } ,
					function () {
						fc_navigate ('node_config', {'alert': 'Complete! Press F5'} );
					});
		});

		$('#lite').bind('click', function () {
			$.post( 'ajax/clear_db_lite.php', { } ,
					function () {
						fc_navigate ('node_config', {'alert': '<?php echo $lng['please_wait']?>'} );
					});
		});

		$('#clear_daemons_time').bind('click', function () {
			$.post( 'ajax/clear_daemons_time.php', { } ,
					function () {
						fc_navigate ('db_info', {'alert': 'Complete!'} );
					});
		});
	</script>

  <legend><h2><?php echo $lng['node_config_title']?></h2></legend>
	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

    <div>
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
	<br><br>

	<div>
		<div class="alert alert-success" id="wait" style="display:none"></div>
		Status: <span id="status"><?php echo $tpl['my_status']?></span><br>
		<button class="btn" id="start">Start</button> 	<button class="btn" id="stop">Stop</button>
	</div>
	<br><br>

	<div>
		<div class="alert alert-success" id="wait" style="display:none"></div>
		Mode: <span id="mode"><?php echo $tpl['my_mode']?></span><br>
		<button class="btn" id="single_mode">Single mode</button> 	<button class="btn" id="pool_mode">Pool mode</button>
	</div>
	<br><br>

	<div>
		<button class="btn" id="lite">Lite nulling</button><br><br>
		<button class="btn" id="full">Full nulling</button>
	</div>
	<br><br>

	<div>
		<button class="btn" id="clear_daemons_time">clear_daemons_time</button>
	</div>
	<br><br>

	<div>
		<button class="btn" onclick="fc_navigate('rewrite_primary_key')">rewrite primary key</button>
	</div>

</div>
<!-- /container -->