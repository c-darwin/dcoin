<script>
$('#show_block').bind('click', function () {
	fc_navigate('block_explorer', {'block_id':$('#block_id').val()});
});
</script>

<link href="css/cf.css?2" rel="stylesheet">

<h1 class="page-header">Block explorer</h1>

<?php
if (!$tpl['start'] && !$tpl['block_id']) {
?>
	<div id="search_block">
		<div class="form-group">
			<label>Search block</label>
			<input class="form-control" style="width: 300px" id="block_id" placeholder="block_id">
		</div>
		<div class="form-group">
			<button id="show_block" class="btn btn-primary" type="button">OK</button>
		</div>
	</div>
<?php
}
?>

<?php
if ($tpl['block_id']) {
?>
	<ol class="breadcrumb">
	<li><a href="#"onclick="fc_navigate('block_explorer')">Block exporer</a></li>
	<li class="active"><?php echo $tpl['block_id']?></li>
	</ol>
<?php
}
?>
<?php echo $tpl['data']?>
