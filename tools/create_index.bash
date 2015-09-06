#!/bin/bash

source values.bash

echo -e "\n`date +%T` Перестроение индексов\n"

echo "drop index okato_ind on okato; \
drop index oktmo_ind on oktmo; \
drop index merged_okato_ind on okato; \
drop index merged_oktmo_ind on oktmo; \
drop index ok_okato on okatooktmo; \
drop index ok_oktmo on okatooktmo; \
drop index wiki_item on wikidata; \
drop index wiki_okato on wikidata; \
drop index wiki_oktmo on wikidata; \
drop index wiki_ate on wikidata; \
drop index wiki_centrum on wikidata; \
drop index types_ind on types; \
drop index atd_ind on atd_items;" | \
mysql --user=$USER --password=$PASS $DB

echo "create index okato_ind on okato (ter, kod1, kod2, kod3, razdel); \
create index merged_okato_ind on okato (mergedcode); \
create index oktmo_ind on oktmo (ter, kod1, kod2, kod3, razdel); \
create index merged_oktmo_ind on oktmo (mergedcode); \
create index ok_okato on okatooktmo (okato); \
create index ok_oktmo on okatooktmo (oktmo); \
create index wiki_item on wikidata (item); \
create index wiki_okato on wikidata (okato); \
create index wiki_oktmo on wikidata (oktmo); \
create index wiki_ate on wikidata (ate); \
create index wiki_centrum on wikidata (centrum); \
create index types_ind on types (item); \
create index atd_ind on atd_items (item);" | \
mysql --user=$USER --password=$PASS $DB

TIME=$SECONDS
let "SEC= TIME % 60"
let "MIN= (TIME / 60) % 60"
let "HOUR= TIME / 3600"

echo -e "\n`date +%T` Индексы перестроены за $HOUR ч. $MIN мин. $SEC сек.\n"

exit 0
