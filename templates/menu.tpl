    <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
         
          <div class="nav-collapse collapse">
            
            
            <ul class="nav">
              <li class="active"><a href="#" onclick="fc_navigate('home')">Home</a></li>
             
              <li><a href="#" onclick="fc_navigate('wallets_list')"><?php echo $lng['wallet']?></a></li>
              
				<li class="dropdown">
					<a class="dropdown-toggle" data-toggle="dropdown" href="#"><?php echo $lng['mining']?><b class="caret"></b></a>
					<ul class="dropdown-menu">
						<li><a href="#" onclick="fc_navigate('upgrade')"><?php echo $lng['upgrade_to_miner']?></a></li>
						<li><a href="#" onclick="fc_navigate('voting')"><?php echo $lng['voting']?></a></li>
						<li><a href="#" onclick="fc_navigate('geolocation')"><?php echo $lng['geolocation'] ?></a></li>
						<li><a href="#" onclick="fc_navigate('promised_amount_list')"><?php echo $lng['promised_amounts'] ?></a></li>
						<li><a href="#" onclick="fc_navigate('cash_requests_in')"><?php echo $lng['inbox']?></a></li>
						<li><a href="#" onclick="fc_navigate('cash_requests_out')"><?php echo $lng['outgoing']?></a></li>
						<li><a href="#" onclick="fc_navigate('holidays_list')"><?php echo $lng['holidays']?></a></li>
						<li><a href="#" onclick="fc_navigate('points')"><?php echo $lng['points']?></a></li>
						<li><a href="#" onclick="fc_navigate('pct')"><?php echo $lng['pct']?></a></li>
					</ul>
				</li>
              
              <li><a href="#" onclick="fc_navigate('tasks')"><?php echo $lng['tasks']?></a></li>

				<li class="dropdown">
					<a class="dropdown-toggle" data-toggle="dropdown" href="#"><?php echo $lng['settings']?><b class="caret"></b></a>
					<ul class="dropdown-menu">
						<li><a href="#" onclick="fc_navigate('node_config')"><?php echo $lng['config_node']?></a></li>
						<li><a href="#" onclick="fc_navigate('change_primary_key')"><?php echo $lng['change_master_key']?></a></li>
						<li><a href="#" onclick="fc_navigate('change_node_key')"><?php echo $lng['change_node_key']?></a></li>
						<li><a href="#" onclick="fc_navigate('change_host')"><?php echo $lng['change_host']?></a></li>
						<li><a href="#" onclick="fc_navigate('notifications')"><?php echo $lng['sms_and_email_notifications']?></a></li>
					</ul>
				</li>  
              
				<li class="dropdown">
					<a class="dropdown-toggle" data-toggle="dropdown" href="#"><?php echo $lng['other']?><b class="caret"></b></a>
					<ul class="dropdown-menu">
						<li><a href="#" onclick="fc_navigate('new_user')"><?php echo $lng['reg_users']?></a></li>
						<li><a href="#" onclick="fc_navigate('change_commission')"><?php echo $lng['commission']?></a></li>
						<!--<li><a href="#" onclick="fc_navigate('api')">Api</a></li>-->
						<li><a href="#" onclick="fc_navigate('information')"><?php echo $lng['information']?></a></li>
						<li><a href="#" onclick="fc_navigate('db_info')"><?php echo $lng['db_info']?></a></li>
						<li><a href="#" onclick="fc_navigate('abuse')"><?php echo $lng['complaints_miners']?></a></li>
						<li><a href="#" onclick="fc_navigate('bug_reporting')"><?php echo $lng['bug_reporting']?></a></li>
						<li><a href="#" onclick="fc_navigate('start_stop')"><?php echo $lng['start_stop']?></a></li>
						<li><a href="#" onclick="fc_navigate('nulling')"><?php echo $lng['nulling']?></a></li>
					</ul>
				</li>
            </ul>
     
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>