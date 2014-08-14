<link href="css2/cf.css" rel="stylesheet">

<h1 class="page-header"><?php echo $lng['cf_projects_title']?></h1>
<?php
if ($tpl['cur_category']) {
	?>
	<ol class="breadcrumb">
		<li><a href="#">CrowdFunding</a></li>
		<li><a href="#"onclick="fc_navigate('cf_catalog')"><?php echo $lng['catalog']?></a></li>
		<li class="active"><?php echo $lng['cf_category'][$tpl['category_id']]?></li>
	</ol>
	<?php
}
?>

<!--	<div class="width_max" style="margin-bottom:70px">
		<ul class="nav navbar-nav navbar-left" style="padding-top:10px">
			<button type="button" class="btn btn-outline btn-default">Explore</button>
			<button type="button" class="btn btn-outline btn-default">Start your campaign</button>
		</ul>

		<ul class="nav navbar-nav navbar-right">
			<li class="dropdown">
				<a href="#" class="dropdown-toggle" data-toggle="dropdown">Language <span class="caret"></span></a>
				<ul class="dropdown-menu" role="menu">
					<li><a href="#">Русский</a></li>
					<li><a href="#">English</a></li>
				</ul>
			</li>
		</ul>

	</div>
-->
	<div style="float:left; width:900px; overflow:auto;">
		<div style="float:left; width:720px; overflow:auto; min-height: 800px">
		<?php
			if ($tpl['projects'] )
			foreach ($tpl['projects'] as $project_id=>$data) {
			?>
			<div class="well project-card" style="float:left; margin-right:20px">
				<a href="#" onclick="fc_navigate('cf_page_preview', {'only_project_id':<?php echo $project_id?><?php echo $data['lang_id']?", 'lang_id':{$data['lang_id']}":""?>})"><img src="<?php echo $data['blurb_img']?>" style="width:200px; height:310px"></a>
				<div>
					<div class="card-location" style="margin-top:10px;font-size: 13px; color: #828587;"><i class="fa  fa-map-marker  fa-fw"></i> <?php echo "{$data['country']},{$data['city']}"?></div>
					<div class="progress" style="height:5px; margin-top:10px; margin-bottom:10px"><div class="progress-bar progress-bar-success" style="width: <?php echo $data['pct']?>%;"></div></div>
					<div class="card-bottom">
						<div style="float:left; overflow:auto; padding-right:10px"><h5><?php echo $data['pct']?>%</h5>funded</div>
						<div style="float:left; overflow:auto; padding-right:10px"><h5><?php echo $data['funding_amount']?> D<?php echo $tpl['currency_list'][$data['currency_id']]?> </h5>pledged</div>
						<div style="float:left; overflow:auto;"><h5><?php echo $data['days']?></h5>days to go</div>
					</div>
				</div>
			</div>
			<?php
			}
		?>
		</div>

		<div class="menu">

			<h3><i class="fa  fa-folder-open-o  fa-fw"></i> Categories</h3>
			<ul class="navigation">
				<?php

				foreach ($lng['cf_category'] as $id=>$name )
					echo "<li><a href='#' onclick=\"fc_navigate('cf_catalog', {'category_id':{$id}})\">{$name}</a></li>";
				?>
			</ul>
		</div>
	</div>



