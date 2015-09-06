#!/usr/bin/php
<?php
include ('values.php');
include ('tools/library.php');
declare(ticks=1);

date_default_timezone_set('UTC');

function sig_handler($signo, $link=null, $result=null) {
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

pcntl_signal(SIGTERM, "sig_handler");
pcntl_signal(SIGINT, "sig_handler");
pcntl_signal(SIGHUP,  "sig_handler");
pcntl_signal(SIGUSR1, "sig_handler");

$last_time='2015-08-10T17:30:00';

#$last_time=file_get_contents($work_files_path.'last_time');

file_put_contents ($work_files_path.'wd.rss', file_get_contents('https://www.wikidata.org/w/api.php?action=feedrecentchanges&namespace=0&from='.$last_time));

file_put_contents ($work_files_path.'last_time', rtrim(date('c'), '+00:00'));

$handle_rss=fopen($work_files_path.'wd.rss','r');
$handle_items=fopen($work_files_path.'items_list','rw');

while ($line=fgets($handle_rss)) {
  if (strpos($line, '<title>Q')) {
    $line=preg_replace('/^.*\Q<title>\E(Q\d+).*$\n/', '\1', $line);
    fwrite($handle_items, $line."\n");
  }
}

$line=fgets($handle_items);
$ids=rtrim($line,"\n");

while (true) {
  if ($line=fgets($handle_items)){
    $ids.='|';
    $ids.=rtrim($line,"\n");
  }
  else
    break;
}

echo $ids;

file_put_contents ($work_files_path.'incr.json', file_get_contents('https://www.wikidata.org/w/api.php?action=wbgetentities&ids='.$ids.'&format=json'));

?>
