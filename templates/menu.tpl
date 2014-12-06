
<script>
	$('#start_daemons').bind('click', function () {
		$.post( 'ajax/start_daemons.php', { } ,
			function () {
				load_menu();
			});
	});

	$('#stop_daemons').bind('click', function () {
		$.post( 'ajax/stop_daemons.php', { } ,
			function () {
				load_menu();
			});
	});

	var urls = ['<?php echo implode("','", $tpl['face_urls']);?>'];
	function get_img (i) {
		console.log('get_img');
		if (typeof urls == 'undefined')
			return false;
		var image = new Image();
		if (typeof urls[i] != 'undefined' && urls[i]!='' && urls[i]!='0') {
			image.src = urls[i];
			image.onload = function(){
				$('#img_avatar').css("background", "url('"+urls[i]+"')  50% 50%");
				$('#img_avatar').css("background-size", "100px Auto");
			};
			// handle failure
			image.onerror = function(){
				console.log('error'+urls[i]);
				get_img (i+1);
			};
		}
		else {
			//$('#img_avatar').attr('src', '<?php echo $tpl['no_avatar']?>');
			$('#img_avatar').css("background", "url('<?php echo $tpl['no_avatar']?>') 0 0");
		}
	}

	<?php
	if(!empty($tpl['avatar'])) {
		echo '$("#img_avatar").css("background", "url(\''.$tpl['avatar'].'\') 50% 50%");';
		echo '$("#img_avatar").css("background-size", "100px Auto");';
	}
	else
		echo 'get_img(0);';
	?>

</script>
<style>
	#settings_menu_left{display: none}
	#lng_menu_left{display: none}
	@media (max-width: 768px) {
		#settings_menu_left{display: block}
		#lng_menu_left{display: block}
		#settings_menu{display: none}
	    body{padding-top: 50px}
	}

	.dcoin-logo { background-image: url('img/logo.png'); width:182px; height: 40px; background-size:182px 40px}
	@media (max-width: 600px) {
		.dcoin-logo { background-image: url('img/logo-small.png'); width:42px; height: 40px; background-size:42px 40px}
		#progress_status_text{display: none}
	}
	.flag_42{display:inline-block; background-image: url('img/us-ru.png'); background-position: 0px 0px; width: 22px; height: 16px}
	.flag_1{display:inline-block; background-image: url('img/us-ru.png'); background-position: 0px 16px; width: 22px; height: 16px}

	#last_block_id a:hover {background-color: inherit}
</style>
<nav class="navbar navbar-default navbar-fixed-top" role="navigation" style="margin-bottom: 0">
	<div class="navbar-header">
		<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
			<span class="sr-only">Toggle navigation</span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		</button>
		<a class="navbar-brand" href="#home" style="display: block; padding-left: 0px;  padding-top: 6px; margin-left: 15px; margin-right: 5px"><div class="dcoin-logo"></div></a>
		<div id="progress_bar" style="float: left;">
		</div>
	</div>
	<!-- /.navbar-header -->


	<ul class="nav navbar-top-links navbar-right" id="settings_menu">
		<?php
		if (!$tpl['my_notice']['main_status_complete']) {
			echo '<li id="last_block_id"><a href="#block_explorer" id="block_id" style="color:#ff0000">'.$tpl['block_id'].'</a></li>';
		}
		else {
			echo '<li id="last_block_id"><a href="#block_explorer" id="block_id" style="color:#428BCA">'.$tpl['block_id'].'</a></li>';
		}
		?>
		<?php echo !defined('COMMUNITY')?$tpl['daemons_status']:''?>
		<li class="dropdown">
			<a class="dropdown-toggle" data-toggle="dropdown" href="#">
				<i class="fa fa-cog fa-fw" style="font-size: 20px"></i>
			</a>
			<ul class="dropdown-menu">
				<?php echo (defined('POOL_ADMIN') || !defined('COMMUNITY'))?'<li><a href="#change_node_key">'.$lng['change_node_key'].'</a></li>':''?>
				<?php echo defined('POOL_ADMIN')?'<li><a href="#pool_admin">Pool admin</a></li>':'' ?>
				<li><a href="#notifications"><?php echo $lng['sms_and_email_notifications']?></a>
				</li>
				<li><a href="#change_primary_key"><?php echo $lng['change_master_key']?></a>
				</li>
				<li><a href="#restoring_access"><?php echo $lng['restoring_access']?></a>
				</li>
				<li><a href="#interface"><?php echo $lng['interface']?></a>
				</li>
				<?php echo (defined('POOL_ADMIN') || !defined('COMMUNITY'))?'<li><a href="#node_config">'.$lng['config_node'].'</a></li>':''?>
				<li class="divider"></li>
				<li><a href="#" onclick="logout()"><i class="fa fa-sign-out fa-lg"></i> <?php echo $lng['logout']?> (user_id: <?php echo $_SESSION['user_id']?>)</a>
				</li>
				<!--<li><a href="#myModal"  data-toggle="modal" data-backdrop="static"><i class="fa fa-sign-in fa-lg"></i> Change key</a>
				</li>-->
			</ul>
			<!-- /.dropdown-messages -->
		</li>

		<!-- /.dropdown -->

		<li class="dropdown">
			<a class="dropdown-toggle" data-toggle="dropdown" href="#">
				<div class="flag_<?php echo $tpl['lang']?>"></div>
			</a>
			<ul class="dropdown-menu dropdown-user">
				<li><a href="#home/lang=1" class="lng_1">English</a>
				</li>
				<li><a href="#home/lang=42" class="lng_42">Русский</a>
				</li>
			</ul>
		</li>

		<!-- /.dropdown -->
	</ul>
	<!-- /.navbar-top-links -->

	<div class="navbar-default navbar-static-side" role="navigation">
		<div class="sidebar-collapse">
			<ul class="nav" id="side-menu">
				<li class="sidebar-search">
					<div class="text-center" style="width: 100px; height: 100px; margin:auto; border-radius: 50%" id="img_avatar"></div>
					<div style="" class="text-center"><a href="#change_avatar"><strong><?php echo $tpl['name']?></strong></a></div>
				</li>
				<li>
					<a href="#home"><i class="fa  fa-home  fa-fw"></i> Home</a>
				</li>
				<li>
					<a href="#wallets_list"><i class="fa   fa-credit-card   fa-fw"></i> <?php echo $lng['wallets']?></a>
				</li>
				<li>
					<a href="#mining_menu"><i class="fa  fa-money  fa-fw"></i> <?php echo $lng['mining']?> <?php echo $tpl['miner_id']?'':'<i class="fa fa-arrow-left"></i>';?></a>
				</li>
				<?php echo $tpl['miner_id']?'<li><a href="#new_user"><i class="fa  fa-smile-o  fa-fw"></i> '.$lng['my_referrals'].'</a></li>':''?>
				<li>
					<a href="#"><i class="fa  fa-users  fa-fw"></i> CrowdFunding<span class="fa arrow"></span></a>
					<ul class="nav nav-second-level">
						<li>
							<a href="#cf_catalog"><?php echo $lng['catalog']?></a>
						</li>
						<li>
							<a href="#my_cf_projects"><?php echo $lng['my_projects']?></a>
						</li>
					</ul>
				</li>


				<li>
					<a href="#"><i class="fa  fa-info-circle -o fa-fw"></i> <?php echo $lng['information']?><span class="fa arrow"></span></a>
					<ul class="nav nav-second-level">
						<!--<li>
							<a href="#pct"><?php echo $lng['pct']?></a>
						</li>
						<li>
							<a href="#reduction"><?php echo $lng['reduction']?></a>
						</li>-->
						<li>
							<a href="#statistic"><?php echo $lng['statistic']?></a>
						</li>
						<li>
							<a href="#block_explorer">Block explorer</a>
						</li>
						<li>
							<a href="#information"><?php echo $lng['information']?></a>
						</li>
						<li>
							<a href="#db_info"><?php echo $lng['db_info']?></a>
						</li>

					</ul>
				</li>


				<li>
					<a href="#"><i class="fa  fa-life-ring  fa-fw"></i> Help<span class="fa arrow"></span></a>
					<ul class="nav nav-second-level">
						<li>
							<a href="<?php echo $lng['faq_url']?>" target="_blank"><?php echo $lng['faq']?></a>
						</li>
						<li>
							<a href="<?php echo $lng['wiki_url']?>" target="_blank">Wiki</a>
						</li>
						<li>
							<a href="mailto: admin@dcoin.me">Support</a>
						</li>
						<li>
							<a href="http://dcoinforum.org" target="_blank">Forum</a>
						</li>
						<!--<li>
							<a href="#" onclick="fc_navigate('bug_reporting')"><?php echo $lng['bug_reporting']?></a>
						</li>-->
						<li>
							<a href="#abuse"><?php echo $lng['complaints_miners']?></a>
						</li>
					</ul>
				</li>

				<li id="lng_menu_left">
					<a href="#"><i class="fa  fa-globe fa-fw"></i> Language<span class="fa arrow"></span></a>
					<ul class="nav nav-second-level">
						<li><a href="#home/lang=1" class="lng_1">English</a>
						</li>
						<li><a href="#home/lang=42" class="lng_42">Русский</a>
						</li>
					</ul>
				</li>
				<li id="settings_menu_left">
					<a href="#"><i class="fa fa-cog fa-fw"></i> Settings<span class="fa arrow"></span></a>
					<ul class="nav nav-second-level">
						<?php echo (defined('POOL_ADMIN') || !defined('COMMUNITY'))?'<li><a href="#change_node_key">'.$lng['change_node_key'].'</a></li>':''?>
						<?php echo defined('POOL_ADMIN')?'<li><a href="#pool_admin">Pool admin</a></li>':'' ?>
						<li><a href="#notifications"><?php echo $lng['sms_and_email_notifications']?></a>
						</li>
						<li><a href="#change_primary_key"><?php echo $lng['change_master_key']?></a>
						</li>
						<li><a href="#restoring_access"><?php echo $lng['restoring_access']?></a>
						</li>
						<li><a href="#interface"><?php echo $lng['interface']?></a>
						</li>
						<?php echo (defined('POOL_ADMIN') || !defined('COMMUNITY'))?'<li><a href="#node_config">'.$lng['config_node'].'</a></li>':''?>
						<li class="divider"></li>
						<li><a href="#" onclick="logout()"><i class="fa fa-sign-out fa-lg"></i> <?php echo $lng['logout']?> (user_id: <?php echo $_SESSION['user_id']?>)</a>
						</li>
					</ul>
				</li>


				<div id="main-login">
					<a href="#myModal" data-backdrop="static" data-toggle="modal" role="button" class="btn btn-danger  btn-block "><i class="fa fa-sign-in fa-lg"></i> Login</a>
					<div style="margin: 2px 10px; font-size: 11px"><?php echo $lng['login_alert']?></div>
				</div>
			</ul>
			<!-- /#side-menu -->
		</div>
		<!-- /.sidebar-collapse -->
	</div>
	<!-- /.navbar-static-side -->
</nav>
<script>
	$( document ).ready(function() {
		console.log($('#key').html().length);
		if ($('#key').html().length>150){
			$("#main-login").html('');
		}
		$( "#progress_bar" ).load( "ajax/progress_bar.php");
	});
</script>
<?php
require_once( ABSPATH . 'templates/modal.tpl' );
echo $modal;
?>
