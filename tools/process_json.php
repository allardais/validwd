#!/usr/bin/php
<?php
include_once ('values.php');
include ('tools/library.php');
declare(ticks=1);

pcntl_signal(SIGTERM, "sig_handler");
pcntl_signal(SIGINT, "sig_handler");
pcntl_signal(SIGHUP,  "sig_handler");
pcntl_signal(SIGUSR1, "sig_handler");

$handle=fopen ($work_files_path."base.json", "r");

$handle_main=fopen ($work_files_path."atd.csv", "w");
$handle_aliases=fopen ($work_files_path."aliases.csv", "w");
$handle_types=fopen ($work_files_path."types.csv", "w");
$handle_index=fopen ($work_files_path."index.csv", "w");

$size=filesize($work_files_path."base.json");

$count=0;
$imported=0;
$current_pos=0;
$last_pos=0;
$len=0;

fwrite (STDERR, date("H:i:s")." Выполняется выборка из json\n\n");

$time=-time();

while (($s1=fgets ($handle)) !== false) {
  $count++;
  
  $last_pos=$current_pos;
  $current_pos=ftell($handle);
  $len=$current_pos - $last_pos;
  
  $percents=(100*$current_pos)/$size;

  $status=sprintf("%3d", $percents).'% Просмотрено '.sprintf("%10d", $count).' импортировано '.sprintf("%10d", $imported).' ';
  fwrite (STDERR, "\r$status");
  
  # Если элемент не содержит свойств P721 (ОКАТО) или P764 (ОКТМО), просто сохраняем информацию о его длине и смещении и переходим к следующему.
  if ( !( (strpos($s1, 'P721":')) or (strpos($s1, 'P764":')) ) ) {
    $s1=substr($s1,0,100); // В 100 первых символов гарантированно попадает id элемента, урезанная строка быстрее обработается регулярным выражением.
    $s1=preg_replace('/^.*\Q"id":"\E([Q|P]\d+).*/','\1',$s1);
    if ( ($s1 != "[\n") and ($s1 != "]\n") ) // Дамп начинается со строки "[" и завершается строкой "]"
      fwrite ($handle_index, $s1.';'.$last_pos.';'.$len."\n");
    continue;
  }
  
  # Все строки элементов, кроме последней, заканчиваются запятой
  if (substr ($s1, -2,1) == ',')
    $trim=strlen($s1)-2;
  else
    $trim=strlen($s1)-1;
  
  $item=json_decode (substr ($s1, 0, $trim), true);
  
  fwrite ($handle_index, $item ["id"].';'.$last_pos.';'.$len."\n");

  # Если это свойство, пропускаем его
  if (substr($item ["id"],0,1)=='P')
    continue;

  $values=$item ["id"];
  if (isset ($item ["labels"]["ru"]["value"])) 
    $values.="\t".$item ["labels"]["ru"]["value"];
  else
    $values.="\t\N";
    
  if (array_key_exists ("aliases", $item))
    if (array_key_exists ("ru", $item ["aliases"]))
      foreach ($item ["aliases"]["ru"] as $alias)
        fwrite ($handle_aliases, $item ["id"].';'.$alias["value"]."\n");
        
  if (isset ($item ["descriptions"]["ru"]["value"])) 
    $values.="\t".$item ["descriptions"]["ru"]["value"];
  else
    $values.="\t\N";
        
  if (array_key_exists ("P721", $item ["claims"])) 
    $values.="\t".$item ["claims"]["P721"]["0"]["mainsnak"]["datavalue"]["value"];
  else
    $values.="\t\N";
        
  if (array_key_exists ("P764", $item ["claims"])) 
    $values.="\t".$item ["claims"]["P764"]["0"]["mainsnak"]["datavalue"]["value"];
  else
    $values.="\t\N";
        
  if (array_key_exists ("P131", $item ["claims"])) 
    $values.="\tQ".$item ["claims"]["P131"]["0"]["mainsnak"]["datavalue"]["value"]["numeric-id"];
  else
    $values.="\t\N";
      
  if (array_key_exists ("P36", $item ["claims"]))
    if (array_key_exists ("datavalue", $item ["claims"]["P36"]["0"]["mainsnak"])) 
      $values.="\tQ".$item ["claims"]["P36"]["0"]["mainsnak"]["datavalue"]["value"]["numeric-id"];
      else
        $values.="\t";
  else
    $values.="\t\N";
    
  if (array_key_exists ("P31", $item ["claims"]))
    foreach ($item["claims"]["P31"] as $type)
      fwrite ($handle_types, $item ["id"].';Q'.$type["mainsnak"]["datavalue"]["value"]["numeric-id"]."\n");
          
  if (array_key_exists ("P17", $item ["claims"])) 
    $values.="\tQ".$item ["claims"]["P17"]["0"]["mainsnak"]["datavalue"]["value"]["numeric-id"];
  else
    $values.="\t\N";
          
  if (array_key_exists ("P625", $item ["claims"])) {
    $values.="\t".$item ["claims"]["P625"]["0"]["mainsnak"]["datavalue"]["value"]["latitude"];
    $values.="\t".$item ["claims"]["P625"]["0"]["mainsnak"]["datavalue"]["value"]["longitude"];
  }
  else $values.="\t\N\t\N";
          
  if (array_key_exists ("P473", $item ["claims"])) 
    $values.="\t".$item ["claims"]["P473"]["0"]["mainsnak"]["datavalue"]["value"];
  else
    $values.="\t\N";
         
  if (array_key_exists ("P281", $item ["claims"])) 
    $values.="\t".$item ["claims"]["P281"]["0"]["mainsnak"]["datavalue"]["value"];
  else
    $values.="\t\N";
         
  fwrite ($handle_main, $values."\n");
  $imported++;
}

$time+=time();

echo "\n\n".date("H:i:s").' Выборка завершена за '.hms($time)."\n\n";

fclose ($handle);
fclose ($handle_main);
fclose ($handle_aliases);
fclose ($handle_types);
?>
