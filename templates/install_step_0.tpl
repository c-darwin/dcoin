<!-- container -->
<div class="container">
<script>
	$('#lang-select').bind('change', function () {
		$('#lang').val('lang='+$(this).val());
	});

	$('#next').bind('click', function () {
		fc_navigate('install_step_1',  $('#lang').val());
	});

</script>

	Select a language<br>
		<select id="lang-select" class="form-control" style="width: 150px " >
			<option value="en">English</option>
			<option value="ru">Russian</option>
		</select><br>
		<input id="lang" type="hidden" value="lang=en">
		<button class="btn btn-outline btn-primary" id="next"><?php echo $lng['next']?></button>
	</form>

</div>