#!/bin/bash

source values.bash

HELP_TEXT="\nСкрипт определяет количество найденных объектов для элементов классификатора из раздела 2 (округа, районы, поселения и т.д.)\n\n\
Укажите в качестве аргумента okato, либо oktmo.\n"

source tools/arg_check.bash

echo -e "\n`date +%T` Выполняется определение количества найденных объектов для элементов классификатора из раздела 2 $CLASSIF_NAME\n"

cat tools/num_found_$1.sql | \
mysql --user=$USER --password=$PASS $DB > /dev/null

TIME=$SECONDS
let "SEC= TIME % 60"
let "MIN= (TIME / 60) % 60"
let "HOUR= TIME / 3600"

echo -e "\n`date +%T` Найденные по $CLASSIF_NAME объекты посчитаны за $HOUR ч. $MIN мин. $SEC сек.\n"

exit 0
