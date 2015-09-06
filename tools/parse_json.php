#!/usr/bin/php
<?php
include ('values.php');
include ('tools/library.php');
declare(ticks=1);

date_default_timezone_set('Europe/Moscow');

function sig_handler($signo, $link=null, $result=null)
{
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

$handle=fopen ($work_files_path."base.json", "r");

$handle_main=fopen ($work_files_path."wikidata.csv", "w");

$start=1;
$count=0;

$num_items=false;

$imported=0;

echo date("H:i:s")." Выполняется выборка из json\n\n";

$time=-time();

while (($s1=fgets ($handle)) !== false) {
  $count++;

  $status='Обрабтано '.sprintf("%10d", $count).' объектов';
  fwrite (STDERR, "\r$status");

  if (substr ($s1, -2,1) == ',')
    $trim=strlen($s1)-2;
  else
    $trim=strlen($s1)-1;

  $item=json_decode (substr ($s1, 0, $trim), true);

  if ($item !== null) {
    if (isset ($item ["labels"]["ru"]["value"])) 
      fwrite ($handle_main, $item ["id"]."\tlabel\t".$item ["labels"]["ru"]["value"]."\n");

    if (array_key_exists ("aliases", $item))
      if (array_key_exists ("ru", $item ["aliases"]))
        foreach ($item ["aliases"]["ru"] as $alias)
          fwrite ($handle_main, $item ["id"]."\talias\t".$alias["value"]."\n");

      if (isset ($item ["descriptions"]["ru"]["value"]))
        fwrite ($handle_main, $item ["id"]."\tdescription\t".$item ["descriptions"]["ru"]["value"]."\n");

      if (!array_key_exists ("claims", $item))
        continue;

      foreach ($item["claims"] as $claim) {
        foreach ($claim as $property) {
          if ($property["mainsnak"]["snaktype"] == 'novalue' ) {
            fwrite ($handle_main, $item ["id"]."\t".$property['mainsnak']['property']."\tnovalue\n");
            continue;
          }

          if ($property["mainsnak"]["snaktype"] == 'somevalue' ) {
            fwrite ($handle_main, $item ["id"]."\t".$property['mainsnak']['property']."\tsomevalue\n");
            continue;
          }

          switch ($property["mainsnak"]["datatype"]) {
            case 'wikibase-item' : {
              fwrite ($handle_main, $item ["id"]."\t".$property['mainsnak']['property']."\tQ".$property["mainsnak"]["datavalue"]["value"]["numeric-id"]."\n");
              break;
            }

            case 'wikibase-property' : {
              fwrite ($handle_main, $item ["id"]."\t".$property['mainsnak']['property']."\tP".$property["mainsnak"]["datavalue"]["value"]["numeric-id"]."\n");
              break;
            }

            case 'url' : {
              fwrite ($handle_main, $item ["id"]."\t".$property['mainsnak']['property']."\t".$property["mainsnak"]["datavalue"]["value"]."\n");
              break;
            }

            case 'globe-coordinate' : {
              fwrite ($handle_main, $item ["id"]."\t".$property['mainsnak']['property']."\t".$property["mainsnak"]["datavalue"]["value"]["latitude"]."\n");
              fwrite ($handle_main, $item ["id"]."\t".$property['mainsnak']['property']."\t".$property["mainsnak"]["datavalue"]["value"]["longitude"]."\n");
              break;
            }

            case 'commonsMedia' : {
              fwrite ($handle_main, $item ["id"]."\t".$property['mainsnak']['property']."\t".$property["mainsnak"]["datavalue"]["value"]."\n");
              break;
            }

            case 'time' : {
              fwrite ($handle_main, $item ["id"]."\t".$property['mainsnak']['property']."\t".$property["mainsnak"]["datavalue"]["value"]["time"]."\n");
              break;
            }

            case 'string' : {
              fwrite ($handle_main, $item ["id"]."\t".$property['mainsnak']['property']."\t".$property["mainsnak"]["datavalue"]["value"]."\n");
              break;
            }

            case 'monolingualtext' : {
              fwrite ($handle_main, $item ["id"]."\t".$property['mainsnak']['property']."\t".$property["mainsnak"]["datavalue"]["value"]["text"]."\n");
              break;
            }
          }
        }
      }
  }
}

$time+=time();
$sec=$time%60;
$min=((int)($time/60))%60;
$hour=(int)($time/3600);

echo "\n\n".date("H:i:s")." Выборка завершена за $hour ч. $min мин. $sec сек.\n\n";

fclose ($handle);
fclose ($handle_main);
?>
