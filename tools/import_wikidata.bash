#!/bin/bash

source values.bash

cd $WORK_FILES_PATH

echo "drop table if exists wikidata; \
create table wikidata ( \
	id integer not null auto_increment primary key, \
	item varchar (20),\
	label text, \
	description text, \
	okato varchar (11) default null, \
	oktmo varchar (11) default null, \
	ate varchar (20), \
	centrum varchar (20), \
	country varchar (20), \
	lat decimal (11,8), \
	lon decimal (11,8), \
	phone text, \
	post text, \
	okatopassed bit default 0, \
	oktmopassed bit default 0, \
	index wiki_item (item), \
	index wiki_okato (okato), \
	index wiki_oktmo (oktmo), \
	index wiki_ate (ate), \
	index wiki_centrum (centrum)); \
load data local infile 'atd.csv' \
        into table wikidata \
        fields terminated by '\t' \
        enclosed by '' \
        lines terminated by '\n' \
        (item, label, description, okato, oktmo, ate, centrum, country, lat, lon, phone, post);" | \
mysql --user=$USER --password=$PASS --local-infile=1 $DB


echo "drop table if exists aliases; \
create table aliases ( \
	id integer not null auto_increment primary key, \
	item varchar (20),\
	alias text, \
	index alias_ind (item)); \
load data local infile 'aliases.csv' \
        into table aliases \
        fields terminated by ';' \
        enclosed by '' \
        lines terminated by '\n' \
        (item, alias);" | \
mysql --user=$USER --password=$PASS --local-infile=1 $DB

echo "drop table if exists types; \
create table types ( \
	id integer not null auto_increment primary key, \
	item varchar (20), \
	type varchar (20), \
	index types_ind (item)); \
load data local infile 'types.csv' \
        into table types \
        fields terminated by ';' \
        enclosed by '' \
        lines terminated by '\n' \
        (item, type);" | \
mysql --user=$USER --password=$PASS --local-infile=1 $DB

cp -f get_date data_date

cd -

TIME=$SECONDS
let "SEC= TIME % 60"
let "MIN= (TIME / 60) % 60"
let "HOUR= TIME / 3600"

echo -e "\n`date +%T` Импорт Викиданных выполнен за $HOUR ч. $MIN мин. $SEC сек.\n"
