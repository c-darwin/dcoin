<script>
		
function add_holidays() {

	$("#add_holidays").css("display", "none");
	$("#sign").css("display", "block");
	$("#for-signature").val( '<?php echo "{$tpl['data']['type_id']},{$tpl['data']['time']},{$tpl['data']['user_id']}"?>,'+startDateTextBox.datetimepicker("getDate").getTime() / 1000+','+endDateTextBox.datetimepicker("getDate").getTime() / 1000 );
	doSign();
	<?php echo !defined('SHOW_SIGN_DATA')?'$("#send_to_net").trigger("click");':'' ?>

}

$('#send_to_net').bind('click', function () {

		$.post( 'ajax/save_queue.php', {
				'type' : '<?php echo $tpl['data']['type']?>',
				'time' : '<?php echo $tpl['data']['time']?>',
				'user_id' : '<?php echo $tpl['data']['user_id']?>',
				'start_time' :  startDateTextBox.datetimepicker("getDate").getTime() / 1000,
				'end_time' :  endDateTextBox.datetimepicker("getDate").getTime() / 1000,
							'signature1': $('#signature1').val(),
			'signature2': $('#signature2').val(),
			'signature3': $('#signature3').val()
		});
		fc_navigate ('holidays_list', {'alert': '<?php echo $lng['sent_to_the_net'] ?>'} );
});


var startDateTextBox = $('#hol_time_start');
var endDateTextBox = $('#hol_time_end');

startDateTextBox.datetimepicker({
	numberOfMonths: 2,
	minDate: 0,
	maxDate: 365,
	timeFormat: 'HH:mm',
	onClose: function(dateText, inst) {
		if (endDateTextBox.val() != '') {
			var testStartDate = startDateTextBox.datetimepicker('getDate');
			var testEndDate = endDateTextBox.datetimepicker('getDate');
			if (testStartDate > testEndDate)
				endDateTextBox.datetimepicker('setDate', testStartDate);
		}
		else {
			endDateTextBox.val(dateText);
		}

	},
	onSelect: function (selectedDateTime){
		endDateTextBox.datetimepicker('option', 'minDate', startDateTextBox.datetimepicker('getDate') );
	}
});
endDateTextBox.datetimepicker({
	numberOfMonths: 2,
	minDate: 0,
	maxDate: 365,
	timeFormat: 'HH:mm',
	onClose: function(dateText, inst) {
		if (startDateTextBox.val() != '') {
			var testStartDate = startDateTextBox.datetimepicker('getDate');
			var testEndDate = endDateTextBox.datetimepicker('getDate');
			if (testStartDate > testEndDate)
				startDateTextBox.datetimepicker('setDate', testEndDate);
		}
		else {
			startDateTextBox.val(dateText);
		}



	},
	onSelect: function (selectedDateTime){
		startDateTextBox.datetimepicker('option', 'maxDate', endDateTextBox.datetimepicker('getDate') );
	}
});

$("#main_div select").addClass( "form-control" );
$("#main_div input").addClass( "form-control" );
$("#main_div button").addClass( "btn-outline btn-primary" );
</script>

<div id="main_div">
<h1 class="page-header"><?php echo $lng['new_holidays_title'] ?></h1>
<ol class="breadcrumb">
	<li><a href="#mining_menu"><?php echo $lng['mining'] ?></a></li>
	<li><a href="#holidays_list"><?php echo $lng['holidays_title'] ?></a></li>
	<li class="active"><?php echo $lng['new_holidays_title'] ?></li>
</ol>

    <div id="add_holidays">

		<div id="start"></div>

		<form class="form-inline">
		<label><?php echo $lng['start_time'] ?>:</label> <input type="text" name="hol_time_start" id="hol_time_start" value="" class="input-medium"/>

		<label><?php echo $lng['end_time'] ?>:</label> <input type="text" name="hol_time_end" id="hol_time_end" value="" class="input-medium" />
		</form>

		<br>
		<button class="btn" onclick="add_holidays()"><?php echo $lng['next'] ?></button>

    </div>

	<?php require_once( 'signatures.tpl' );?>
</div>