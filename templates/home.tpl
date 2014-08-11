<script>
	var i=0;
	function doPoll() {
		$.post('ajax/my_notice.php', function(data) {
			poll_time = Date.now();
			i++;

			$('#main_status').html(data.main_status);
			if (data.main_status_complete!=1)
				$('#main_status').css("class", "list-group-item list-group-item-danger" );
			else
				$('#main_status').css("class", "list-group-item" );

			$('#account_status').text(data.account_status);
			$('#cur_block_id').text(data.cur_block_id);
			$('#connections').text(data.connections);
			$('#time_last_block').text(data.time_last_block);
			if (data.alert == 1 && i%2 == 0)
				$('#bar_alert').css("display", "block");
			else if (data.alert == 1 && i%2 != 0)
				$('#bar_alert').css("display", "none");

			setTimeout(doPoll,30000);

		}, 'json' );
	}

	if (Date.now() - poll_time > 10000)
		doPoll();

	$('#new_cf_project').bind('click', function () {

		$('#page-wrapper').spin();
		$( "#dc_content" ).load( "content.php", { tpl_name: "new_cf_project" }, function() {
			$.getScript("https://maps.googleapis.com/maps/api/js?sensor=false&callback=initialize", function(){
				$('#page-wrapper').spin(false);
			});
		});

	});
</script>

<link href="css2/cf.css" rel="stylesheet">
<h1 class="page-header">Home</h1>

<div id="message"></div>
	
<div id="generate">
	<div class="row" style="padding:0 15px">
		<div class="alert alert-danger">
			При помощи Dcoin можно как заработать деньги, так и потерять их. Подробно про риски можно почитать <a href="http://habrahabr.ru/company/dcoin/blog/229673/">тут</a>.
		</div>
	</div>

	<div class="row">
		<div class="col-lg-4">
			<h3>Панель</h3>
			<ul class="list-group">
				<li class="list-group-item" id="main_status"><?php echo $tpl['my_notice']['main_status']?></li>
				<li class="list-group-item"><?php echo $lng['account_status']?>: <span id="account_status"><?php echo $tpl['my_notice']['account_status'];?></span> <?php echo !empty($_SESSION['restricted'])?'restricted':'' ?> <?php echo defined('POOL_ADMIN')?'(Pool admin)':'' ?></li>
				<li class="list-group-item">User ID: <span id="user_id"><?php echo $user_id?></span></li>
				<li class="list-group-item">Статус демонов: <?php echo $tpl['demons_status']?></li>
				<li class="list-group-item"><?php echo $lng['number_of_blocks']?>: <span id="cur_block_id"><?php echo $tpl['my_notice']['number_of_blocks']?></span></li>
				<li class="list-group-item"><?php echo $lng['time_last_block']?>: <span id="time_last_block"><?php echo $tpl['my_notice']['time_last_block']?></span></li>
				<li class="list-group-item">Соединений: <span id="connections"><?php echo $tpl['my_notice']['connections']?></span></li>
			</ul>
		</div>
		<!-- /.col-lg-4 -->
		<div class="col-lg-4">
			<h3>Последние операции</h3>
			<div class="table-responsive table-bordered">
			<table class="table">
				<thead>
				<tr>
					<th></th>
					<th>Сумма</th>
					<th>Примечание</th>
					<th>Подтв.</th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td><i class="fa fa-minus"></i></td>
					<td>1000 DUSD</td>
					<td>Айфон 10G заказ #366125 Доставка домой</td>
					<td>0</td>
				</tr>
				<tr>
					<td><i class="fa fa-plus"></i></td>
					<td>50000 DRUB</td>
					<td></td>
					<td>11446</td>
				</tr>
				<tr>
					<td><i class="fa fa-plus"></i></td>
					<td>69333.12 DRUB</td>
					<td>Айфон 10G заказ #366125 Доставка домой</td>
					<td>66699</td>
				</tr>
				<tr>
					<td><i class="fa fa-plus"></i></td>
					<td>69333.12 DRUB</td>
					<td>Айфон 10G заказ #366125 Доставка домой</td>
					<td>66699</td>
				</tr>
				</tbody>
			</table>
			</div>
		</div>
		<!-- /.col-lg-4 -->
		<div class="col-lg-4">

			<h3>Балансы</h3>
			<div class="table-responsive table-bordered">
				<table class="table"><thead><tr><th>Валюта</th><th>Сумма</th><th>%/год</th></tr></thead><tbody><tr><td>DUSD</td><td>17.8</td><td>0</td></tr></tbody></table>
			</div>


		</div>
		<!-- /.col-lg-4 -->
	</div>
	<!-- /.row -->
	<div class="row">
		<div style="width:800px; overflow:auto; margin-left: 15px">
			<h3>CrowdFunding</h3>
			<?php
			foreach ($tpl['projects'] as $project_id=>$data) {
			?>
			<div class="well project-card" style="float:left; margin-right:20px; background-color: #fff">
				<a href="#" onclick="fc_navigate('cf_page_preview', {'only_project_id':<?php echo $project_id?>, 'lang_id':<?php echo $data['lang_id']?>})"><img src="<?php echo $data['blurb_img']?>" style="width:200px; height:310px"></a>
				<div>
					<div class="card-location" style="margin-top:10px;font-size: 13px; color: #828587;"><i class="fa  fa-map-marker  fa-fw"></i> <?php echo "{$data['country']},{$data['city']}"?></div>
					<div class="progress" style="height:5px; margin-top:10px; margin-bottom:10px"><div class="progress-bar progress-bar-success" style="width: <?php echo $data['pct']?>%;"></div></div>
					<div class="card-bottom">
						<div style="float:left; overflow:auto; padding-right:10px"><h5><?php echo $data['pct']?>%</h5>funded</div>
						<div style="float:left; overflow:auto; padding-right:10px"><h5><?php echo $data['funding_amount']?> DRUB </h5>pledged</div>
						<div style="float:left; overflow:auto;"><h5><?php echo $data['days']?></h5>days to go</div>
					</div>
				</div>
			</div>
			<?php
			}
		?>
			<button type="button" class="btn btn-primary" data-toggle="button"  id="new_cf_project">Запустите свой CrowdFunding проект!</button>
		</div>
	</div>



		<?php if (@$_SESSION['ADMIN']==1) {?>
			<br><br><br><br><button type="button" class="btn" data-toggle="button"  onclick="$.post('admin/content.php', { tpl_name: 'index', parameters: '' },
	              function(data) {
	              $('.fc_content').html( data );
	              }, 'html');" style="margin-left:30px">admin</button>
		<?php } ?>
	</div>

	<?php require_once( 'signatures.tpl' );?>

