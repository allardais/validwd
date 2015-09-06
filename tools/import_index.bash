#!/bin/bash

source values.bash

echo -e "\n`date +%T` Выполняется импорт индекса json\n"

echo "drop table if exists jsonindex; \
create table jsonindex ( \
        item char (20) primary key, \
        pos bigint, \
        len int); \
load data local infile '"$WORK_FILES_PATH"index.csv' \
        into table jsonindex \
        fields terminated by ';' \
        enclosed by '' \
        lines terminated by '\n' \
        (item, pos, len);" | \
mysql --user=$USER --password=$PASS --local-infile=1 $DB

TIME=$SECONDS
let "SEC= TIME % 60"
let "MIN= (TIME / 60) % 60"
let "HOUR= TIME / 3600"

echo -e "\n`date +%T` Импорт индекса json выполнен за $HOUR ч. $MIN мин. $SEC сек.\n"

exit 0