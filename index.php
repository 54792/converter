<!DOCTYPE html>
<html lang="ru">
<head>
	<title>Конвертер валют</title>
	<!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet" media="screen">
	<link href="css/datepicker.css" rel="stylesheet">
	<style type="text/css">body{padding-top:20px;}.sidebar-nav{padding:9px 0;}</style>
	<meta charset="utf-8">
</head>
<?php
if (isset($_GET['currency'])) $processing = true; else $processing = false; // это первичная загрузка или обработка данных?
require_once('functions.php'); // подключаем файл с функциями
if (!$processing) { // первичная загрузка - проверяем необходимость обновления данных
	if (file_exists('quotes.xml')) {
		$local_copy_exists = true;
		$xml = simplexml_load_file('quotes.xml'); // загрузка данных из локальной копии
	}
	else $local_copy_exists = false;
	$actualDate = $xml->Date; // чтение даты, на которую актуальны сохраненные курсы 
	$today = date("Ymd"); // сегодняшняя дата
	if ((date("w") < 6 && $today > $actualDate) || !$local_copy_exists) { // если сегодня рабочий день, а данные устарели или вообще не получены
		$quotes = file_get_contents('http://www.bank.lv/vk/xml.xml'); // получаем обновленные данные
		file_put_contents('quotes.xml',$quotes); // и сохраняем их для последующего использования
		$xml = simplexml_load_string($quotes);
	}
}
else { // обработка формы - берем данные из локальной копии и проверяем присланные формой данные на корректность
	$xml = simplexml_load_file('quotes.xml'); 
	$formDate = NormalizeDate($_GET['date']);
	$formAmount = NormalizeAmount($_GET['amount']);
	$formCurrency = $_GET['currency'];
}
$actualDate = $xml->Date;
$todayDate = time();
?>
<body>
	<!-- Sidebar -->
	<div class="container">
		<div class="navbar">
			<div class="navbar-inner">
				<a class="brand" href="#">Ivan Skvortsov (4101BD)</a>
			</div>
		</div>
	</div>
	<!-- Content -->
	<div class="container">
		<div class="row">
			<div class="span3">
				<div class="well">
					<ul class="nav nav-list">
						<li class="nav-header">Особенности</li>
						<li class="divider"></li>
						<li>
							<small>
								<p>Список котируемых валют и их курсы загружаются с сервера Банка Латвии посредством XML интерфейса.</p>
								<p>Контроль ошибок осуществляется как на стороне клиента (средствами HTML5), так и на стороне сервера.</p>
								<p>Кэширование данных производится по рабочим дням при первом запросе страницы.</p>
								<p></p>
							</small>
						</li>
					</ul>
					
				</div>
			</div>
			<div class="span9">
				<form class="form-horizontal">
				<!-- <fieldset> -->
					<legend style="font-size:2em">Конвертер валюты</legend>
					<p>Сервис позволяет конвертировать произвольную сумму иностранной валюты в латвийские латы. Конвертация осуществляется по официальному курсу, установленному Банком Латвии на заданную дату (начиная с 1 января 1995 года).</p>
					<div class="control-group" style="margin-top:35px">
						<label class="control-label" for="inputDate">Дата конвертации</label>
						<div class="controls">
							<? if ($processing) $convDate = date('d.m.Y',$formDate); else $convDate = date('d.m.Y',$todayDate); ?>
							<div class="input-append date" id="dp" data-date="<? echo $convDate ?>" data-date-format="dd.mm.yyyy">
								<input class="span3" type="text" id="inputDate" name="date" value="<? echo $convDate ?>" pattern="^0*([1-9]|[12][0-9]|3[01])\.0*([1-9]|1[0-2])\.(199[5-9]|200[0-9]|201[0-3])$" title="Формат ввода: 01.01.2013, диапазон дат 01.01.1995 - <? echo date('d.m.Y',$todayDate) ?>" maxlength="10" required>
								<span class="add-on"><i class="icon-calendar"></i></span>
							</div>
						</div>
					</div>	
					<div class="control-group">
						<label class="control-label" for="inputAmount">Сумма</label>
						<div class="controls controls-row">
							<? if ($processing) $amountValue = $formAmount; else $amountValue = ''; ?>
							<input class="span2" type="text" id="inputAmount" name="amount" value="<? echo $amountValue ?>" title="Пример ввода: 12.34" pattern="^\d+(\.\d{0,2})?$" required>
							<select class="span2 input-small" name="currency">
								<?php foreach ($xml->Currencies->Currency as $curr) {
										echo '<option ';
										if ($processing) $selected = $formCurrency; else $selected = 'EUR';
										if ($curr->ID == $selected) echo 'selected ';
										echo 'value="', $curr->ID, '">',$curr->ID,'</option>', PHP_EOL;
									}
								?>
							</select>
						</div>
					</div>
					<div class="control-group">
						<div class="controls">
							<p class="lead" id="pLead">
								<?php if (!$processing) echo '&nbsp;'; else { // обработка формы - выводим результат расчета
								if ($formDate != $actualDate) { // дата конвертации отличается от текущей
									$url = 'http://www.bank.lv/vk/xml.xml?date='.date('Ymd',$formDate);
									$xml = simplexml_load_file($url); 
									//echo $url;
								}
								if ($xml == false) echo 'Сервис временно недоступен. Обратитесь позже.';
								else {
								$result = '';
								foreach ($xml->Currencies->Currency as $curr) {
									if ((string)$curr->ID == $formCurrency) {
										$units = (int) $curr->Units;
										$rate = (float) $curr->Rate;
										$result = number_format($formAmount,2,'.','').' '.$curr->ID.' = '.number_format($formAmount/$units*$rate,2,'.','').' LVL';
										break;
									}
								}
								if ($result != '') echo $result;
								else echo 'Курс ', $formCurrency, ' на ', date('d.m.Y',$formDate), ' не установлен'; 
								}
								}?>
							</p>
						</div>
					</div>
					<div class="form-actions">
						<button type="submit" class="btn btn-primary">Конвертация</button> 
						<button type="reset" class="btn" id="btnCancel">Сброс</button><!-- type="reset" -->
					</div>
				<!-- </fieldset> -->
				</form>
			</div>	
		</div>
	</div>
	<!-- Scripts -->
    <script src="http://code.jquery.com/jquery-latest.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/bootstrap-datepicker.js"></script>
	<script>
	$(function(){		
		$('#dp').datepicker()
			.on('changeDate', function(ev){
				$('#dp').datepicker('hide');
			});
	});	
	document.getElementById('btnCancel').onclick = function() {
		document.getElementById('pLead').innerHTML = '&nbsp;';
		document.getElementById('inputAmount').value = '';
		return(false);
	}
	</script>	
</body>
</html>
