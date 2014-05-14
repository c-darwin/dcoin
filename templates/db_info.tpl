<!-- container -->
<div class="container">

	<legend><h2><?php echo $lng['db_info']?></h2></legend>

	<p><?php echo date('d-m-Y H:i:s')?></p>

	<?php
	echo '<table class="table table-bordered" style="width:600px"><caption>daemons</caption>';
	echo '<thead><tr><th>Script</th><th>Time</th><th>Memory (mb)</th><th>Restart</th></tr></thead>';
	echo '<tbody>';
	foreach ( $tpl['demons'] as $data ) {
	print "<tr><td>{$data['script']}</td><td>{$data['time']}</td><td>{$data['memory']}</td><td>{$data['restart']}</td></tr>";
	}
	echo '</tbody>';
	echo '</table>';
	?>

	<?php
	echo '<table class="table table-bordered" style="width:600px"><caption>nodes_ban</caption>';
	echo '<thead><tr><th>host</th><th>user_id</th><th>ban_start</th><th>info</th></tr></thead>';
	echo '<tbody>';
	foreach ( $tpl['nodes_ban'] as $data ) {
	print "<tr><td>{$data['host']}</td><td>{$data['user_id']}</td><td>{$data['ban_start']}</td><td>{$data['info']}</td></tr>";
	}
	echo '</tbody>';
	echo '</table>';
	?>


	<?php
	echo '<table class="table table-bordered" style="width:600px"><caption>nodes_connection</caption>';
	echo '<thead><tr><th>host</th><th>user_id</th></thead>';
	echo '<tbody>';
	foreach ( $tpl['nodes_connection'] as $data ) {
	print "<tr><td>{$data['host']}</td><td>{$data['user_id']}</td></tr>";
	}
	echo '</tbody>';
	echo '</table>';
	?>

	<?php
	echo '<table class="table table-bordered" style="width:600px"><caption>main_lock</caption>';
	echo '<thead><tr><th>lock_time</th><th>script_name</th></thead>';
	echo '<tbody>';
	if (isset($tpl['main_lock']))
		foreach ( $tpl['main_lock'] as $data ) {
			print "<tr><td>{$data['lock_time']}</td><td>{$data['script_name']}</td></tr>";
		}
	echo '</tbody>';
	echo '</table>';
	?>

	<?php
	echo '<table class="table table-bordered" style="width:600px"><caption>other</caption>';
	echo '<thead><tr><th>name</th><th>value</th></thead>';
	echo '<tbody>';
	print "<tr><td>queue_tx</td><td>{$tpl['queue_tx']}</td></tr>";
	print "<tr><td>transactions_testblock</td><td>{$tpl['transactions_testblock']}</td></tr>";
	print "<tr><td>transactions</td><td>{$tpl['transactions']}</td></tr>";
	echo '</tbody>';
	echo '</table>';
	?>



</div>

<!-- /container -->