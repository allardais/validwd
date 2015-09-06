<?php
include_once ('values.php');
include ('tools/library.php');

error_reporting (E_ALL);

$link=get_link ($db_host, $db_user, $db_pass, $db_name);

$data_date=substr(file_get_contents($work_files_path.'data_date'),0,10);

$mode='direct';

if (!isset ($_GET ['code'])) $_GET ['code']='';
if (!isset ($_GET ['base'])) $_GET ['base']='home';

if ($_GET ['base'] == 'lost')
  include ('lost.php');
elseif ($_GET ['base'] == 'home')
  include ('l0.php');
else
  print_table ($link, $data_date, $_GET ['base'], $_GET ['code']);

?>
