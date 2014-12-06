<script>
	$('#lang-select').bind('change', function () {
		$('#lang').val($(this).val());
	});

	$('#next').bind('click', function () {
		fc_navigate('install_step_1',  {'lang':$('#lang').val()});
	});

</script>
<style>
	#page-wrapper{
		margin: 0px 10% 0px 10%;
		border: 1px solid #E7E7E7;
		min-height: 550px;
	}
	#wrapper{height: 100%;}
	#dc_content{
		height: 550px;
		vertical-align: middle;
	}
</style>
<div style="max-width: 600px; margin: auto; margin-top: 50px">

	Select a language<br>
		<select id="lang-select" class="form-control" style="width: 150px " >
			<option value="1">English</option>
			<option value="42">Russian</option>
		</select><br>
		<input id="lang" type="hidden" value="1">
		<button class="btn btn-outline btn-primary" id="next"><?php echo $lng['next']?></button>
	</form>

</div>