#!/bin/bash

source values.bash

cd $WORK_FILES_PATH

echo "drop table if exists okato; \
create table okato ( \
        id integer not null auto_increment primary key,  \
        ter char (2), \
        kod1 char (3), \
        kod2 char (3), \
        kod3 char (3), \
        razdel char (1), \
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
	index okato_ind (ter, kod1, kod2, kod3, razdel), \
	index merged_okato_ind (mergedcode)); \
load data local infile 'okato.csv' \
        into table okato \
        fields terminated by ';' \
        enclosed by '' \
        lines terminated by '\n' \
        ignore 1 lines \
        (ter, kod1, kod2, kod3, razdel, type, name, centrum, nomdeskr, nomakt, status, dateutv, datevved);" | \
mysql --user=$USER --password=$PASS --local-infile=1 $DB

cd -

TIME=$SECONDS
let "SEC= TIME % 60"
let "MIN= (TIME / 60) % 60"
let "HOUR= TIME / 3600"

echo -e "\n`date +%T` Импорт ОКАТО выполнен за $HOUR ч. $MIN мин. $SEC сек.\n"
