<?php
$html_head="<!DOCTYPE html>
<html>
<head>
<meta charset=\"utf-8\">
<title>Валидатор Викиданных";
$html_head.="</title>
<style>
";

$html_head.=file_get_contents('style.css');
$html_head.="
.item { font-size: small; }
</style>";
$html_head.="
</head>
<body>
";

$query='SELECT item, label, description FROM wikidata WHERE (okato IS NULL) AND (oktmo IS NULL) AND item<>\'Q159\' AND item<>\'\'';
$result=mysqli_query ($link, $query);

if ($mode == 'html')
  $back='<p><a href="index.html">На главную</a></p>';
else
  $back='<p><a href="?base=home">На главную</a></p>';

$text= "<p>Данные Викиданных от $data_date. Обновляются раз в неделю.</p><p>Элементы, у которых не указаны ни ОКАТО, ни ОКТМО, но они фигурируют в качестве административного центра или административно-территориальной единицы.</p>\n";

$table= '<table class="data"><thead><tr><th></th><th>Элемент</th><th>Описание</th><th>Элементы, которые на него ссылаются</th></tr></thead>'."\n";

     if ($mode == 'html')
	{
	  $handle=fopen ("html/lost.html", "w");
	  fwrite ($handle, $html_head.$back.$text.$table);
	}
      else
	echo $html_head.$back.$text.$table;

$i=0;

while ($row=mysqli_fetch_array ($result, MYSQLI_ASSOC))
  {
    $i++;
    $item=$row['item'];
    $query="SELECT item, label FROM wikidata WHERE centrum='$item' OR ate='$item'";
    $result_l=mysqli_query ($link, $query);

    $linked_items='';
    $j=0;

    while ($row_l=mysqli_fetch_array ($result_l, MYSQLI_ASSOC))
      {
	$j++;	
	if ($j > 1)
	  $linked_items.=', ';
	$linked_items.='<a href="https://www.wikidata.org/wiki/'.$row_l['item'].'" target="blank">'.$row_l['label'].'</a>';

	
      }
    $output_row= '<tr><td>'.$i.'</td><td><a href="https://www.wikidata.org/wiki/'.$row['item'].'" target="blank">'.$row['label'].' <span class="item">('.$row['item'].')</span></a></td><td>'.$row['description'].'</td><td>'.$linked_items.'</td></tr>'."\n";

    if ($mode == 'html')
      fwrite ($handle, $output_row);
    else
      echo $output_row;
    
  }

    if ($mode == 'html')
      {
	fwrite ($handle, '</table>'."\n");
      }
    else
      echo '</table>'."\n";

mysqli_free_result ($result);

$query='SELECT item, label, description, oktmo FROM wikidata WHERE oktmopassed=0 AND (oktmo IS NOT NULL) AND item<>\'Q159\'  AND item<>\'\'';
$result=mysqli_query ($link, $query);

$text= '<p>Элементы, у которых указаны ОКТМО, но эти коды в классификаторе не найдены.</p>'."\n";

$table= '<table class="data"><thead><tr><th></th><th>Элемент</th><th>Описание</th></tr></thead>'."\n";

     if ($mode == 'html')
	{
	  fwrite ($handle, $text.$table);
	}
      else
	echo $text.$table;

$i=0;

while ($row=mysqli_fetch_array ($result, MYSQLI_ASSOC))
  {
	$i++;
        $output_row= '<tr><td>'.$i.'</td><td><a href="https://www.wikidata.org/wiki/'.$row['item'].'" target="blank">'.$row['label'].' <span class="item">('.$row['item'].')</span></a> '.$row['oktmo'].'</td><td>'.$row['description'].'</td></tr>'."\n";

    if ($mode == 'html')
      fwrite ($handle, $output_row);
    else
      echo $output_row;
  }

    if ($mode == 'html')
      {
	fwrite ($handle, '</table>'."\n");
      }
    else
      echo '</table>'."\n";

mysqli_free_result ($result);

$query='SELECT item, label, description, okato FROM wikidata WHERE okatopassed=0 AND (okato IS NOT NULL) AND item<>\'Q159\'  AND item<>\'\'';
$result=mysqli_query ($link, $query);

$text= '<p>Элементы, у которых указаны ОКАТО, но эти коды в классификаторе не найдены.</p>'."\n";

$table= '<table class="data"><thead><tr><th></th><th>Элемент</th><th>Описание</th></tr></thead>'."\n";

     if ($mode == 'html')
	{
	  fwrite ($handle, $text.$table);
	}
      else
	echo $text.$table;

$i=0;

while ($row=mysqli_fetch_array ($result, MYSQLI_ASSOC))
  {
	$i++;
        $output_row= '<tr><td>'.$i.'</td><td><a href="https://www.wikidata.org/wiki/'.$row['item'].'" target="blank">'.$row['label'].' <span class="item">('.$row['item'].')</span></a> '.$row['okato'].'</td><td>'.$row['description'].'</td></tr>'."\n";

    if ($mode == 'html')
      fwrite ($handle, $output_row);
    else
      echo $output_row;
  }

if ($mode == 'html')
  {
    fwrite ($handle, '</table></body></html>'."\n");
  }
else
  echo '</table></body></html>'."\n";
?> 
