<style>
	#page-wrapper{
		margin: 0px 10% 0px 10%;
		border: 1px solid #E7E7E7;
		min-height: 550px;
	}
	#wrapper{height: 100%;}
	#dc_content{
		vertical-align: middle;
	}
</style>
<div style="max-width: 600px; margin: auto; margin-top: 50px">


	<ul class="nav nav-tabs" style="margin-bottom: 20px">
		<li><a href="#install_step_0">Step 0</a></li>
		<li><a href="#install_step_1">Step 1</a></li>
		<li><a href="#install_step_2">Step 2</a></li>
		<li><a href="#install_step_2_1">Step 3</a></li>
		<li class="active"><a href="#install_step_3">Step 4</a></li>
	</ul>

	<?php echo $lng['install_create_cron']?>: <strong><?php echo (OS=='WIN')?'':'* * * * *' ?> <?php print $tpl['php_path']?> <?php echo ABSPATH ?>cron/daemons.php</strong><br>
	<?php echo $lng['install_mysql_warning']?>

<br>
	<?php echo $lng['status']?>:<br>

<script>
	daemons_ok = false;

	check_cron = function() {
	 
		$.get("ajax/check_cron_and_chmod.php", {},
		function(data) {
			$('#queue_parser_blocks').text( "queue_parser_blocks: " + data.queue_parser_blocks );
			$('#testblock_is_ready').text( "testblock_is_ready: " + data.testblock_is_ready );
			$('#disseminator').text( "disseminator: " + data.disseminator );
			$('#testblock_generator').text( "testblock_generator: " + data.testblock_generator );
			$('#queue_parser_testblock').text( "queue_parser_testblock: " + data.queue_parser_testblock );
			$('#queue_parser_tx').text( "queue_parser_tx: " + data.queue_parser_tx );
			$('#pct_generator').text( "pct_generator: " + data.pct_generator );
			$('#blocks_collection').text( "blocks_collection: " + data.blocks_collection );
			$('#node_voting').text( "node_voting: " + data.node_voting );
			$('#connector').text( "connector: " + data.connector );
			$('#testblock_disseminator').text( "testblock_disseminator: " + data.testblock_disseminator );
			$('#chmod0777').text( data.chmod0777 );
			
			if ( data.queue_parser_blocks=='ok' && data.testblock_is_ready=='ok' && data.disseminator=='ok' && data.testblock_generator=='ok' && data.queue_parser_testblock=='ok' && data.queue_parser_tx=='ok' && data.pct_generator=='ok' && data.blocks_collection=='ok' && data.node_voting=='ok' && data.connector=='ok' && data.testblock_disseminator=='ok'  ) {
				$('#enableOnInput').removeAttr('disabled');
				daemons_ok = true;
				return;
			}
			
		}, "json");
	 
	   if (true && !daemons_ok)
		 setTimeout(check_cron, "1000");
	};
	 
	check_cron();

</script>


<div class="container">
	<div id="queue_parser_blocks">no</div>
	<div id="testblock_is_ready">no</div>
	<div id="disseminator">no</div>
	<div id="testblock_generator">no</div>
	<div id="queue_parser_testblock">no</div>
	<div id="queue_parser_tx">no</div>
	<div id="pct_generator">no</div>
	<div id="blocks_collection">no</div>
	<div id="node_voting">no</div>
	<div id="connector">no</div>
	<div id="testblock_disseminator">no</div>
</div>

<br>

<?php echo $lng['install_chmod']?>:<br>
	<?php echo $lng['status']?>:<br>
	<div id="chmod0777">no</div>
	<p><?php echo $lng['install_chmod_wait']?></p>

<button class="btn btn-success" onclick="fc_navigate('install_step_4')" id='enableOnInput' disabled="disabled"><?php echo $lng['next']?></button>



</div>