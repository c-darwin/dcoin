
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
	//var commission = <?php echo $tpl['variables']['system_commission']?>;
	//var new_amount = amount*(100-commission)/100;
	//$("#commission-"+id).text('- commission 5% = '+new_amount.toFixed(2));
}

console.log('intervalIdArray='+intervalIdArray);
if (typeof intervalIdArray != "undefined") {
	for (i=0; i<intervalIdArray.length; i++)
		clearInterval(intervalIdArray[i]);
}
var intervalIdArray = [];
function dc_counter(amount, pct, currency_id)
{
	var i=0;
	pct = pct / 3;

	var intervalID = setInterval( function() {
		i++;
		//console.log(i);
		var new_amount =  Math.pow(1+pct, i) * amount;
		$('#'+currency_id).text(new_amount.toFixed(5));
	} , 300);
	intervalIdArray.push(intervalID);
}

$("#main_div select").addClass( "form-control" );
$("#main_div input").addClass( "form-control" );
$("#main_div button").addClass( "btn-outline btn-primary" );
$("#main_div .put_in_the_wallet").width( 130 );
$("#main_div .amount").width( 70 );

</script>
<div id="main_div">
<h1 class="page-header"><?php echo $lng['promised_amount_title']?></h1>
<ol class="breadcrumb">
	<li><a href="#" onclick="fc_navigate('mining_menu')"><?php echo $lng['mining'] ?></a></li>
	<li class="active"><?php echo $lng['promised_amount_title'] ?></li>
</ol>

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
			echo '<table class="table" style="width:500px"><!--<caption><h3>'.$lng['found_in_blocks'].'</h3></caption>-->';
			echo "<thead><tr><th>ID</th><th>{$lng['status']}</th><th style='text-align: center'>{$lng['currency']}</th><th style='text-align: center'>{$lng['amount']}</th><!--<th style='text-align: center'>{$lng['pct_year']}</th>--><th>DC</th><th style='text-align: center'>{$lng['in_wallet']}</th><!--<th>{$lng['max_other_currencies']}</th>--><th style='text-align:center'></th></tr></thead>";
			echo '<tbody>';
			$js = '';
			foreach($tpl['promised_amount_list']['accepted'] as $data) {
					$to_wallet = 0;
					if ($data['tdc'] > 0.01)
						$to_wallet = $data['tdc']-0.01;
					echo "<tr>";
					echo "<td>{$data['id']}</td>";
					echo "<td>{$data['status_text']}</td>";
					echo "<td style='text-align: center'>{$tpl['currency_list'][$data['currency_id']]}</td>";
					if ($data['currency_id'] ==1 || $data['status']=='repaid')
						echo "<td style='text-align: center'>{$data['amount']}</td>";
					else
						echo "<td style='text-align: center'><input type='text' class='amount' id='amount-input-{$data['id']}' onkeyup=\"clear_amount('amount-input-{$data['id']}')\" value='{$data['amount']}'><br><button onclick=\"change_amount_click({$data['id']})\" class='btn' style='width:74px'>{$lng['change']}</button>(max: {$data['max_amount']})</td>";
					//echo "<td style='text-align: center'>{$data['pct']}</td>";
					if ($data['currency_id']==1)
						$color = '#428BCA';
					else
						$color = 'green';
					echo "<td id='currency_{$data['currency_id']}_{$data['status']}' style='color: {$color}; font-weight: bold; font-size: 15px'>{$data['tdc']}</td>";
					echo "<td style='text-align: center'><input type='text' class='input-mini' id='repaid-input-{$data['id']}' onkeyup=\"calc_commission('{$data['id']}')\" value='{$to_wallet}'><br><span id='commission-{$data['id']}'></span><button  onclick=\"mining_click({$data['id']})\" class='btn put_in_the_wallet' style='width:130px'>{$lng['put_in_the_wallet']}</button></td>";
					//echo "<td>{$data['max_other_currencies']}</td>";
					if ($data['currency_id'] > 1)
						echo "<td><a class=\"btn btn-outline btn-danger\" href=\"#\" onclick=\"fc_navigate('promised_amount_delete', {'del_id':'".$data['id']."'})\"><i class=\"fa fa-trash-o fa-lg\"></i> {$lng['delete']}</a></td>";
					else
						echo "<td></td>";
					echo "</tr>";
					if ($data['pct_sec']>0)
						$js.="dc_counter({$data['tdc']}, {$data['pct_sec']}, 'currency_{$data['currency_id']}_{$data['status']}');\n";
			}
			echo '</tbody>';
			echo '</table>';
			echo "<script>{$js}</script>";
	}
	?>
<button  onclick="fc_navigate('promised_amount_add')" class="btn"><?php echo $lng['add_note']?></button>
<?php
if (isset($tpl['actualization_promised_amounts']))
	print '<button  onclick="fc_navigate(\'promised_amount_actualization\')" class="btn">'.$lng['actualize_promised_amounts'].'</button>';
?>
<br><br><div class="alert alert-info"><strong><?php echo $lng['limits'] ?></strong>  <?php echo $tpl['limits_text'] ?></div>
<br>
<a href="#" onclick="fc_navigate('for_repaid_fix')">for_repaid_fix</a>
</div>