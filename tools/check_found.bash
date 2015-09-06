#!/bin/bash

source values.bash

HELP_TEXT="\nСкрипт отмечает коды классификатора, для которых есть соответствующие элементы Викиданных.\n\n\
Укажите в качестве аргумента okato, либо oktmo.\n"

source tools/arg_check.bash

echo -e "\n`date +%T` Выполняется отметка найденных элементов по классификатору $CLASSIF_NAME\n"

cat tools/check_found_$1.sql | \
mysql --user=$USER --password=$PASS $DB > /dev/null

TIME=$SECONDS
let "SEC= TIME % 60"
let "MIN= (TIME / 60) % 60"
let "HOUR= TIME / 3600"

echo -e "\n`date +%T` Найденные по $CLASSIF_NAME элементы отмечены за $HOUR ч. $MIN мин. $SEC сек.\n"

exit 0
