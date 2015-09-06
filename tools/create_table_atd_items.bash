#!/bin/bash

source values.bash

echo "drop table if exists atd_items; \
create table atd_items ( \
	id integer not null auto_increment primary key, \
	item varchar (20),\
	label text, \
	index atd_ind (item));" | \
mysql --user=$USER --password=$PASS $DB

echo

echo `date +%T`' Создана таблица atd_items'

echo
