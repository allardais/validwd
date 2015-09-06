#!/bin/bash

source values.bash

cd $WORK_FILES_PATH

echo "drop table if exists okatooktmo; \
create table okatooktmo ( \
        id integer not null auto_increment primary key,  \
        ustavname text, \
        type varchar (100), \
        okato varchar (11), \
        okatotemp varchar (11), \
        oktmo varchar (11), \
        oktmotemp varchar (11), \
        oktmoname varchar (200), \
        oktmoate varchar (8), \
        nameate text, \
	index ok_okato (okato), \
	index ok_oktmo (oktmo) ); \
load data local infile 'okatooktmo.csv' \
        into table okatooktmo \
        fields terminated by ';' \
        enclosed by '' \
        lines terminated by '\n' \
        ignore 1 lines \
        (ustavname, type, okato, okatotemp, oktmo, oktmotemp, oktmoname, oktmoate, nameate);" | \
mysql --user=$USER --password=$PASS --local-infile=1 $DB

cd -

TIME=$SECONDS
let "SEC= TIME % 60"
let "MIN= (TIME / 60) % 60"
let "HOUR= TIME / 3600"

echo -e "\n`date +%T` Импорт таблицы соответствия ОКАТО и ОКТМО выполнен за $HOUR ч. $MIN мин. $SEC сек.\n"
