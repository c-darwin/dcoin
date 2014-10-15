<nav class="navbar navbar-default navbar-fixed-top" role="navigation" style="margin-bottom: 0">
	<div class="navbar-header">
		<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
			<span class="sr-only">Toggle navigation</span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		</button>

		<a class="navbar-brand" href="#" style="display: block; padding-left: 0px;  padding-top: 6px; margin-left: 15px; margin-right: 5px" onclick="fc_navigate('home')"><img src="../img/logo3.png" height="40"></a>
		<!--<a class="navbar-brand" href="#" style="display: block; /* or inline-block; I think IE would respect it since a link is an inline-element */
	                   background: url(img/logo.png) center left no-repeat;
	                   text-align: center;
	                   background-size: 30px 30px;
	                   padding-left: 40px; margin-left: 15px; margin-right: 50px" onclick="fc_navigate('home')">Dcoin <span style="font-size: 12px">v<?php echo $tpl['ver']?></span></a>-->
	</div>
	<!-- /.navbar-header -->

	<ul class="nav navbar-top-links navbar-right">
		<li class="dropdown">
			<a class="dropdown-toggle" data-toggle="dropdown" href="#">
				<i class="fa fa-cog fa-fw"></i> Settings <i class="fa fa-caret-down"></i>
			</a>
			<ul class="dropdown-menu">
				<?php echo (defined('POOL_ADMIN') || !defined('COMMUNITY'))?'<li><a href="#" onclick="fc_navigate(\'change_node_key\')">'.$lng['change_node_key'].'</a></li>':''?>
				<?php echo defined('POOL_ADMIN')?'<li><a href="#" onclick="fc_navigate(\'pool_admin\')">Pool admin</a></li>':'' ?>
				<li><a href="#" onclick="fc_navigate('notifications')"><?php echo $lng['sms_and_email_notifications']?></a>
				</li>
				<li><a href="#" onclick="fc_navigate('change_primary_key')"><?php echo $lng['change_master_key']?></a>
				</li>
				<li><a href="#" onclick="fc_navigate('interface')"><?php echo $lng['interface']?></a>
				</li>
				<?php echo (defined('POOL_ADMIN') || !defined('COMMUNITY'))?'<li><a href="#" onclick="fc_navigate(\'node_config\')">'.$lng['config_node'].'</a></li>':''?>
				<li class="divider"></li>
				<li><a href="#" onclick="logout()"><i class="fa fa-sign-out fa-lg"></i> <?php echo $lng['logout']?> (user_id: <?php echo $_SESSION['user_id']?>)</a>
				</li>
				<li><a href="#myModal"  data-toggle="modal" data-backdrop="static"><i class="fa fa-sign-in fa-lg"></i> Change key</a>
				</li>
			</ul>
			<!-- /.dropdown-messages -->
		</li>

		<!-- /.dropdown -->
		<!--
		<li class="dropdown">
			<a class="dropdown-toggle" data-toggle="dropdown" href="#">
				<i class="fa  fa-globe fa-fw"></i> Language <i class="fa fa-caret-down"></i>
			</a>
			<ul class="dropdown-menu dropdown-user">
				<li><a href="#" onclick="fc_navigate('home', 'lang=1'); load_menu();">English</a>
				</li>
				<li><a href="#" onclick="fc_navigate('home', 'lang=42'); load_menu();">Русский</a>
				</li>
			</ul>
		</li>
		-->
		<!-- /.dropdown -->
	</ul>
	<!-- /.navbar-top-links -->

	<div class="navbar-default navbar-static-side" role="navigation">
		<div class="sidebar-collapse">
			<ul class="nav" id="side-menu">
				<li class="sidebar-search">
					<div><img src="img/circle.png" style="position: absolute; height: 102px"></div>
					<div style="" class="text-center"><a href="#" onclick="fc_navigate('change_avatar')"><img src="<?php echo $tpl['avatar']?>" style="width: 100px; height: 100px;"></a></div>
					<div style="" class="text-center"><a href="#" onclick="fc_navigate('change_avatar')"><strong><?php echo $tpl['name']?></strong></a></div>
				</li>
				<li>
					<a href="#" onclick="fc_navigate('home')"><i class="fa  fa-home  fa-fw"></i> Home</a>
				</li>
				<li>
					<a href="#" onclick="fc_navigate('wallets_list')"><i class="fa   fa-credit-card   fa-fw"></i> <?php echo $lng['wallets']?></a>
				</li>
				<li>
					<a href="#" onclick="fc_navigate('mining_menu')"><i class="fa  fa-money  fa-fw"></i> <?php echo $lng['mining']?></a>
				</li>
				<li>
					<a href="#"><i class="fa  fa-users  fa-fw"></i> CrowdFunding<span class="fa arrow"></span></a>
					<ul class="nav nav-second-level">
						<li>
							<a href="#" onclick="fc_navigate('cf_catalog')"><?php echo $lng['catalog']?></a>
						</li>
						<li>
							<a href="#" onclick="fc_navigate('my_cf_projects')"><?php echo $lng['my_projects']?></a>
						</li>
					</ul>
				</li>


				<li>
					<a href="#"><i class="fa  fa-info-circle -o fa-fw"></i> <?php echo $lng['information']?><span class="fa arrow"></span></a>
					<ul class="nav nav-second-level">
						<li>
							<a href="#" onclick="fc_navigate('pct')"><?php echo $lng['pct']?></a>
						</li>
						<li>
							<a href="#" onclick="fc_navigate('reduction')"><?php echo $lng['reduction']?></a>
						</li>
						<li>
							<a href="#" onclick="fc_navigate('information')"><?php echo $lng['information']?></a>
						</li>
						<li>
							<a href="#" onclick="fc_navigate('db_info')"><?php echo $lng['db_info']?></a>
						</li>
						<li>
							<a href="#" onclick="fc_navigate('statistic')"><?php echo $lng['statistic']?></a>
						</li>
						<li>
							<a href="#" onclick="fc_navigate('block_explorer')">Block explorer</a>
						</li>

					</ul>
				</li>


				<li>
					<a href="#"><i class="fa  fa-life-ring  fa-fw"></i> Help<span class="fa arrow"></span></a>
					<ul class="nav nav-second-level">
						<li>
							<a href="http://dcoin.me" target="_blank"><?php echo $lng['about_dcoin']?></a>
						</li>
						<li>
							<a href="http://en.dcoinwiki.com" target="_blank">Wiki</a>
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
							<a href="#" onclick="fc_navigate('abuse')"><?php echo $lng['complaints_miners']?></a>
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

<?php
require_once( ABSPATH . 'templates/modal.tpl' );
echo $modal;
?>