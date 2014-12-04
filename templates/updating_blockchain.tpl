<style>
	#page-wrapper{
		margin: 0px 10% 0px 10%;
		border: 1px solid #E7E7E7;
		min-height: 550px;
	}
	#wrapper{height: 100%;}
	#dc_content{
		height: 550px;
		vertical-align: middle;
	}
</style>
<script>
	$(document).ready(function() 	{
		var refreshId = setInterval( function() {
			$.post( 'ajax/my_notice.php', {}, function (data) {
					console.log(data.time_last_block_int);
					console.log(data.cur_block_id);
					if (data.cur_block_id>1) {
						$('#blockchain_loading').css('display', 'none');
						$('#get_blocks').css('display', 'block');
						var time = Number(data.time_last_block_int + '000');
						console.log(time);
						var d = new Date(time);
						$('#block_time').text(d);
						$('#cur_block_id').text(data.cur_block_id);
						console.log(d);
					}
				}, 'JSON'
			);
		}, 1000);
	});
</script>
<div style="max-width: 600px; margin: auto; text-align: center">
	<?php
	if ($tpl['wait']) {
		echo '<div id="blockchain_loading"><div class="blockchain_loader" >Loading...</div><br>';
		echo "{$tpl['wait']}</div>";
	}
	else {
		echo '<h3>'.$lng['synchronization_blockchain'].'</h3><div style="text-align:center; position:relative; width:200px; margin:auto">
				  <div class="get_blocks_loader" >Loading...</div>
				  <div style="top:45px; left:50%; margin-left:-35px;position:absolute;text-align:center; width:70px" id="cur_block_id">'.$tpl['block_id'].'</div>
				   </div>';
		echo "{$lng['time_last_block']}: <span id='block_time' class='unixtime'>{$tpl['block_time']}</span><br>";
		echo "</div>";
	}
	?>
</div>
