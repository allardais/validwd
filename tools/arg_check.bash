if [ $# -lt "1" ]
  then
    echo -e $HELP_TEXT
    exit 1
fi

if [ $1 = "okato" ]
  then
    CLASSIF_NAME='ОКАТО'
  else
    if [ $1 = "oktmo" ]
      then
	CLASSIF_NAME='ОКТМО'
      else
	echo -e $HELP_TEXT
	exit 1
    fi
fi
