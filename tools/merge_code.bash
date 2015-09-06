#!/bin/bash

source values.bash

HELP_TEXT="\nСкрипт объединяет части ter, kod1, kod2, kod3 в единое целое с удалением лишних нулей для указанного классификатора.\n\n\
Укажите в качестве аргумента okato, либо oktmo.\n"

source tools/arg_check.bash

echo -e "\n`date +%T` Выполняется объединение частей кодов в классификаторе $CLASSIF_NAME\n"

cat tools/merge_$1.sql | \
mysql --user=$USER --password=$PASS $DB > /dev/null

TIME=$SECONDS
let "SEC= TIME % 60"
let "MIN= (TIME / 60) % 60"
let "HOUR= TIME / 3600"

echo -e "`date +%T` Части кодов $CLASSIF_NAME объединены за $HOUR ч. $MIN мин. $SEC сек.\n"

exit 0
