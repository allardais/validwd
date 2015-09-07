#!/usr/bin/php
<?php
include ('values.php');
include ('tools/library.php');
declare(ticks=1);
pcntl_signal(SIGTERM, "sig_handler");
pcntl_signal(SIGINT, "sig_handler");
pcntl_signal(SIGHUP,  "sig_handler");
pcntl_signal(SIGUSR1, "sig_handler");

date_default_timezone_set('Europe/Moscow');

function get_from_json ($link, $handle, $item) {
  $query="SELECT pos, len FROM jsonindex WHERE item='$item'";
  $result=mysqli_query ($link, $query);
  
  # Если такого элемента в дампе нет, значит было слияние и теперь на его месте стоит перенаправление
  # Берём реальный элемент из Викиданных
  if (!($row=mysqli_fetch_array ($result, MYSQLI_ASSOC))) {
    $entity=get_from_wikidata($item);
    echo "\n$item взят из Викиданных\n";
    return $entity['entities'][$item];
  }

  fseek($handle, $row['pos']);
  $s=fgets($handle, $row['len']);
  
  if (substr ($s, -2,1) == ',')
    $trim=strlen($s)-2;
  else
    $trim=strlen($s)-1;
  
  mysqli_free_result($result);
  
  
  return json_decode (substr ($s, 0, $trim), true);
}

# Функция возвращает 0 если элемент есть в базе и 1 если элемент пришлось взять из дампа
function get_lost ($link, $handle, $row, $field) {
  if ($row [$field] == '')
    return 0;
  
  $item=$row [$field];
  if ($field == 'type')
    $table='atd_items';
  else
    $table='wikidata';
  
  $query="SELECT 1 FROM $table WHERE item='$item'";
  $result=mysqli_query ($link, $query);
  if ( $r=mysqli_fetch_array ($result, MYSQLI_ASSOC) ) {
    mysqli_free_result($result);
    return 0;
  }
  
  mysqli_free_result($result);
  $item_w=get_from_json ($link, $handle, $item);
  
  $values='\''.$item.'\'';
  $cols='item';
  if (isset ($item_w ['labels']['ru']['value'])) {
    $values.=', \''.$item_w ['labels']['ru']['value'].'\'';
    $cols.=', label';
  }
  
  # Для типа элемента нам нужна только метка.
  if ($field != 'type') {  
    if (isset ($item_w ['descriptions']['ru']['value'])) {
      $values.=', \''.$item_w ['descriptions']['ru']['value'].'\'';
      $cols.=', description';
    }
    if (array_key_exists ("claims", $item_w )) {
      if (array_key_exists ("P721", $item_w ['claims'])) {
        $values.=', \''.$item_w ['claims']['P721']['0']['mainsnak']['datavalue']['value'].'\'';
        $cols.=', okato';
      }
      if (array_key_exists ("P764", $item_w ['claims'])) {
        $values.=', \''.$item_w ['claims']['P764']['0']['mainsnak']['datavalue']['value'].'\'';
        $cols.=', oktmo';
      }
      if (array_key_exists ("P131", $item_w ['claims'])) {
        $values.=', \'Q'.$item_w ['claims']['P131']['0']['mainsnak']['datavalue']['value']['numeric-id'].'\'';
        $cols.=', ate';
      }
      if (array_key_exists ("P36", $item_w ['claims'])) {
        $values.=', \'Q'.$item_w ['claims']['P36']['0']['mainsnak']['datavalue']['value']['numeric-id'].'\'';
        $cols.=', centrum';
      }
      if (array_key_exists ("P17", $item_w ['claims'])) {
        $values.=', \'Q'.$item_w ['claims']['P17']['0']['mainsnak']['datavalue']['value']['numeric-id'].'\'';
        $cols.=', country';
      }
      if (array_key_exists ("P625", $item_w ['claims'])) {
        $values.=', \''.$item_w ['claims']['P625']['0']['mainsnak']['datavalue']['value']['latitude'].'\'';
        $values.=', \''.$item_w ['claims']['P625']['0']['mainsnak']['datavalue']['value']['longitude'].'\'';
        $cols.=', lat';
        $cols.=', lon';
      }
      if (array_key_exists ("P473", $item_w ['claims'])) {
        $values.=', \''.$item_w ['claims']['P473']['0']['mainsnak']['datavalue']['value'].'\'';
        $cols.=', phone';
      }
      if (array_key_exists ("P281", $item_w ['claims'])) {
        $values.=', \''.$item_w ['claims']['P281']['0']['mainsnak']['datavalue']['value'].'\'';
        $cols.=', post';
      }
    }
  }
  
  $query="INSERT INTO $table ($cols) VALUES ($values)";
  mysqli_query ($link, $query);
  
  # Увеличиваем количество добавленных элементов на единицу
  return 1;
}

$link=get_link ($db_host, $db_user, $db_pass, $db_name);

$handle=fopen($work_files_path."base.json", "r");

$time=-time();

# Выясняем количество имеющихся элементов
$query='SELECT SQL_CALC_FOUND_ROWS 1 FROM wikidata LIMIT 0';
mysqli_query ($link, $query);

$num_items=found_rows($link);

$num_added=0;

echo date("H:i:s")." Выполняется поиск потерянных элементов для АТЕ и административных центров\n\n";

# Просматриваем все элементы, добавляем недостающие из дампа
for ($i=1; $i <= $num_items; $i++) {
  $query='SELECT ate, centrum FROM wikidata WHERE id='.$i;
  $result=mysqli_query ($link, $query);
  $row=mysqli_fetch_array ($result, MYSQLI_ASSOC);
  mysqli_free_result($result);
  if (isset ($row['ate'])) $num_added+=get_lost($link, $handle, $row, 'ate');
  if (isset ($row['centrum'])) $num_added+=get_lost($link, $handle, $row, 'centrum');
  fwrite (STDERR, "\r".percents($num_items, $i).' Обработано '.sprintf("%10d", $i).' элементов из '.sprintf("%10d", $num_items).' добавлено элементов '.sprintf("%5d", $num_added).' ');
}

$query='SELECT SQL_CALC_FOUND_ROWS 1 FROM types LIMIT 0';
mysqli_query ($link, $query);

$num_items=found_rows($link);

$num_added=0;

echo "\n\n".date("H:i:s")." Выполняется поиск потерянных элементов для типов территорий\n\n";

for ($i=1; $i <= $num_items; $i++) {
  $query='SELECT type FROM types WHERE id='.$i;
  $result=mysqli_query ($link, $query);
  $row=mysqli_fetch_array ($result, MYSQLI_ASSOC);
  mysqli_free_result($result);
  if (isset ($row['type'])) $num_added+=get_lost($link, $handle, $row, 'type');
  fwrite (STDERR, "\r".percents($num_items, $i).' Обработано '.sprintf("%10d", $i).' элементов из '.sprintf("%10d", $num_items).' добавлено элементов '.sprintf("%5d", $num_added).' ');
}

$time+=time();

echo "\n\n".date("H:i:s").' Поиск выполнен за '.hms($time)."\n\n";

mysqli_close($link);
fclose($handle);

?>