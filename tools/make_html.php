#!/usr/bin/php
<?php
include_once ('values.php');
include ('tools/library.php');
declare(ticks=1);

pcntl_signal(SIGTERM, "sig_handler");
pcntl_signal(SIGINT, "sig_handler");
pcntl_signal(SIGHUP,  "sig_handler");
pcntl_signal(SIGUSR1, "sig_handler");

function make_html ($link, $data_date, $base_table) {
  if ($base_table == 'okato')
    $classif="ОКАТО";
  else
    $classif="ОКТМО";

  echo date("H:i:s")." Генерация html для классификатора $classif\n\n";
  
  $time=-time();
  
  print_table ($link, $data_date, $base_table,'', 'html');
  
  $i=1;
  
  $query='SELECT mergedcode FROM '.$base_table.' WHERE mergedcode<>\'00000000\' AND exist<>0';
  $result=mysqli_query ($link, $query);
  $num_pages=mysqli_num_rows($result);
  
  while ($row=mysqli_fetch_array ($result, MYSQLI_ASSOC)) {
    $percents=(100*$i)/$num_pages;
    $status=sprintf("%3d", $percents).'% Обработано '.sprintf("%7d", $i).' из '.sprintf("%7d", $num_pages).' ';
    fwrite(STDERR, "\r$status");
    
    print_table ($link, $data_date, $base_table, $row['mergedcode'], 'html');
    
    $i++;
  }
  
  $time+=time();

  echo "\n\n".date("H:i:s")." Генерация html для $classif выполнена за ".hms($time)."\n\n";
}

$link=get_link ($db_host, $db_user, $db_pass, $db_name);


$data_date=substr(file_get_contents($work_files_path.'data_date'),0,10);

$time=-time();

make_html ($link, $data_date, 'okato');

make_html ($link, $data_date, 'oktmo');

$mode='html';

include ('l0.php');

include ('lost.php');

$time+=time();

echo "\n\n".date("H:i:s").' Генерация html выполнена за '.hms($time)."\n\n";

mysqli_close($link);
?>
