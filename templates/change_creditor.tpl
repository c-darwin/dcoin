<div id="main_div">
	<h1 class="page-header"><?php echo $lng['credits']?></h1>
	<ol class="breadcrumb">
		<li><a href="#"onclick="fc_navigate('wallets_list')"><?php echo $lng['wallets']?></a></li>
		<li class="active"><?php echo $lng['credits']?></li>
	</ol>

	<h3>Мне должы</h3>
	<table class="table" style="width:500px">
	<?php
		echo '<tr><th>'.$lng['time'].'</th><th>'.$lng['amount'].'</th><th>'.$lng['currency'].'</th><th>User_ID</th><th>'.$lng['action'].'</th></tr>';
		foreach ($tpl['I_creditor'] as $data) {
			echo "<tr>";
			echo "<td>{$data['time']}</td>";
			echo "<td>{$data['amount']}</td>";
			echo "<td>D{$tpl['currency_list'][$data['currency_id']]}</td>";
			echo "<td>{$data['from_user_id']}</td>";
			echo "<td><button type='button' class='btn btn-default'>Передать</button></td>";
		}
	?>
	</table>

	<br>
	<h3>Я должен</h3>
	<table class="table" style="width:500px">
		<?php
		echo '<tr><th>'.$lng['time'].'</th><th>'.$lng['amount'].'</th><th>'.$lng['currency'].'</th><th>User_ID</th></tr>';
		foreach ($tpl['I_debtor'] as $data) {
			echo "<tr>";
			echo "<td>{$data['time']}</td>";
			echo "<td>{$data['amount']}</td>";
			echo "<td>D{$tpl['currency_list'][$data['currency_id']]}</td>";
			echo "<td>{$data['to_user_id']}</td>";
		}
		?>
	</table>



</div>