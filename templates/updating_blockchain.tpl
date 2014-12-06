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
	#dc_menu{display: none}
</style>
<script>
	$(document).ready(function() 	{
		var refreshId = setInterval( function() {
			$.post( 'ajax/synchronization_blockchain.php', {}, function (data) {
					console.log(data.block_time);
					console.log(data.block_id);
					if (data.block_id>1) {
						$('#blockchain_loading').css('display', 'none');
						$('#blocks_counter').css('display', 'block');
						var time = Number(data.block_time + '000');
						console.log(time);
						var d = new Date(time);
						$('#block_time').text(d);
						$('#cur_block_id').text(data.block_id);
						console.log(d);
					}
					else if (data.block_id==-1) {
						window.clearInterval(refreshId);
						window.location.href = "index.php";
					}
				}, 'JSON'
			);
		}, <?php echo (substr(PHP_OS, 0, 3) == "WIN")?120000:1000 ?>);
	});
</script>
<div style="max-width: 600px; margin: auto; text-align: center">
	<div id="blockchain_loading" style="display: <?php echo $tpl['wait']?'block':'none'?>"><div class="blockchain_loader" >Loading...</div>
	<br>
	<?php echo $tpl['wait']?></div>

	<div id="blocks_counter" style="display: <?php echo $tpl['wait']?'none':'block'?>"><h3><?php echo $lng['synchronization_blockchain']?></h3><div style="text-align:center; position:relative; width:200px; margin:auto">
			<div class="get_blocks_loader" >Loading...</div>
			<div style="top:45px; left:50%; margin-left:-35px;position:absolute;text-align:center; width:70px" id="cur_block_id"><?php echo $tpl['block_id']?></div>
			</div>
		<?php echo $lng['time_last_block']?>: <span id='block_time' class='unixtime'><?php echo $tpl['block_time']?></span><br>
	</div>
	<div id="check_time" style="margin-top: 50px"><?php
		if (!get_community_users($db)) {
			echo $lng['check_time']." ";
			echo (substr(PHP_OS, 0, 3) == "WIN")?$lng['check_time_win']:$lng['check_time_nix'];
		}
		?></div>
</div>

</div>
<script src="js/unixtime.js"></script>
