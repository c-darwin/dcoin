<!-- container -->

<div class="container">
<script>
	function mining_click(id){
		fc_navigate('promised_amount_mining',  {'promised_amount_id':id, 'amount':$('#repaid-input-'+id).val()});
	}
	function change_amount_click(id){
		fc_navigate('change_promised_amount',  {'promised_amount_id':id, 'amount':$('#amount-input-'+id).val()});
	}

function clear_amount (id) {
	var amount = $("#"+id).val();
	var amount_ = '';
	amount_ = parseFloat(amount.replace(",", "."));
	amount_ = amount_.toFixed(2);

	if (amount.indexOf(",")!=-1) {
		$("#"+id).val(amount_);
	}
}

function calc_commission (id) {
	clear_amount ('repaid-input-'+id);
	var amount = $('#repaid-input-'+id).val();
	var commission = <?php echo $tpl['variables']['system_commission']?>;
	var new_amount = amount*(100-commission)/100;
	$("#commission-"+id).text('- commission 5% = '+new_amount.toFixed(2));
}

</script>
  
	<legend><h2><?php echo $lng['promised_amount_title']?></h2></legend>
	<?php echo ($tpl['alert'])?'<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>'.$tpl['alert'].'</div>':''?>


	<?php
	if (isset($tpl['promised_amount_list']['my_pending'])) {
		echo '<table class="table table-bordered" style="width:500px"><caption><h3>'.$lng['my_pending'].'</h3></caption>';
		echo '<thead><tr><th>ID</th><th>'.$lng['currency'].'</th><th>'.$lng['amount'].'</th></tr></thead>';
		echo '<tbody>';
		foreach($tpl['promised_amount_list']['my_pending'] as $data) {
		echo "<tr><td>{$data['id']}</td>
			<td>{$tpl['currency_list'][$data['currency_id']]}</td>
			<td>{$data['amount']}</td>
		</tr>";
		}
		echo '</tbody></table>';
	}

	if (isset($tpl['promised_amount_list']['accepted'])) {
			echo '<table class="table table-bordered" style="width:500px"><caption><h3>'.$lng['found_in_blocks'].'</h3></caption>';
			echo "<thead><tr><th>ID</th><th>{$lng['status']}</th><th>{$lng['currency']}</th><th style='text-align: center'>{$lng['amount']}</th><th>DC</th><th style='text-align: center'>{$lng['in_wallet']}</th><th>Max other currencies</th><th style='text-align:center'>{$lng['delete']}</th></tr></thead>";
			echo '<tbody>';
			foreach($tpl['promised_amount_list']['accepted'] as $data) {
					echo "<tr>";
					echo "<td>{$data['id']}</td>";
					echo "<td>{$data['status_text']}</td>";
					echo "<td>{$tpl['currency_list'][$data['currency_id']]}</td>";
					if ($data['currency_id'] ==1 || $data['status']=='repaid')
						echo "<td style='text-align: center'>{$data['amount']}</td>";
					else
						echo "<td style='text-align: center'><input type='text' class='input-mini' id='amount-input-{$data['id']}' onkeyup=\"clear_amount('amount-input-{$data['id']}')\" value='{$data['amount']}'><br><button onclick=\"change_amount_click({$data['id']})\" class='btn' style='width:70px'>{$lng['change']}</button>(max: {$data['max_amount']})</td>";
					echo "<td>{$data['tdc']}</td>";
					echo "<td style='text-align: center'><input type='text' class='input-mini' id='repaid-input-{$data['id']}' onkeyup=\"calc_commission('{$data['id']}')\"><br><span id='commission-{$data['id']}'></span><br><button onclick=\"mining_click({$data['id']})\" class='btn' style='width:130px'>{$lng['put_in_a_wallet']}</button></td>";
					echo "<td>{$data['max_other_currencies']}</td>";
					if ($data['currency_id'] > 1)
						echo "<td><a href='#' onclick=\"fc_navigate('promised_amount_delete', {'del_id':'".$data['id']."'})\">Del</a></td>";
					else
						echo "<td></td>";
					echo "</tr>";
			}
			echo '</tbody>';
			echo '</table>';
	}
	?>
<button  onclick="fc_navigate('promised_amount_add')" class="btn"><?php echo $lng['add_note']?></button>
<br><br><p><span class="label label-important"><?php echo $lng['limits'] ?></span>  <?php echo $tpl['limits_text'] ?></p>
<br>
<a href="#" onclick="fc_navigate('for_repaid_fix')">for_repaid_fix</a>
</div>
<!-- /container -->