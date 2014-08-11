<h1 class="page-header"><?php echo $lng['mining']?></h1>

<div class="panel panel-primary">
	<div class="panel-heading">
		Как майнить?
	</div>
	<div class="panel-body">
		<ul class="list-group" style="margin-bottom: 0px">

			<li class="list-group-item"><i class="fa  fa-check-square-o  fa-lg"></i> Сделайте <a href="#" onclick="fc_navigate('upgrade')">апгрейд аккаунта</a></li>
			<li class="list-group-item"><strong>Настройте <a href="#" onclick="fc_navigate('notifications')">уведомления</a></strong> </li>
			<li class="list-group-item" style="color: #ccc">Добавьте обещанную сумму</li>
			<li class="list-group-item" style="color: #ccc">Установите комиссию </li>
			<li class="list-group-item" style="color: #ccc">Выполняйте задания по проверке других майнеров</li>
			<li class="list-group-item" style="color: #ccc">Голосуйте за параметры валют</li>
			<li class="list-group-item" style="color: #ccc">Не пропускайте входящие запросы</li>
			<li class="list-group-item" style="color: #ccc">Переводите монеты с обещанных сумм на свой счет</li>

		</ul>
	</div>
</div>

<div class="row">
	<div class="col-lg-4">
		<div class="panel panel-success">
			<div class="panel-heading">
				<?php echo $lng['inbox']?>
			</div>
			<div class="panel-body">
				<p>Входящие запросы на указаные Вами <a href="#" onclick="fc_navigate('promised_amount_list')">обещанные суммы</a>. Вы можете настроить информирование о входящих запросах в разделе <a href="#" onclick="fc_navigate('notifications')">смс и email уведомления</a> </p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('cash_requests_in')">Перейти</a>
			</div>
		</div>
	</div>
	<!-- /.col-lg-4 -->
	<div class="col-lg-4">
		<div class="panel panel-success">
			<div class="panel-heading">
				<?php echo $lng['tasks']?>
			</div>
			<div class="panel-body">
				<p>Чтобы получать майнерский % по обещанным сумма, Вам нужно набрать определнное кол-во баллов при помощи выполнения заданий по проверке других майнеров. .</p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('tasks')">Перейти</a>
			</div>
		</div>
	</div>
	<!-- /.col-lg-4 -->
	<div class="col-lg-4">
		<div class="panel panel-success">
			<div class="panel-heading">
				<?php echo $lng['voting']?>
			</div>
			<div class="panel-body">
				<p>Чтобы получать майнерский % по мимо проверки других майнеров нужно голосоать за % роста по каждой валюте, которая есть у Вас в обещанных суммах; а также - за размер реферальных бонусов.</p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('voting')">Перейти</a>
			</div>
		</div>
	</div>
	<!-- /.col-lg-4 -->
</div>
<!-- /.row -->
<div class="row">
	<div class="col-lg-4">
		<div class="panel panel-info">
			<div class="panel-heading">
				<?php echo $lng['reg_users']?>
			</div>
			<div class="panel-body">
				<p>Каждый пользователь, который зарегистрируется по выданному Вами ключу становится Вашим рефералом. И Вы будут получать с монет, намайненных из обещанных сумм, реферальные бонусы</p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('new_user')">Перейти</a>
			</div>
		</div>
	</div>
	<!-- /.col-lg-4 -->
	<div class="col-lg-4">
		<div class="panel panel-info">
			<div class="panel-heading">
				<?php echo $lng['promised_amounts'] ?>
			</div>
			<div class="panel-body">
				<p>Это суммы, которые Вы готовы отдать в обмен на такое же кол-во Dcoin. А также, DWOC, которые выдаются каждому майнеру просто так после добавления первой обещанной суммы.</p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('promised_amount_list')">Перейти</a>
			</div>
		</div>
	</div>
	<!-- /.col-lg-4 -->
	<div class="col-lg-4">
		<div class="panel panel-info">
			<div class="panel-heading">
				<?php echo $lng['outgoing']?>
			</div>
			<div class="panel-body">
				<p>Вы можете обменять свои Dcoin на такое же кол-во наличных денег у любого майнера, у которого есть нужная Вам общенная сумма.</p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('cash_requests_out')">Перейти</a>
			</div>
		</div>
	</div>
	<!-- /.col-lg-4 -->
</div>
<!-- /.row -->
<div class="row">
	<div class="col-lg-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<?php echo $lng['points']?>
			</div>
			<div class="panel-body">
				<p>Чтобы получать майнерсий % нужно набрать определенное кол-во баллов. Майнерский % можно получить только со 2-го месяца с даты, когда Вы стали майнером.</p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('points')">Перейти</a>
			</div>
		</div>
	</div>
	<div class="col-lg-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<?php echo $lng['commission']?>
			</div>
			<div class="panel-body">
				<p>Вы будете получать % с перевода средств или forex-ордера, если транзакция окажется в блоке, подписанном Вашим ключем.</p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('change_commission')">Перейти</a>
			</div>
		</div>
	</div>
	<div class="col-lg-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<?php echo $lng['holidays']?>
			</div>
			<div class="panel-body">
				<p>Если в какие-то дни Вы не хотите получать запросы на Ваши обещанные суммы, вы можете добавить каникулы. В это время Dcoin по общанным сумма расти не будут. </p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="fc_navigate('holidays_list')">Перейти</a>
			</div>
		</div>
	</div>
	<div class="col-lg-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<?php echo $lng['geolocation'] ?>
			</div>
			<div class="panel-body">
				<p>Место, в котором Вы готовы обменить указанные Вами общанные суммы на такое же кол-во Dcoin. При смене местополжения, нужно будет заново пройти процедуду добавления обещанных сумм</p>
			</div>
			<div class="panel-footer">
				<a href="#" onclick="map_navigate ('geolocation')">Перейти</a>
			</div>
		</div>
	</div>
</div>
<!-- /.row -->