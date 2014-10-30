<link href="<?php echo $tpl['cf_url']?>css/cf.css" rel="stylesheet">
<style>
	<?php
	if (!$user_id) {
		echo "#dc_content{width:900px; margin:0 auto}\n";
		echo "#wrapper{background-color:#fff}\n";
		echo "#page-wrapper{margin:0}\n";
	}
	?>

	.list-inline {margin-left:0px}
</style>
<script>
	$(document).ready(function () {
		$.ajax({
			url: "http://pool.democratic-coin.com/ajax/get_ver.php",
			type: 'GET',
			dataType: "html",
			crossDomain: true,
			success: function (ver) {
				$( "#version" ).html( ver );
				$("#exe").attr("href", 'https://github.com/c-darwin/dcoin/releases/download/'+ver+'/Dcoin.exe');
			}
		});
	});
</script>

	<div style="float:left; width:900px; overflow:auto; text-align: center; padding-top: 100px">
			<div style="margin-bottom:5px">

					<a id="exe" href="https://github.com/c-darwin/dcoin/releases/download/v0.0.9b4/Dcoin.exe" class="btn btn-default btn-lg"><span class="network-name"><?php echo $lng['download']?></span></a>

			</div>
			<span style="color:#ccc">Windows <span id="version">v0.0.9b4</span></span><br><br><?php echo $lng['or']?>
			<div style="margin-top:10px">

					<a href="http://pool.dcoin.me" class="btn btn-default btn-lg"><span class="network-name"><?php echo $lng['open_in_the_pool']?></span></a>

			</div>
		<Br>
		<a href="#" onclick="history.go(-1);return false;">BACK</a>
	</div>



