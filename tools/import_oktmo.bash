#!/bin/bash

source values.bash

cd $WORK_FILES_PATH

echo "drop table if exists oktmo; \
create table oktmo ( \
        id integer not null auto_increment primary key,  \
        ter char (2), \
        kod1 char (3), \
        kod2 char (3), \
        kod3 char (3), \
        razdel char (1), \
        kc char (1), \
        type varchar (100), \
        name text, \
        centrum text, \
        nomdeskr text, \
        nomakt text, \
        status char (1), \
        dateutv date, \
        datevved date, \
        exist mediumint default 0, \
        numfound mediumint default 0, \
        found bit default 0, \
        html bit default 0, \
        checked bit default 0, \
	mergedcode varchar (11) default NULL, \
	index oktmo_ind (ter, kod1, kod2, kod3, razdel), \
	index merged_oktmo_ind (mergedcode)); \
load data local infile 'oktmo.csv' \
        into table oktmo \
        fields terminated by ';' \
        enclosed by '' \
        lines terminated by '\n' \
        (ter, kod1, kod2, kod3, kc, razdel, type, name, centrum, nomdeskr, nomakt, status, dateutv, datevved);" | \
mysql --user=$USER --password=$PASS --local-infile=1 $DB

cd -

TIME=$SECONDS
let "SEC= TIME % 60"
let "MIN= (TIME / 60) % 60"
let "HOUR= TIME / 3600"

echo -e "\n`date +%T` Импорт ОКТМО выполнен за $HOUR ч. $MIN мин. $SEC сек.\n"
