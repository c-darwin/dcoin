<script>
$('#show_block').bind('click', function () {
	fc_navigate('block_explorer', {'block_id':$('#goto_block_id').val()});
});

function my_notice() {
	$('#wrapper').spin();
	$.post('ajax/my_notice.php', function(data) {
		//poll_time = Date.now();
		//i++;

		$('#main_status').html(data.main_status);
		if (data.main_status_complete!=1)
			$("#main_status").css({ 'color': 'red'});
		else
			$("#main_status").css({ 'color': '#333'});

		$('#account_status').text(data.account_status);
		if (data.account_status=='Miner') {
			$('#account_status').css("color", "green");
			$('#account_status').css("font-weight", "bold");
		}
		$('#cur_block_id').text(data.cur_block_id);
		$('#connections').text(data.connections);

		console.log(data.time_last_block_int);
		var time = Number(data.time_last_block_int + '000');
		console.log(time);
		var d = new Date(time);
		$('#time_last_block_int').text(d);
		if (data.alert == 1 && i%2 == 0)
			$('#bar_alert').css("display", "block");
		else if (data.alert == 1 && i%2 != 0)
			$('#bar_alert').css("display", "none");

		//setTimeout(doPoll,30000);
		$('#wrapper').spin(false);

	}, 'json' );
}

$('#panel_refresh').bind('click', function () {
	my_notice();
	return false;
});
my_notice();
</script>

<link href="css/cf.css?2" rel="stylesheet">

<h1 class="page-header">Block explorer</h1>


<?php
if (!$tpl['start'] && !$tpl['block_id']) {
?>
	<div>
	<ul class="list-group">
		<li class="list-group-item" id="main_status"><?php echo $tpl['my_notice']['main_status']?></li>
		<li class="list-group-item"><?php echo $lng['account_status']?>: <span id="account_status"><?php echo $tpl['my_notice']['account_status'];?></span> <?php echo !empty($_SESSION['restricted'])?'restricted':'' ?> <?php echo defined('POOL_ADMIN')?'(Pool admin)':'' ?></li>
		<li class="list-group-item">User ID: <span id="user_id"><?php echo $user_id?></span></li>
		<li class="list-group-item"><?php echo $lng['number_of_blocks']?>: <span id="cur_block_id">0</span></li>
		<li class="list-group-item"><?php echo $lng['time_last_block']?>: <span id="time_last_block_int" class="unixtime"><?php echo $tpl['my_notice']['time_last_block_int']?></span></li>
	</ul>
	</div>
	<div style="clear: both"></div>
	<div id="search_block">
		<div class="form-group">
			<label>Search block</label>
			<input class="form-control" style="width: 300px" id="goto_block_id" placeholder="block_id">
		</div>
		<div class="form-group">
			<button id="show_block" class="btn btn-primary" type="button">OK</button>
		</div>
	</div>
<?php
}
?>

<?php
if ($tpl['block_id']) {
?>
	<ol class="breadcrumb">
	<li><a href="#block_explorer">Block explorer</a></li>
	<li class="active"><?php echo $tpl['block_id']?></li>
	</ol>
<?php
}
?>
<?php echo $tpl['data']?>

<script src="js/unixtime.js"></script>