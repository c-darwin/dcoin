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

	@-webkit-keyframes ui-spinner-rotate-right {
		0% {
			-webkit-transform: rotate(0deg);
		}
		25% {
			-webkit-transform: rotate(180deg);
		}
		50% {
			-webkit-transform: rotate(180deg);
		}
		75% {
			-webkit-transform: rotate(360deg);
		}
		100% {
			-webkit-transform: rotate(360deg);
		}
	}
	@-webkit-keyframes ui-spinner-rotate-left {
		0% {
			-webkit-transform: rotate(0deg);
		}
		25% {
			-webkit-transform: rotate(0deg);
		}
		50% {
			-webkit-transform: rotate(180deg);
		}
		75% {
			-webkit-transform: rotate(180deg);
		}
		100% {
			-webkit-transform: rotate(360deg);
		}
	}
	@-moz-keyframes ui-spinner-rotate-right {
		0% {
			-moz-transform: rotate(0deg);
		}
		25% {
			-moz-transform: rotate(180deg);
		}
		50% {
			-moz-transform: rotate(180deg);
		}
		75% {
			-moz-transform: rotate(360deg);
		}
		100% {
			-moz-transform: rotate(360deg);
		}
	}
	@-moz-keyframes ui-spinner-rotate-left {
		0% {
			-moz-transform: rotate(0deg);
		}
		25% {
			-moz-transform: rotate(0deg);
		}
		50% {
			-moz-transform: rotate(180deg);
		}
		75% {
			-moz-transform: rotate(180deg);
		}
		100% {
			-moz-transform: rotate(360deg);
		}
	}
	@keyframes ui-spinner-rotate-right {
		0% {
			transform: rotate(0deg);
		}
		25% {
			transform: rotate(180deg);
		}
		50% {
			transform: rotate(180deg);
		}
		75% {
			transform: rotate(360deg);
		}
		100% {
			transform: rotate(360deg);
		}
	}
	@keyframes ui-spinner-rotate-left {
		0% {
			transform: rotate(0deg);
		}
		25% {
			transform: rotate(0deg);
		}
		50% {
			transform: rotate(180deg);
		}
		75% {
			transform: rotate(180deg);
		}
		100% {
			transform: rotate(360deg);
		}
	}
	.ui-spinner {
		position: relative;
		border-radius: 100%;
	}
	.ui-spinner .side {
		width: 50%;
		height: 100%;
		overflow: hidden;
		position: absolute;
	}
	.ui-spinner .side .fill {
		border-radius: 999px;
		position: absolute;
		width: 100%;
		height: 100%;
		-webkit-animation-iteration-count: infinite;
		-moz-animation-iteration-count: infinite;
		animation-iteration-count: infinite;
		-webkit-animation-timing-function: linear;
		-moz-animation-timing-function: linear;
		animation-timing-function: linear;
	}
	.ui-spinner .side-left {
		left: 0;
	}
	.ui-spinner .side-left .fill {
		left: 100%;
		border-top-left-radius: 0;
		border-bottom-left-radius: 0;
		-webkit-animation-name: ui-spinner-rotate-left;
		-moz-animation-name: ui-spinner-rotate-left;
		animation-name: ui-spinner-rotate-left;
		-webkit-transform-origin: 0 50%;
		-moz-transform-origin: 0 50%;
		transform-origin: 0 50%;
	}
	.ui-spinner .side-right {
		left: 50%;
	}
	.ui-spinner .side-right .fill {
		left: -100%;
		border-top-right-radius: 0;
		border-bottom-right-radius: 0;
		-webkit-animation-name: ui-spinner-rotate-right;
		-moz-animation-name: ui-spinner-rotate-right;
		animation-name: ui-spinner-rotate-right;
		-webkit-transform-origin: 100% 50%;
		-moz-transform-origin: 100% 50%;
		transform-origin: 100% 50%;
	}

	.example-hole .ui-spinner {
		width: 230px;
		height: 230px;
		background: #ddd;
	}
	<?php $css_time = (substr(PHP_OS, 0, 3) == "WIN")?240:5 ?>
	.example-hole .ui-spinner .side .fill {
		background: #3c76ca;
		-webkit-animation-duration: <?php echo $css_time?>s;
		-moz-animation-duration: <?php echo $css_time?>s;
		animation-duration: <?php echo $css_time?>s;
		opacity: 0.8;
	}

	.example-hole .ui-spinner:after {
		content: "";
		background: #fff;
		position: absolute;
		width: 140px;
		height: 140px;
		border-radius: 50%;
		top: 45px;
		left: 45px;
		display: block;
	}
/*------------------------------------------*/

	.meter1 {
		height: 20px;  /* Can be anything */
		position: relative;
		margin: 20px 0 20px 0; /* Just for demo spacing */
		background: #f0f0f0;
		-moz-border-radius: 25px;
		-webkit-border-radius: 25px;
		border-radius: 25px;
		padding: 0px;
		-webkit-box-shadow: inset 0 -1px 1px rgba(255,255,255,0.3);
		-moz-box-shadow   : inset 0 -1px 1px rgba(255,255,255,0.3);
		box-shadow        : inset 0 -1px 1px rgba(255,255,255,0.3);
	}
	.meter1 > span {
		display: block;
		-webkit-border-top-right-radius: 8px;
		-webkit-border-bottom-right-radius: 8px;
		-moz-border-radius-topright: 8px;
		-moz-border-radius-bottomright: 8px;
		border-top-right-radius: 8px;
		border-bottom-right-radius: 8px;
		-webkit-border-top-left-radius: 20px;
		-webkit-border-bottom-left-radius: 20px;
		-moz-border-radius-topleft: 20px;
		-moz-border-radius-bottomleft: 20px;
		border-top-left-radius: 20px;
		border-bottom-left-radius: 20px;
		background-color: rgb(43,194,83);
		background-image: -webkit-gradient(
			linear,
			left bottom,
			left top,
			color-stop(0, rgb(43,194,83)),
			color-stop(1, rgb(84,240,84))
		);
		background-image: -moz-linear-gradient(
			center bottom,
			rgb(43,194,83) 37%,
			rgb(84,240,84) 69%
		);
		-webkit-box-shadow:
		inset 0 2px 9px  rgba(255,255,255,0.3),
		inset 0 -2px 6px rgba(0,0,0,0.4);
		-moz-box-shadow:
		inset 0 2px 9px  rgba(255,255,255,0.3),
		inset 0 -2px 6px rgba(0,0,0,0.4);
		box-shadow:
		inset 0 2px 9px  rgba(255,255,255,0.3),
		inset 0 -2px 6px rgba(0,0,0,0.4);
		position: relative;
		overflow: hidden;
	}
	.meter1 > span:after, .animate > span > span {
		content: "";
		position: absolute;
		top: 0; left: 0; bottom: 0; right: 0;
		background-image:
		-webkit-gradient(linear, 0 0, 100% 100%,
		color-stop(.25, rgba(255, 255, 255, .2)),
		color-stop(.25, transparent), color-stop(.5, transparent),
		color-stop(.5, rgba(255, 255, 255, .2)),
		color-stop(.75, rgba(255, 255, 255, .2)),
		color-stop(.75, transparent), to(transparent)
		);
		background-image:
		-moz-linear-gradient(
			-45deg,
			rgba(255, 255, 255, .2) 25%,
			transparent 25%,
			transparent 50%,
			rgba(255, 255, 255, .2) 50%,
			rgba(255, 255, 255, .2) 75%,
			transparent 75%,
			transparent
		);
		z-index: 1;
		-webkit-background-size: 50px 50px;
		-moz-background-size: 50px 50px;
		background-size: 50px 50px;
		-webkit-animation: move 2s linear infinite;
		-moz-animation: move 2s linear infinite;
		-webkit-border-top-right-radius: 8px;
		-webkit-border-bottom-right-radius: 8px;
		-moz-border-radius-topright: 8px;
		-moz-border-radius-bottomright: 8px;
		border-top-right-radius: 8px;
		border-bottom-right-radius: 8px;
		-webkit-border-top-left-radius: 20px;
		-webkit-border-bottom-left-radius: 20px;
		-moz-border-radius-topleft: 20px;
		-moz-border-radius-bottomleft: 20px;
		border-top-left-radius: 20px;
		border-bottom-left-radius: 20px;
		overflow: hidden;
	}

	.animate > span:after {
		display: none;
	}

	@-webkit-keyframes move {
		0% {
			background-position: 0 0;
		}
		100% {
			background-position: 50px 50px;
		}
	}

	@-moz-keyframes move {
		0% {
			background-position: 0 0;
		}
		100% {
			background-position: 50px 50px;
		}
	}

	.nostripes > span > span, .nostripes > span:after {
		-webkit-animation: none;
		-moz-animation: none;
		background-image: none;
	}
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
						var pct = Math.round(data.block_id/200000*100);
						$('#meter').width(pct+'%');
						$('#meter').text(pct+'%');

					}
					else if (data.block_id==-1) {
						window.clearInterval(refreshId);
						window.location.href = "index.php";
					}
				}, 'JSON'
			);
		}, <?php echo (substr(PHP_OS, 0, 3) == "WIN")?120000:5000 ?>);
	});
</script>
<div style="max-width: 600px; margin: auto; text-align: center">
	<div id="blockchain_loading" style="display: <?php echo $tpl['wait']?'block':'none'?>"><div class="blockchain_loader" >Loading...</div>
	<br>
	<?php echo $tpl['wait']?></div>

	<div id="blocks_counter" style="display: <?php echo $tpl['wait']?'none':'block'?>">
		<h3 style="margin-bottom: 20px"><?php echo $lng['synchronization_blockchain']?></h3>
		<div style="text-align:center; position:relative; width:230px; margin:auto; margin-bottom: 10px">
			<div class="example example-hole">
				<div class="ui-spinner">
                <span class="side side-left">
                    <span class="fill"></span>
                </span>
                <span class="side side-right">
                    <span class="fill"></span>
                </span>
				</div>
			</div>
			<div style="top:100px; left:50%; margin-left:-50px;position:absolute;text-align:center; width:100px; font-size: 20px" id="cur_block_id"><?php echo $tpl['block_id']?></div>
			</div>
		<!--<?php echo $lng['time_last_block']?>: <span id='block_time' class='unixtime'><?php echo $tpl['block_time']?></span><br>-->
		<div class="meter1">
			<span style="width: <?php echo round(($tpl['block_id']/200000)*100)?>%; height: 20px; color: #fff" id="meter"><?php echo round(($tpl['block_id']/200000)*100)?>%</span>
		</div>
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
