<!-- container -->
<div class="container">

	<legend><h2>Логи нода</h2></legend>
	
	<?php
	echo '<table class="table table-bordered" style="width:200px">';
	echo '<thead><tr><th>ID</th><th>Time</th><th>Data</th></tr></thead>';
	echo '<tbody>';
	for ($i=0; $i<sizeof($tpl['log']); $i++) {
	
		print "<tr><td>{$tpl['log'][$i]['id']}</td><td>{$tpl['log'][$i]['time']}</td><td>{$tpl['log'][$i]['data']}</td></tr>";
	
	}
	echo '</tbody>';
	echo '</table>';
	?>
     

</div>
<!-- /container -->