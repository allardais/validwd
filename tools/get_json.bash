#! /bin/bash

source values.bash

if [ $# -lt "1" ] 
	then
		GET_DATE=`date --date='Monday' +%Y%m%d`
	else
		GET_DATE=$1
fi

FILENAME=$GET_DATE.json

cd $WORK_FILES_PATH

echo -e "\n`date +%T` Скачивание свежего дампа\n"

wget -c http://dumps.wikimedia.org/other/wikidata/$FILENAME.gz 

echo -e "\n`date +%T` Распаковка\n"

gunzip $FILENAME

mv $FILENAME base.json

echo `date --date="$GET_DATE" +%d.%m.%Y` > get_date

cd -
