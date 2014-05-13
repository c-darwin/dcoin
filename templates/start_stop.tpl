<!-- container -->
<div class="container">

	<script>
		$('#start').bind('click', function () {
			$('#wait').text('<?php echo $lng['please_wait']?>');
			$.post( 'ajax/start_daemons.php', { } ,
					function () {
						fc_navigate ('start_stop', {'alert': 'complete'} );
						$('#status').text('ON');
					});
		});

		$('#stop').bind('click', function () {
			$('#wait').text('<?php echo $lng['please_wait']?>');
			$.post( 'ajax/stop_daemons.php', { } ,
					function () {
						fc_navigate ('start_stop', {'alert': 'complete'} );
						$('#status').text('OFF');
					});
		});
	</script>

  <legend><h2><?php echo $lng['start_stop_title']?></h2></legend>
 	<?php echo ($tpl['alert'])?'<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>'.$tpl['alert'].'</div>':''?>
	<div class="alert alert-success" id="wait" style="display:none"></div>

    
    <div id="new">
	    Status: <span id="status"><?php echo $tpl['my_status']?></span><br>
	    <button class="btn" id="start">Start</button> 	<button class="btn" id="stop">Stop</button>

    </div>
     
    

</div>
<!-- /container -->