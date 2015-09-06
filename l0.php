<?php

$html_head="<!DOCTYPE html>
<html>
<head>
<meta charset=\"utf-8\">
<title>Валидатор Викиданных";
$html_head.="</title>
<style>
body {
	margin: 20px 20px;
	background-color: #eeeeee;
}

a {
  color: black;

}

#cards {
font-size: 24px;
    list-style-type: none;
    margin: 0 0 50px;
    padding: 0;
    text-align: center;
}

#cards li {
margin: 50px;
    display: inline;

}

#cards a {box-shadow: 0 0 10px rgba(0,0,0,0.5);
  color: white;
  text-decoration: none;
  background: green none repeat scroll 0 0;
  border: 5px double white;
  border-radius: 10px;
  display: inline-block;
  padding: 12pt 8pt;
  vertical-align: middle;
  width: 10em;
}
";

$html_head.=file_get_contents('style.css');
$html_head.="
</style>";
$html_head.="
</head>
<body>
";

$disclaimer="<h1>Валидатор Викиданных</h1><p>Данные Викиданных от $data_date. Обновляются раз в неделю.</p>";

if ($mode == 'html') {
  $handle=fopen ("html/index.html", "w");
  fwrite ($handle, $html_head.$disclaimer);
}
else
  echo $html_head.$disclaimer;

$query='SELECT SQL_CALC_FOUND_ROWS 1 FROM okato LIMIT 0';
mysqli_query ($link, $query);
$num_okato=found_rows ($link);

$query='SELECT SQL_CALC_FOUND_ROWS 1 FROM okato WHERE found=1 LIMIT 0';
mysqli_query ($link, $query);
$num_okato_found=found_rows ($link);

$query='SELECT SQL_CALC_FOUND_ROWS 1 FROM oktmo LIMIT 0';
mysqli_query ($link, $query);
$num_oktmo=found_rows ($link);

$query='SELECT SQL_CALC_FOUND_ROWS 1 FROM oktmo WHERE found=1 LIMIT 0';
mysqli_query ($link, $query);
$num_oktmo_found=found_rows ($link);

$status_okato=$num_okato_found.' из '.$num_okato.' ';

if ( ((100*$num_okato_found)%$num_okato) != 0 ) {
  $status_okato.='~';
  $percents=round((100*$num_okato_found)/$num_okato);
}
else
  $percents=(100*$num_okato_found)/$num_okato;

$status_okato.=$percents.'%';

$status_oktmo=$num_oktmo_found.' из '.$num_oktmo.' ';

if ( ((100*$num_oktmo_found)%$num_oktmo) != 0 ) {
  $status_oktmo.='~';
  $percents=round((100*$num_oktmo_found)/$num_oktmo);
}
else
  $percents=(100*$num_oktmo_found)/$num_oktmo;

$status_oktmo.=$percents.'%';

if ($mode == 'html')
  $okato_link="<a href=\"okato/00.html\">ОКАТО<br>найдено элементов\n<br>$status_okato</a>";
else
  $okato_link="<a href=\"?base=okato\">ОКАТО<br>найдено элементов\n<br>$status_okato</a>";

if ($mode == 'html')
  $oktmo_link="<a href=\"oktmo/00000000.html\">ОКТМО<br>найдено элементов\n<br>$status_oktmo</a>";
else
  $oktmo_link="<a href=\"?base=oktmo\">ОКТМО<br>найдено элементов\n<br>$status_oktmo</a>";

if ($mode == 'html')
  $lost='<a href="lost.html">Эти элементы, возможно, требуют проверки</a>';
else
  $lost='<a href="?base=lost">Эти элементы, возможно, требуют проверки</a>';

$content="
<ul id=\"cards\">
<li>
$okato_link
</li>
<li>
$oktmo_link
</li>
</ul>
$lost
</body>
</html>
";

if ($mode == 'html')
  fwrite ($handle, $content);
else
  echo $content;

?> 
