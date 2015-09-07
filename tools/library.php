<?php

function sig_handler($signo) {
  
  switch ($signo) {
    case SIGTERM:
      // handle shutdown tasks
      echo "\n\n";
      exit (1);
      break;
    case SIGINT:
      // handle shutdown tasks
      echo "\n\n";
      exit (1);
      break;
    case SIGHUP:
      // handle restart tasks
      break;
    case SIGUSR1:
      echo "Caught SIGUSR1...\n";
      break;
    default:
      // handle all other signals
  }
  
}

function get_link ($db_host, $db_user, $db_pass, $db_name) {
  # Функция для подключения к базе даннных
  $link= mysqli_connect ($db_host, $db_user, $db_pass, $db_name);
  if ( !($link) )
    die ('Can not connect to Database Server');
  return $link;
}

function get_from_wikidata ($id, &$last_time=0) {
  # Функция получает данные непосредственно с Викиданных по API.
  # Параметр $last_time позволяет делать запросы не чаще одного раза в 5 секунд
  $content = file_get_contents('https://www.wikidata.org/w/api.php?action=wbgetentities&ids='.$id.'&format=json');
  if ( (microtime(true) - $last_time) < 6 )
    sleep(6);
  $last_time=microtime(true);
  return json_decode($content, true);
}

# Функция merge_code больше не используется
function merge_code ($base_table, $ter, $kod1='000', $kod2='000', $kod3='000') {
  # Функция объединяет части кода в единое целое с учётом особенностей классификатора
  $code=$ter;
  if ( ($kod1 <> '000') or ($base_table == 'oktmo') ) $code.=$kod1;
  if ( ($kod2 <> '000') or ($kod3 <> '000') or ($base_table == 'oktmo') ) $code.=$kod2; # Есть ОКТМО с нулями в середине
  if ($kod3 <> '000') $code.=$kod3;

  return $code;
}

function analog ($link, $base_table, $code) {
  # Функция возвращает для указанного кода аналог из другого классификатора
  
  # ОКТМО субъекта отличается от ОКАТО шестью нулями справа
  if ( (strlen($code) == 8) and (substr($code,2,6) == '000000') )
    return substr($code,0,2);

  if (strlen($code) == 2)
    return $code.'000000';
    
  $code_s=$code;

  # В таблице соответствия все ОКАТО дополнены нулями до 11 знаков
  if ($base_table == 'okato') {
    $zeros= (11 - strlen($code))/3;
    for ($i=1; $i <= $zeros; $i++) $code_s.='000';
  }
  
  # В прежней версии таблицы соответствия кодов лидирующий нуль в кодах отсутствовал
/*
    $flag=false;
    if (substr ($code_s,0,1) == '0') {
      $code_s=substr ($code_s,1,strlen ($code_s) - 1);
      $flag=true;
    }
*/

  if ($base_table == 'okato')
    $code_o='oktmo';
  else
    $code_o='okato';

  $query='SELECT id, '.$code_o.' FROM okatooktmo WHERE ('.$base_table.'=\''.$code_s.'\')';
  $result_o=mysqli_query ($link, $query);
  $row_o=mysqli_fetch_array ($result_o, MYSQLI_ASSOC);
  $analog=$row_o [$code_o];

  mysqli_free_result($result_o);
/*
    # Восстанавливаем лидирующий нуль
    if ($flag and ($analog != ''))
      $analog='0'.$analog;
*/

  # В классификаторе ОКАТО длина кода варьируется в зависимости от уровня административного деления.
  if ($code_o == 'okato')
    if (substr ($analog,8,3) != '000')
      return $analog;
      elseif (substr($analog,6,3) != '000')
	return substr($analog,0,8);
      elseif (substr($analog,2,3) != '000')
	return substr($analog,0,5);
      else
	return substr($analog,0,2);

    return $analog;
}

function get_label ($link, $item) {
  # Функция возвращает метку для элемента с указанным идентификатором
  
  # Иногда попадаются пустые идентификаторы
  if ($item == '')
    return $item;

    $query='SELECT label FROM wikidata WHERE item=\''.$item.'\'';
    $result=mysqli_query ($link, $query);
    $r=mysqli_fetch_array ($result, MYSQLI_ASSOC);
    mysqli_free_result($result);

    return $r ['label'];
}

function found_rows ($link) {
  # Функция возвращает количество найденных записей, когда требуется узнать только их количество
  
  $result=mysqli_query ($link, 'SELECT FOUND_ROWS()');
  $row=mysqli_fetch_array ($result, MYSQLI_ASSOC);
  return $row['FOUND_ROWS()'];
}

function percents ($total, $part) {
 return sprintf ("%3d%%", ($part*100)/$total);
}

function hms ($time) {
  $sec=$time%60;
  $min=((int)($time/60))%60;
  $hour=(int)($time/3600);
  
  return "$hour ч. $min мин. $sec сек.";
}

function print_table_old ($link, $data_date, $base_table, $level, $ter='00', $kod1='000', $kod2='000', $mode='direct') {
  if ($base_table == 'okato') {
    $base_name='ОКАТО';
    $alter_table='oktmo';
    $alter_name='ОКТМО';
  }
    elseif ($base_table == 'oktmo') {
      $base_name='ОКТМО';
      $alter_table='okato';
      $alter_name='ОКАТО';
    }

    # Формируем запрос для списка объектов
    # Формируем условие для выборки

  if ($ter == '00')
    $level=1;
    elseif ($kod1 == '000')
      $level=2;
      elseif ($kod2 == '000')
	$level=3;
      else
	$level=4;

  switch ($level) {
    case 1 : {
      $clause='ter<>\'00\' AND kod1=\'000\' AND kod2=\'000\' AND kod3=\'000\'';
      break;
    }
    case 2 : {
      $clause='ter=\''.$ter.'\' AND kod1<>\'000\' AND kod2=\'000\' AND kod3=\'000\'';
      break;
    }
    case 3 : {
      $clause='ter=\''.$ter.'\' AND kod1=\''.$kod1.'\'  AND ( ( kod2<>\'000\' AND kod3=\'000\') OR ( kod2=\'000\' AND kod3<>\'000\') )';
      break;
    }
    case 4 : {
      $clause='ter=\''.$ter.'\' AND kod1=\''.$kod1.'\'  AND kod2=\''.$kod2.'\' AND kod3<>\'000\'';
      break;
    }
  }
    
  if ($mode == 'html')
    $navigation="<a href=\"../$alter_table/0.html\">$alter_name</a>\n";
  else
    $navigation="<a href=\"?base=$alter_table&amp;l=1\">$alter_name</a>\n";

  if ($level > 1) {
    if ($mode == 'html')
      $navigation.="<br>\n<br>\n<a href=\"0.html\">$base_name</a> /";
    else
      $navigation.="<br>\n<br>\n<a href=\"?base=$base_table&amp;l=1\">$base_name</a> /";
  }
    elseif ($level == 1)
      $navigation.="<br>\n<br>\n".$base_name.' /';

  if ($level > 2) {
    $query='SELECT name, mergedcode FROM '.$base_table.' WHERE (ter=\''.$ter.'\' AND kod1=\'000\' AND kod2=\'000\' AND kod3=\'000\')';
    $result=mysqli_query ($link, $query);
    $row=mysqli_fetch_array ($result, MYSQLI_ASSOC);
    mysqli_free_result($result);

    if ($mode == 'html')
      $navigation.=' <a href="'.$row ['mergedcode'].'.html">'.$row ['name'].'</a> /';
    else
      $navigation.=" <a href=\"?base=$base_table&amp;l=2&amp;ter=$ter\">".$row ['name'].'</a> /';
  }
    elseif ($level == 2) {
      $query='SELECT name, mergedcode FROM '.$base_table.' WHERE (ter=\''.$ter.'\' AND kod1=\'000\' AND kod2=\'000\' AND kod3=\'000\')';
      $result=mysqli_query ($link, $query);
      $row=mysqli_fetch_array ($result, MYSQLI_ASSOC);
      mysqli_free_result($result);

      $navigation.=' '.$row ['name'].' /';
  }

  if ($level > 3) {
    $query='SELECT name, mergedcode FROM '.$base_table.' WHERE (ter=\''.$ter.'\' AND kod1=\''.$kod1.'\' AND kod2=\'000\' AND kod3=\'000\')';
    $result=mysqli_query ($link, $query);
    $row=mysqli_fetch_array ($result, MYSQLI_ASSOC);
    mysqli_free_result($result);

    if ($mode == 'html')
      $navigation.=' <a href="'.$row ['mergedcode'].'.html">'.$row ['name'].'</a> /';
    else
      $navigation.= " <a href=\"?base=$base_table&amp;l=3&amp;ter=$ter&amp;kod1=$kod1\">".$row ['name'].'</a> /';
  }
    elseif ($level == 3) {
      $query='SELECT name, mergedcode FROM '.$base_table.' WHERE (ter=\''.$ter.'\' AND kod1=\''.$kod1.'\' AND kod2=\'000\' AND kod3=\'000\')';
      $result=mysqli_query ($link, $query);
      $row=mysqli_fetch_array ($result, MYSQLI_ASSOC);
      mysqli_free_result($result);

      $navigation.=' '.$row ['name'].' /';
    }

  if ($level == 4) {
    $query='SELECT name, mergedcode FROM '.$base_table.' WHERE (ter=\''.$ter.'\' AND kod1=\''.$kod1.'\' AND kod2=\''.$kod2.'\' AND kod3=\'000\')';
    $result=mysqli_query ($link, $query);
    $row=mysqli_fetch_array ($result, MYSQLI_ASSOC);
    mysqli_free_result($result);

    $navigation.= ' '.$row ['name'].' /';
  }

  $navigation.='<br><br>';

  $html_head="<!DOCTYPE html>\n<html>\n<head><meta charset=\"utf-8\">\n<title>Валидатор Викиданных по $base_name";

  if ($level > 1)
    $html_head.=' '.$row ['mergedcode'].' '.$row ['name'];

  $html_head.="</title>\n<style>\n";

  $html_head.=file_get_contents('style.css');
  $html_head.="\n</style>\n</head>\n<body>\n";

  if ($mode == 'html')
    $disclaimer='<p><a href="../index.html">На главную</a></p>';
  else
    $disclaimer='<p><a href="?l=0">На главную</a></p>';

  $disclaimer.="<p>Данные Викиданных от $data_date. Обновляются раз в неделю.</p>";

  $disclaimer2="<div class=\"disclaimer\"><p>Не спешите исправлять ошибки! Проверьте, может так и должно быть.</p></div>\n";

  $legend='<table class="legend"><tr><td class="dup">Элементы с одинаковым кодом</td><td class="bad">Неожиданный код</td><td class="alter">Элемента с таким кодом нет,<br>есть с аналогичным кодом</td></tr></table>';

  if ($mode == 'html') {
    if ($level > 1)
      $handle=fopen ("html/$base_table/".$row ['mergedcode'].'.html', "w");
    else
      $handle=fopen ("html/$base_table/0.html", "w");

    fwrite ($handle, $html_head.$disclaimer.$disclaimer2.$legend);
  }
  else
    echo $html_head.$disclaimer.$disclaimer2.$legend;

  if ($mode == 'html')
    fwrite ($handle, $navigation);
  else
    echo $navigation;

    # Формируем заголовок таблицы
  $table='<table class="data"><thead><tr>';
  $table.='<th>Найдено вложенных элементов</th>';

  if ($base_table == 'okato')
    $table.='<th>ОКАТО</th><th>Аналог. ОКТМО</th>';
    elseif ($base_table == 'oktmo')
      $table.='<th>ОКТМО</th><th>Аналог. ОКАТО</th>';

  $table.='<th>Элемент классификатора</th>';
  $table.='<th>Элемент Викиданных</th>';
  $table.='<th><span class="label">Метка</span>, псевдонимы</th>';
  $table.='<th>Тип территории</th>';
  $table.='<th>Описание</th>';

  if ($base_table == 'okato')
    $table.='<th>ОКАТО в Викиданных</th><th>ОКТМО в Викиданных</th>';
      elseif ($base_table == 'oktmo')
      $table.='<th>ОКТМО в Викиданных</th><th>ОКАТО в Викиданных</th>';

  $table.='<th><abbr title="Административно-территориальная единица">АТЕ</abbr></th>';
  $table.='<th>Центр</th>';
  # На некоторых уровнях определённые столбцы не выводятся
  if ($level != 1) {
    $table.='<th>Широта</th>';
    $table.='<th>Долгота</th>';
    $table.='<th>Телефонный код</th>';
    $table.='<th>Индекс</th>';
  }
    $table.='</tr></thead>'."\n";

  if ($mode == 'html')
    fwrite ($handle, $table);
  else
    echo $table; # Выводим шапку таблицы

  $query="SELECT ter, kod1, kod2, kod3, razdel, type, name, nomdeskr, exist, numfound, mergedcode FROM $base_table WHERE ($clause)";
  $result=mysqli_query ($link, $query);

  while ($row=mysqli_fetch_array ($result, MYSQLI_ASSOC)) {
    $base_code=$row ['mergedcode'];
    $alter_code=analog($link, $base_table, $base_code);

    $clause=$base_table.'=\''.$base_code.'\'';    

    # Формируем список полей
    $fields='item';
    $fields.=', label';
    $fields.=', description';
    $fields.=', oktmo';
    $fields.=', okato';
    $fields.=', ate';
    $fields.=', centrum';
    # На некоторых уровнях определённые поля не запрашиваются
    if ($level != 1) {
      $fields.=', lat';
      $fields.=', lon';
      $fields.=', phone';
      $fields.=', post';
    }

    $base_dup=false;
    $alter_dup=false;

    $base_bad=false;
    $alter_bad=false;

    $alter=false;

    $query="SELECT $fields FROM wikidata WHERE ($clause)";
    $result_w=mysqli_query ($link, $query);

    $num_items=mysqli_num_rows($result_w);
    if (1 < $num_items)
      $base_dup=true;

    if ( (0 == $num_items) and ($alter_code !='') ) {
      $clause=$alter_table.'=\''.$alter_code.'\'';
      $query="SELECT $fields FROM wikidata WHERE ($clause)";
      $result_w=mysqli_query ($link, $query);

      $num_items=mysqli_num_rows($result_w);

      if (0 != $num_items)
	$alter=true;

      if (1 < $num_items)
	$alter_dup=true;
    }

    $row_w=mysqli_fetch_array ($result_w, MYSQLI_ASSOC);

    if ($level==1)
      $colspan=8;
    else
      $colspan=12;

    $rowspan='';

    if (1 < $num_items)
      $rowspan=" rowspan=\"$num_items\"";

    $elements_exist=0;
    $elements_found=0;

    do {
      # Если количество вложенных объектов классификатора не равно нулю, ставим соотвествующую ссылку на следующий уровень списка

      if ($row['exist'] != 0) {
	$elements_exist=$row['exist'];
	$next_level=$level+1;
	$elements_found=$row['numfound'];
      }
      else
	$next_level=10;

      $query='SELECT alias FROM aliases WHERE (item=\''.$row_w['item'].'\')';
      $result_a=mysqli_query ($link, $query);

      $query='SELECT type FROM types WHERE (item=\''.$row_w['item'].'\')';
      $result_t=mysqli_query ($link, $query);

      $title=' title="'.$row['nomdeskr'].'"';

      if ($elements_exist != 0) {
	$status_found=$elements_found.' из '.$elements_exist.' ';

	if ($elements_found == 0)
	  $percents=0;
	  elseif ( ((100*$elements_found)%$elements_exist) != 0 ) {
	      $status_found.='~';
	      $percents=round((100*$elements_found)/$elements_exist);
	  }
	  else
	    $percents=(100*$elements_found)/$elements_exist;
	$status_found.=$percents.'%';
      }
      else
	$status_found='';

      if (($row_w["$base_table"] != $base_code) and (isset ($row_w ["$base_table"])) )
	$base_bad=true;

      if (($row_w ["$alter_table"] != $alter_code) and (isset ($row_w ["$alter_table"])) )
	$alter_bad=true;

      # Формируем вывод строк таблицы
      if ($base_dup or $alter_dup)
	$output_row='<tr class="dup">';
	  elseif ($alter)
	    $output_row='<tr class="alter">';
	  else
	    $output_row='<tr>';

      if ( !(($base_dup or $alter_dup) and ($num_items == 1)) ) {
	$output_row.='<th'.$rowspan.'>'.$status_found.'</th>';
	$output_row.='<th'.$rowspan;

	if ($base_bad)
	  $output_row.=' class="bad"';

	$output_row.='>'.$base_code.'</th>';
	$output_row.='<th'.$rowspan;

	if ($alter_bad)
	  $output_row.=' class="bad"';

	$output_row.='>'.$alter_code.'</th>';
	$output_row.='<th';

	if (isset ($row['nomdeskr']))
	  $output_row.=$title;

	$output_row.=$rowspan;

	switch ($next_level) {
	  case 2 :
	    if ($mode == 'html')
	      $output_row.='><a href="'.$row ['mergedcode'].'.html">'.$row['type'].' '.$row['name'].'</a></th>';
	    else
	      $output_row.='><a href="?l=2&amp;base='.$base_table.'&amp;ter='.$row['ter'].'">'.$row['type'].' '.$row['name'].'</a></th>';
	    break;
	  case 3 :
	    if ($mode == 'html')
	      $output_row.='><a href="'.$row ['mergedcode'].'.html">'.$row['type'].' '.$row['name'].'</a></th>';
	    else
	      $output_row.='><a href="?l=3&amp;base='.$base_table.'&amp;ter='.$row['ter'].'&amp;kod1='.$row['kod1'].'">'.$row['type'].' '.$row['name'].'</a></th>';
	    break;
	  case 4 :
	    if ($mode == 'html')
	      $output_row.='><a href="'.$row ['mergedcode'].'.html">'.$row['type'].' '.$row['name'].'</a></th>';
	    else
	      $output_row.='><a href="?l=4&amp;base='.$base_table.'&amp;ter='.$row['ter'].'&amp;kod1='.$row['kod1'].'&amp;kod2='.$row['kod2'].'">'.$row['type'].' '.$row['name'].'</a></th>';
	    break;
	  case 10 :
	    $output_row.='>'.$row['type'].' '.$row['name'].'</th>';
	    break;
	}
      }

      if (isset ($row_w['item'])) {
	$output_row.='<td class="numeric"><a href="https://www.wikidata.org/wiki/'.$row_w['item'].'" target="blank">'.$row_w['item'].'</a></td>';
	$output_row.='<td>';
	$output_row.='<p class="label">'.$row_w['label'].'</p>';

	while ($row_a=mysqli_fetch_array ($result_a, MYSQLI_ASSOC))
	  $output_row.='<p>'.$row_a ['alias'].'</p>';
	mysqli_free_result($result_a);

	$output_row.='</td>';
	$output_row.='<td>';

	while ($row_t=mysqli_fetch_array ($result_t, MYSQLI_ASSOC))
	  $output_row.='<p><a href="https://www.wikidata.org/wiki/'.$row_t['type'].'" target="blank">'.get_label ($link, $row_t, "type", $last_time).'</a></p>';
	mysqli_free_result($result_t);

	$output_row.='</td>';
	$output_row.='<td>'.$row_w['description'].'</td>';

	$output_row.='<td class="numeric';

	if ($base_bad)
	  $output_row.=' bad';

	$output_row.='">'.$row_w ["$base_table"].'</td>'; 
	$output_row.='<td class="numeric';

	if ($alter_bad)
	  $output_row.=' bad';

	$output_row.='">'.$row_w ["$alter_table"].'</td>';

	if (isset ($row_w['ate']))
	  $output_row.='<td><a href="https://www.wikidata.org/wiki/'.$row_w['ate'].'" target="blank">'.get_label ($link, $row_w, "ate", $last_time).'</a></td>';
	else
	  $output_row.='<td></td>';

	if (isset ($row_w['centrum']))
	  $output_row.='<td><a href="https://www.wikidata.org/wiki/'.$row_w['centrum'].'" target="blank">'.get_label ($link, $row_w, "centrum", $last_time).'</a></td>';
	else
	  $output_row.='<td></td>';

	if ($level != 1) {
	  $output_row.='<td class="numeric">'.$row_w['lat'].'</td>';
	  $output_row.='<td class="numeric">'.$row_w['lon'].'</td>';
	  $output_row.='<td class="numeric">'.$row_w['phone'].'</td>';
	  $output_row.='<td class="numeric">'.$row_w['post'].'</td>';
	}

	$output_row.='</tr>'."\n";
      }
	elseif ($level == 1)
	  $output_row.='<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>'."\n";
	else
	  $output_row.='<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>'."\n";

      if ($mode == 'html')
	fwrite ($handle, $output_row);
      else
	echo $output_row; # Выводим строку таблицы

      $rowspan='';
      $num_items=1;
    } while ($row_w=mysqli_fetch_array ($result_w, MYSQLI_ASSOC));
    mysqli_free_result($result_w);
  }

  if ($mode == 'html')
    fwrite ($handle, "</table></body></html>\n");
  else
    echo '</table></body></html>'."\n";

  mysqli_free_result($result);
}

function print_table ($link, $data_date, $base_table, $code='', $mode='direct') {
  # Функция выводит таблицу с результатами поиска элементов с кодами указанного муниципального или административного образования
  # Если параметр mode='html', генерируются html файлы.
  
  if ($base_table == 'okato') {
  $base_name='ОКАТО';
  $alter_table='oktmo';
  $alter_name='ОКТМО';
  $base_root_page='00.html';
  $alter_root_page='00000000.html';
  }
    elseif ($base_table == 'oktmo') {
      $base_name='ОКТМО';
      $alter_table='okato';
      $alter_name='ОКАТО';
      $base_root_page='00000000.html';
      $alter_root_page='00.html';
    }

    # Поскольку в базе данных есть коды, разбитые на части, воспользуемся этим для выяснения уровня образования.
  if ($code != '') {
    $query='SELECT ter, kod1, kod2, kod3, name FROM '.$base_table.' WHERE mergedcode=\''.$code.'\'';
    $result=mysqli_query ($link, $query);
    $row=mysqli_fetch_array ($result, MYSQLI_ASSOC);
    mysqli_free_result($result);

    if ($row['ter'] == '00')
      $level=1;
      elseif ($row['kod1'] == '000')
	$level=2;
	  elseif ($row['kod2'] == '000')
	    $level=3;
	  else
	    $level=4;
  }
    else
      $level=1;


    # Страшноватый но рабочий блок формирования навигации по уровням образований
    
    # Ссылка для перехода на альтернативный классификатор
  if ($mode == 'html')
    $navigation="<a href=\"../$alter_table/$alter_root_page\">$alter_name</a>\n";
  else
    $navigation="<a href=\"?base=$alter_table\">$alter_name</a>\n";

  if ($level > 1) {
    if ($mode == 'html')
      $navigation.="<br>\n<br>\n<a href=\"$base_root_page\">$base_name</a> /";
    else
      $navigation.="<br>\n<br>\n<a href=\"?base=$base_table\">$base_name</a> /";
  }
    elseif ($level == 1)
      $navigation.="<br>\n<br>\n".$base_name.' /';

  if ($level > 2) {
    $query='SELECT name, mergedcode FROM '.$base_table.' WHERE (ter=\''.$row['ter'].'\' AND kod1=\'000\' AND kod2=\'000\' AND kod3=\'000\')';
    $result_n=mysqli_query ($link, $query);
    $row_n=mysqli_fetch_array ($result_n, MYSQLI_ASSOC);
    mysqli_free_result($result_n);

    if ($mode == 'html')
      $navigation.=' <a href="'.$row_n ['mergedcode'].'.html">'.$row_n ['name'].'</a> /';
    else
      $navigation.=" <a href=\"?base=$base_table&amp;code=".$row_n ['mergedcode']."\">".$row_n ['name'].'</a> /';
  }
    elseif ($level == 2) {
      $query='SELECT name, mergedcode FROM '.$base_table.' WHERE (ter=\''.$row['ter'].'\' AND kod1=\'000\' AND kod2=\'000\' AND kod3=\'000\')';
      $result_n=mysqli_query ($link, $query);
      $row_n=mysqli_fetch_array ($result_n, MYSQLI_ASSOC);
      mysqli_free_result($result_n);

      $navigation.=' '.$row_n ['name'].' /';
    }

  if ($level > 3) {
    $query='SELECT name, mergedcode FROM '.$base_table.' WHERE (ter=\''.$row['ter'].'\' AND kod1=\''.$row['kod1'].'\' AND kod2=\'000\' AND kod3=\'000\')';
    $result_n=mysqli_query ($link, $query);
    $row_n=mysqli_fetch_array ($result_n, MYSQLI_ASSOC);
    mysqli_free_result($result_n);

    if ($mode == 'html')
      $navigation.=' <a href="'.$row_n ['mergedcode'].'.html">'.$row_n ['name'].'</a> /';
    else
      $navigation.= " <a href=\"?base=$base_table&amp;code=".$row_n ['mergedcode']."\">".$row_n ['name'].'</a> /';
  }
    elseif ($level == 3) {
      $query='SELECT name, mergedcode FROM '.$base_table.' WHERE (ter=\''.$row['ter'].'\' AND kod1=\''.$row['kod1'].'\' AND kod2=\'000\' AND kod3=\'000\')';
      $result_n=mysqli_query ($link, $query);
      $row_n=mysqli_fetch_array ($result_n, MYSQLI_ASSOC);
      mysqli_free_result($result_n);

      $navigation.=' '.$row_n ['name'].' /';
    }

  if ($level == 4) {
    $query='SELECT name, mergedcode FROM '.$base_table.' WHERE (ter=\''.$row['ter'].'\' AND kod1=\''.$row['kod1'].'\' AND kod2=\''.$row['kod2'].'\' AND kod3=\'000\')';
    $result_n=mysqli_query ($link, $query);
    $row_n=mysqli_fetch_array ($result_n, MYSQLI_ASSOC);
    mysqli_free_result($result_n);

    $navigation.= ' '.$row_n ['name'].' /';
  }


  $navigation.='<br><br>';

  $html_head="<!DOCTYPE html>\n<html>\n<head>\n<meta charset=\"utf-8\">\n<title>Валидатор Викиданных по $base_name";

  if ($level > 1)
    $html_head.=' '.$code.' '.$row ['name'];

  $html_head.="</title>\n<style>\n";
  $html_head.=file_get_contents('style.css');
  $html_head.="\n</style>\n</head>\n<body>\n";

  if ($mode == 'html')
    $disclaimer='<p><a href="../index.html">На главную</a></p>';
  else
    $disclaimer='<p><a href="?base=home">На главную</a></p>';

  $disclaimer.="<p>Данные Викиданных от $data_date. Обновляются раз в неделю.</p>";

  $disclaimer2="<div class=\"disclaimer\"><p>Не спешите исправлять ошибки! Проверьте, может так и должно быть.</p></div>\n";

  $legend='<table class="legend"><tr><td class="dup">Элементы с одинаковым кодом</td><td class="bad">Неожиданный код</td><td class="alter">Элемента с таким кодом нет,<br>есть с аналогичным кодом</td></tr></table>';
  
  # Начинаем выводить html

  if ($mode == 'html') {
    if ($level > 1)
      $handle=fopen ("html/$base_table/".$code.'.html', "w");
    else
      $handle=fopen ("html/$base_table/$base_root_page", "w");

    fwrite ($handle, $html_head.$disclaimer.$disclaimer2.$legend);
  }
  else
    echo $html_head.$disclaimer.$disclaimer2.$legend;

  if ($mode == 'html')
    fwrite ($handle, $navigation);
  else
    echo $navigation;

  # Формируем шапку таблицы
  $table='<table class="data"><thead><tr>';
  $table.='<th>Найдено вложенных элементов</th>';

  if ($base_table == 'okato') $table.='<th>ОКАТО</th><th>Аналог. ОКТМО</th>';
    elseif ($base_table == 'oktmo') $table.='<th>ОКТМО</th><th>Аналог. ОКАТО</th>';

  $table.='<th>Элемент классификатора</th>';
  $table.='<th>Элемент Викиданных</th>';
  $table.='<th><span class="label">Метка</span>, псевдонимы</th>';
  $table.='<th>Тип территории</th>';
  $table.='<th>Описание</th>';

  if ($base_table == 'okato') $table.='<th>ОКАТО в Викиданных</th><th>ОКТМО в Викиданных</th>';
    elseif ($base_table == 'oktmo') $table.='<th>ОКТМО в Викиданных</th><th>ОКАТО в Викиданных</th>';

  $table.='<th><abbr title="Административно-территориальная единица">АТЕ</abbr></th>';
  $table.='<th>Центр</th>';
  # На некоторых уровнях определённые столбцы не выводятся
  if ($level != 1) {
    $table.='<th>Широта</th>';
    $table.='<th>Долгота</th>';
    $table.='<th>Телефонный код</th>';
    $table.='<th>Индекс</th>';
  }
  $table.='</tr></thead>'."\n";

  if ($mode == 'html')
    fwrite ($handle, $table);
  else
    echo $table; # Выводим шапку таблицы
    
    # Формируем запрос для списка объектов, входящих в текущее образование
    # Формируем условие для выборки

  switch ($level) {
    case 1 : {
      $clause='ter<>\'00\' AND kod1=\'000\' AND kod2=\'000\' AND kod3=\'000\'';
      break;
    }
    case 2 : {
      $clause='ter=\''.$row['ter'].'\' AND kod1<>\'000\' AND kod2=\'000\' AND kod3=\'000\'';
      break;
    }
    case 3 : {
      $clause='ter=\''.$row['ter'].'\' AND kod1=\''.$row['kod1'].'\'  AND ( ( kod2<>\'000\' AND kod3=\'000\') OR ( kod2=\'000\' AND kod3<>\'000\') )';
      break;
    }
    case 4 : {
      $clause='ter=\''.$row['ter'].'\' AND kod1=\''.$row['kod1'].'\'  AND kod2=\''.$row['kod2'].'\' AND kod3<>\'000\'';
      break;
    }
  }
    
  $query="SELECT type, name, nomdeskr, exist, numfound, mergedcode FROM $base_table WHERE $clause";
  $result=mysqli_query ($link, $query);
  
  # А теперь для каждого объекта в текущем образовании ищем соответсвующий элемент Викиданных

  while ($row=mysqli_fetch_array ($result, MYSQLI_ASSOC)) {
    $base_code=$row ['mergedcode'];
    $alter_code=analog($link, $base_table, $base_code);

    $clause=$base_table.'=\''.$base_code.'\'';    

    # Формируем список полей
    $fields='item';
    $fields.=', label';
    $fields.=', description';
    $fields.=', oktmo';
    $fields.=', okato';
    $fields.=', ate';
    $fields.=', centrum';
    # На некоторых уровнях определённые поля не запрашиваются
    if ($level != 1) {
      $fields.=', lat';
      $fields.=', lon';
      $fields.=', phone';
      $fields.=', post';
    }
    
    # Инициализируем флаги
    # Элементы с дублирующимися кодами
    $base_dup=false;
    $alter_dup=false;

    # С неожиданными (возможно - ошибочными) кодами
    $base_bad=false;
    $alter_bad=false;

    # Элементы, найденные только по аналогичному коду в другом классификаторе
    $alter=false;

    $query="SELECT $fields FROM wikidata WHERE ($clause)";
    $result_w=mysqli_query ($link, $query);

    $num_items=mysqli_num_rows($result_w);

    # Элементов с этим кодом больше одного. Сталоб быть - дубликат.
    if (1 < $num_items)
      $base_dup=true;

    # Элементов с данным кодом не нашлось. Попробуем найти с аналогичным (если есть такой).
    if ( (0 == $num_items) and ($alter_code !='') ) {
      $clause=$alter_table.'=\''.$alter_code.'\'';
      $query="SELECT $fields FROM wikidata WHERE ($clause)";
      $result_w=mysqli_query ($link, $query);

      $num_items=mysqli_num_rows($result_w);

      if (0 != $num_items)
	$alter=true;

      if (1 < $num_items)
	$alter_dup=true;
    }

    # Берём первый найденный элемент Викиданных
    $row_w=mysqli_fetch_array ($result_w, MYSQLI_ASSOC);

    # Готовимся объединить ячейки с кодами и наименованием объекта классификатора, чтобы не выводить их для каждого дубликата
    $rowspan='';

    if (1 < $num_items)
      $rowspan=" rowspan=\"$num_items\"";

    # Инициализируем количество вложенных объектов у текущего объекта классификатора и количество найденных элементов у вложенных объектов
    $elements_exist=0;
    $elements_found=0;

    do {
      if ($row['exist'] != 0) {
	$elements_exist=$row['exist'];
	$elements_found=$row['numfound'];
      }

      # Элемент может иметь несколько псевдонимов и типов. Берём их из отдельных таблиц.
      $query='SELECT alias FROM aliases WHERE (item=\''.$row_w['item'].'\')';
      $result_a=mysqli_query ($link, $query);

      $query='SELECT types.type, atd_items.label FROM types, atd_items WHERE (types.item=\''.$row_w['item'].'\' AND types.type=atd_items.item)';
      $result_t=mysqli_query ($link, $query);

      # Подготовим всплывающий комментарий к объекту из классификатора
      $title=' title="'.$row['nomdeskr'].'"';

      # Считаем статистику существующих и найденных вложенных объектов
      if ($elements_exist != 0) {
	$status_found=$elements_found.' из '.$elements_exist.' ';

        # Если процент найденных элементов не целый, выводим приблизительное значение
	if ($elements_found == 0)
	  $percents=0;
	    elseif ( ((100*$elements_found)%$elements_exist) != 0 ) {
	      $status_found.='~';
	      $percents=round((100*$elements_found)/$elements_exist);
	    }
	    else
	      $percents=(100*$elements_found)/$elements_exist;
	$status_found.=$percents.'%';
      }
      else
	$status_found='';
      
      # Проверка соответствия кодов элемента классификаторам (проверка на неожиданность).
      if (($row_w[$base_table] != $base_code) and (isset ($row_w [$base_table])) )
	$base_bad=true;

      if (($row_w [$alter_table] != $alter_code) and (isset ($row_w [$alter_table])) )
	$alter_bad=true;

      # Формируем вывод строк таблицы
      # Раскрашиваем строки с дубликатами и элементами, найденными по альтернативному коду
      if ($base_dup or $alter_dup)
	$output_row='<tr class="dup">';
	elseif ($alter)
	  $output_row='<tr class="alter">';
	else
	  $output_row='<tr>';

      # Такой вот мудрёный способ оформления дублирующихся элементов
      if ( !(($base_dup or $alter_dup) and ($num_items == 1)) ) {
	$output_row.='<th'.$rowspan.'>'.$status_found.'</th>';
	$output_row.='<th'.$rowspan;

        # Раскрашиваем неожиданные коды в части данных классификатора (блок серых ячеек)
	if ($base_bad)
	  $output_row.=' class="bad"';

	$output_row.='>'.$base_code.'</th>';
	$output_row.='<th'.$rowspan;

	if ($alter_bad)
	  $output_row.=' class="bad"';

	$output_row.='>'.$alter_code.'</th>';
	$output_row.='<th';

        # Добавляем всплывающий коммментарий из классификатора, при наличии
	if (isset ($row['nomdeskr']))
	  $output_row.=$title;

	$output_row.=$rowspan;

        # Если у объекта есть вложенные объекты, его наименование становится ссылкой
	if ($elements_exist != 0) {
	  if ($mode == 'html')
	    $output_row.='><a href="'.$row ['mergedcode'].'.html">'.$row['type'].' '.$row['name'].'</a></th>';
	  else
	    $output_row.='><a href="?base='.$base_table.'&amp;code='.$row ['mergedcode'].'">'.$row['type'].' '.$row['name'].'</a></th>';
	}
	else
	  $output_row.='>'.$row['type'].' '.$row['name'].'</th>';
      }

      # Информацию классификатора отобразили. Если элемента Викиданных с таким кодом не нашлось, выводим пустые ячейки и переходим к следующему объекту.
      if (!isset ($row_w['item'])) {
        if ($level == 1)
          $output_row.='<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>'."\n";
        else
          $output_row.='<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>'."\n";
        
        if ($mode == 'html')
          fwrite ($handle, $output_row);
        else
          echo $output_row; # Выводим строку таблицы
        $rowspan='';
        $num_items=1;
        continue;
      }

        # Элемент для текущего объекта есть. Выводим его свойства.
      
        # Идентификатор
	$output_row.='<td class="numeric"><a href="https://www.wikidata.org/wiki/'.$row_w['item'].'" target="blank">'.$row_w['item'].'</a></td>';
	$output_row.='<td>';
        # Метка
	$output_row.='<p class="label">'.$row_w['label'].'</p>';

        # Псевдонимы
	while ($row_a=mysqli_fetch_array ($result_a, MYSQLI_ASSOC))
	  $output_row.='<p>'.$row_a ['alias'].'</p>';
	mysqli_free_result($result_a);

	$output_row.='</td>';
	$output_row.='<td>';

        # Типы объекта (область, город, район, округ, столица ...)
	while ($row_t=mysqli_fetch_array ($result_t, MYSQLI_ASSOC))
	  $output_row.='<p><a href="https://www.wikidata.org/wiki/'.$row_t['type'].'" target="blank">'.$row_t['label'].'</a></p>';
	mysqli_free_result($result_t);

	# Описание
	$output_row.='</td>';
	$output_row.='<td>'.$row_w['description'].'</td>';
	$output_row.='<td class="numeric';

        # Отметим неожиданный код, если есть
	if ($base_bad)
	  $output_row.=' bad';

        # Код в Викиданных по текущему классификатору
	$output_row.='">'.$row_w ["$base_table"].'</td>'; 
	$output_row.='<td class="numeric';

	if ($alter_bad)
	  $output_row.=' bad';

        # Код по альтернативному классификатору
	$output_row.='">'.$row_w ["$alter_table"].'</td>';

        # Ссылка на элемент Викиданных в роли административно-территориальной единицы...
	if (isset ($row_w['ate']))
	  $output_row.='<td><a href="https://www.wikidata.org/wiki/'.$row_w['ate'].'" target="blank">'.get_label ($link, $row_w['ate']).'</a></td>';
	else
	  $output_row.='<td></td>';

        # ... и административного центра
	if (isset ($row_w['centrum']))
	  $output_row.='<td><a href="https://www.wikidata.org/wiki/'.$row_w['centrum'].'" target="blank">'.get_label ($link, $row_w['centrum']).'</a></td>';
	else
	  $output_row.='<td></td>';

        # Широта, долгота, телефонный код, почтовый индекс
	if ($level != 1) {
	  $output_row.='<td class="numeric">'.$row_w['lat'].'</td>';
	  $output_row.='<td class="numeric">'.$row_w['lon'].'</td>';
	  $output_row.='<td class="numeric">'.$row_w['phone'].'</td>';
	  $output_row.='<td class="numeric">'.$row_w['post'].'</td>';
	}

	$output_row.='</tr>'."\n";

       # В зависимости от режима работы, выводим в файл, либо в браузер.
      if ($mode == 'html')
	fwrite ($handle, $output_row);
      else
	echo $output_row; # Выводим строку таблицы
	    
      # Вывод дубликатов обработали. инициализируем переменные
      $rowspan='';
      $num_items=1;
    } while ($row_w=mysqli_fetch_array ($result_w, MYSQLI_ASSOC)); # Пробуем взять ещё дубликат
  mysqli_free_result($result_w);
  }

  if ($mode == 'html')
    fwrite ($handle, "</table></body></html>\n");
  else
    echo '</table></body></html>'."\n";

  mysqli_free_result($result);
}

?>