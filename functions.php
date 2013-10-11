<?php
define("FIRST_DATE", 788940000); // 01.01.1995 - начало исторических данных
define("LAST_DATE", 1388469600); // 30.12.2013 - конец истории лата

function DateToUnix($value=0,$format="") { // преобразует дату двух форматов в UnixTimestamp
	switch ($format) {
	case "DD.MM.YYYY":
		$date_elements = explode(".",$value);
		$result = mktime(0, 0, 0, $date_elements[1], $date_elements[0], $date_elements[2]); 
		break;
	case "YYYYMMDD":
		$result = strtotime($value); 
		break;
	default: 
		$result = time();
	}
	return $result;
}

function NormalizeDate($value) { // корректирует дату формата DD.MM.YYYY, помещая ее в разумные пределы и возвращает UnixTimestamp
	$pattern = '/^0*([1-9]|[12][0-9]|3[01])\.0*([1-9]|1[0-2])\.[0-9]{1,4}$/'; // шаблон ввода даты
	if (strlen($value) > 10 || preg_match($pattern, $value) != 1) return time(); // если введены некорректные данные, возвращаем сегодняшюю дату
	$date_elements = explode(".",$value); // если формат строки корректный, преобразуем её в дату
	$result = mktime(0, 0, 0, $date_elements[1], $date_elements[0], $date_elements[2]); 
	if ($result < FIRST_DATE) $result = FIRST_DATE; // слишком ранняя дата
	if ($result > LAST_DATE || $result > time()) $result = min(time(),LAST_DATE); // слишком большая дата
	return $result;
}

function NormalizeAmount($value) { // корректирует неверно введенную пользователем сумму
	$pattern = '/^\d+(\.\d{0,})?$/'; // шаблон ввода суммы
	if (preg_match($pattern, $value) != 1) return 1;
	else return $value;
}
?>
