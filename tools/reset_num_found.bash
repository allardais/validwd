#!/bin/bash

source values.bash

HELP_TEXT="\nСкрипт обнуляет результаты поиска кодов классификатора, для которых есть соответствующие элементы Викиданных.\n\n\
Укажите в качестве аргумента okato, либо oktmo.\n"

source tools/arg_check.bash

echo -e "\n`date +%T` Выполняется обнуление результатов поиска по $CLASSIF_NAME\n"

if [ $1 = "okato" ]
  then
    QUERY="UPDATE okato SET found=0, numfound=0; UPDATE wikidata SET okatopassed=0;"
  else
    if [ $1 = "oktmo" ]
      then
	QUERY="UPDATE oktmo SET found=0, numfound=0; UPDATE wikidata SET oktmopassed=0;"
    fi
fi

echo $QUERY | \
mysql --user=$USER --password=$PASS $DB

TIME=$SECONDS
let "SEC= TIME % 60"
let "MIN= (TIME / 60) % 60"
let "HOUR= TIME / 3600"

echo -e "\n`date +%T` Результаты поиска по $CLASSIF_NAME обнулены за $HOUR ч. $MIN мин. $SEC сек.\n"

exit 0
