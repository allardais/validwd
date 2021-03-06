#! /bin/bash

source values.bash

(
tools/get_json.bash $1
tools/reset_num_found.bash okato
tools/reset_num_found.bash oktmo
tools/create_table_atd_items.bash
tools/process_json.php
tools/import_wikidata.bash
tools/import_index.bash
tools/check_found.bash okato
tools/check_found.bash oktmo
tools/num_found.bash okato
tools/num_found.bash oktmo
tools/get_lost.php
mv git/allardais.github.io/validwd prev/html`date --date="last Monday" +%d%m%Y`
mkdir git/allardais.github.io/validwd
mkdir git/allardais.github.io/validwd/okato
mkdir git/allardais.github.io/validwd/oktmo
tools/make_html.php
) | tee log/`date +%d%m%Y`.log
