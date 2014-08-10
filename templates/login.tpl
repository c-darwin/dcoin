<style>
	#page-wrapper{
		margin: 0px 200px 0px 200px;
		border: 1px solid #E7E7E7;
		min-height: 550px;
	}
	#wrapper{height: 100%;}
	#dc_content{
		height: 550px;
		text-align: center;
		vertical-align: middle;
	}
</style>
	<!--<p><?php echo $lng['login_text']?></p>-->

<div style="margin-left: -32px; width:65px;position: absolute;top: 50%;left: 50%;  ">
	<button type="button" class="btn btn-primary btn-lg" id="show_login">Войти</button>
</div>

<?php if (!$user_id) echo '<div class="alert alert-info" style="width: 500px;position:absolute; bottom:0; left: 50%; margin-left: -250px;">Если у Вас нет ключа, Вы может попросить его на <a href="http://dcoinforum.org/" style="text-decoration: underline">форуме</a></div>' ?>


	<script>
		$('#show_login').bind('click', function () {
			$('#myModal').modal('show');
		});
	</script>
	<div class="for-signature"></div>
