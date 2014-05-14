<!-- container -->
<div class="container">

<script>

var json_data = '';
$('#next').bind('click', function () {

	$("#voting").css("display", "none");
	$("#sign").css("display", "block");
	var data = '';
	$("input[type=text],input[type=hidden],select", $("#voting")).each(function(){
		if ($(this).attr('name')=='currency_id'){
			currency_id = $(this).val();
			data=data+'"'+currency_id+'":';
		}
		if ($(this).attr('name')=='miner_pct')
			data=data+'['+$(this).val()+',';
		if ($(this).attr('name')=='user_pct')
			data=data+''+$(this).val()+',';
		if ($(this).attr('name')=='max_promised_amount')
			data=data+''+$(this).val()+',';
		if ($(this).attr('name')=='max_other_currencies')
			data=data+''+$(this).val()+',';
		if ($(this).attr('name')=='reduction')
			data=data+''+$(this).val()+'],';
	} );
	json_data = '{'+data.substr(0, data.length-1)+'}';

	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"?>,'+json_data);
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>
} );

$('#send_to_net').bind('click', function () {
	
	$.post( 'ajax/save_queue.php', {
				'type' : '<?php echo $tpl['data']['type']?>',
				'time' : '<?php echo $tpl['data']['time']?>',
				'user_id' : '<?php echo $tpl['data']['user_id']?>',
				'currency_id' : $('#currency_id').val(),
				'json_data' : json_data,
							'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
			}, function (data) {
				fc_navigate ('voting', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
			}
	);

});

function getPct (pct) {
	if (!pct)
		pct = 0;
	for (i=0; i<sortable.length; i++){
		sortable[i][0] = parseFloat(sortable[i][0]);
		if (sortable[i][0]==pct) {
			pct = sortable[i][0];
			console.log('break1 '+pct);
			break;
		}
		if (sortable[i][0]>pct) {
			pct = sortable[i-1][0];
			console.log('break2 '+pct);
			break;
		}
	}
	if (pct > sortable[sortable.length-1][0])
		pct = sortable[sortable.length-1][0];
	return pct;
}

js_pct = <?php echo $tpl['js_pct']?>;
var sortable = [];
for (var vehicle in js_pct)
	sortable.push([vehicle, js_pct[vehicle]])
sortable.sort(function(a, b) {return a[1] - b[1]});
/*
var miner_pct_sec = [];
$('input[name="miner_pct"]').bind('keyup', function(event) {
	$("input[type=text],input[type=hidden]", $("#voting")).each(function() {
		if ($(this).attr('name')=='currency_id')
			currency_id = $(this).val();
		if ($(this).attr('name')=='miner_pct') {
			miner_pct = parseFloat($(this).val());
			if (miner_pct>1000)
				miner_pct = 1000;
			miner_pct = getPct (miner_pct);
			miner_pct_sec[currency_id] = js_pct[miner_pct];
			$(this).val(miner_pct);
		}
	} );
});*/
/*
var user_pct_sec = [];
$('input[name="user_pct"]').bind('keyup', function(event) {
	$("input[type=text],input[type=hidden]", $("#voting")).each(function() {
		if ($(this).attr('name')=='currency_id')
			currency_id = $(this).val();
		if ($(this).attr('name')=='user_pct') {
			user_pct = parseFloat($(this).val());
			if (user_pct>1000)
				user_pct = 1000;
			user_pct = getPct (user_pct);
			user_pct_sec[currency_id] = js_pct[user_pct];
			$(this).val(user_pct);
		}
	} );
});
*/

ArraySort = function(array, sortFunc){
	var tmp = [];
	var aSorted=[];
	var oSorted={};

	for (var k in array) {
		if (array.hasOwnProperty(k))
			tmp.push({key: k, value:  array[k]});
	}

	tmp.sort(function(o1, o2) {
		return sortFunc(o1.value, o2.value);
	});

	if(Object.prototype.toString.call(array) === '[object Array]'){
		$.each(tmp, function(index, value){
			aSorted.push(value.value);
		});
		return aSorted;
	}

	if(Object.prototype.toString.call(array) === '[object Object]'){
		$.each(tmp, function(index, value){
			oSorted[value.key]=value.value;
		});
		return oSorted;
	}
};


</script>
	<legend><h2><?php echo $lng['voting']?></h2></legend>

	<?php require_once( ABSPATH . 'templates/alert_success.php' );?>

<div id="voting">
	<?php
	if ($tpl['promised_amount_currency_list']) {
	?>
	<?php echo $lng['voting_message']?>
	<table class="table" style="width: 500px">
		<tr><th><?php echo $lng['currency']?></th><th><?php echo $lng['voting_miner_pct']?></th><th><?php echo $lng['voting_user_pct']?></th><th><?php echo $lng['voting_max_promised_amount']?></th><th><?php echo $lng['voting_max_other_currencies']?></th><th><?php echo $lng['voting_reduction']?></th></tr>
		<?php
		 foreach($tpl['promised_amount_currency_list'] as $currency_id=>$data) {
			print "<tr><td>{$data['name']}<input type='hidden' name='currency_id' value='{$currency_id}'></td><td><select style='width: 80px' name='miner_pct'>";
			foreach($tpl['AllPct'] as $pct_y=>$pct_sec)
				print "<option value='{$pct_sec}'>{$pct_y}</option>";
			print "</select></td><td><select style='width: 80px' name='user_pct'>";
			foreach($tpl['AllPct'] as $pct_y=>$pct_sec)
				print "<option value='{$pct_sec}'>{$pct_y}</option>";
			print "</select></td><td><select style='width: 80px' name='max_promised_amount'>";
			foreach($tpl['AllMaxPromisedAmount'] as $amount)
				print "<option>{$amount}</option>";
			print "</select></td><td><select style='width: 80px' name='max_other_currencies'>";
			for ($i=0; $i<$tpl['max_currency_id']; $i++)
				print "<option>{$i}</option>";
			print "</select></td><td><select style='width: 80px' name='reduction'><option>0</option><option>10</option><option>25</option><option>50</option><option>90</option></select></td></tr>";
		}
		?>
	</table>
	<div class="control-group">
		<div class="controls">
			<button class="btn" type="button" id="next"><?php echo $lng['next']?></button>
		</div>
	</div>
	<?php
	}
	else if ($tpl['wait_voting']) {
		print '<table class="table" style="width: 500px"><tr><th>'.$lng['currency'].'</th><th>Text</th><th></tr>';
		foreach($tpl['wait_voting'] as $currency_id=>$data) {
			print "<tr><td>{$tpl['currency_list'][$currency_id]}</td><td>{$data}</td></tr>";
		}
		print '</table>';
	}
	else
		print 'empty';
	?>
</div>

	<?php require_once( 'signatures.tpl' );?>

    
</div>
<!-- /container -->