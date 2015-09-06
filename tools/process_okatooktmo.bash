#!/bin/bash

source values.bash

echo -e "\n`date +%T` Выполняется обработка таблицы соответствия ОКАТО и ОКТМО\n"

cat tools/process_okatooktmo.sql | \
mysql --user=$USER --password=$PASS $DB > /dev/null

TIME=$SECONDS
let "SEC= TIME % 60"
let "MIN= (TIME / 60) % 60"
let "HOUR= TIME / 3600"

echo -e "\n`date +%T` Таблица соответствия ОКАТО и ОКТМО обработана за $HOUR ч. $MIN мин. $SEC сек.\n"

exit 0
