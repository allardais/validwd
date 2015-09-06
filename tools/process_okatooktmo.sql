DELIMITER //

DROP PROCEDURE IF EXISTS process_okatooktmo//

CREATE PROCEDURE process_okatooktmo()
BEGIN
SELECT SQL_CALC_FOUND_ROWS 1 FROM okatooktmo LIMIT 0;
SET @num_codes= FOUND_ROWS();
SET @i=1;
WHILE @i <= @num_codes DO
  SELECT (@okato:=okato), (@oktmo:=oktmo), (@okato_temp:=okatotemp), (@oktmo_temp:=oktmotemp) FROM okatooktmo WHERE id=@i;
  IF ( (@okato='') AND (@okato_temp<>'') )
    THEN
      UPDATE okatooktmo SET okato=@okato_temp WHERE id=@i;
  END IF;
  IF ( (@oktmo='') AND (@oktmo_temp<>'') )
    THEN
      UPDATE okatooktmo SET oktmo=@oktmo_temp WHERE id=@i;
  END IF;
  SET @i=@i+1;
END WHILE;
UPDATE okatooktmo SET okato=NULL WHERE okato='';
UPDATE okatooktmo SET oktmo=NULL WHERE oktmo='';
END
//

CALL process_okatooktmo()//

DROP PROCEDURE process_okatooktmo//