DELIMITER //

DROP PROCEDURE IF EXISTS deep_okato//

CREATE PROCEDURE deep_okato()
BEGIN
SELECT SQL_CALC_FOUND_ROWS 1 FROM okato LIMIT 0;
SET @num_codes= FOUND_ROWS();
SET @i=1;
WHILE @i <= @num_codes DO
  SELECT (@ter:=ter), (@kod1:=kod1), (@kod2:=kod2), (@kod3:=kod3), (@razdel:=razdel) FROM okato WHERE id=@i;
  IF ( (@ter<>'00') AND (@razdel<>'2') )
    THEN
      IF @kod1='000'
	THEN
	  SELECT SQL_CALC_FOUND_ROWS 1 FROM okato WHERE (ter=@ter AND (kod1<>'000' OR kod2<>'000' OR kod3<>'000')) LIMIT 0;
	ELSE IF @kod2='000'
	      THEN
		SELECT SQL_CALC_FOUND_ROWS 1 FROM okato WHERE (ter=@ter AND kod1=@kod1 AND (kod2<>'000' OR kod3<>'000')) LIMIT 0;
	      ELSE
		SELECT SQL_CALC_FOUND_ROWS 1 FROM okato WHERE (ter=@ter AND kod1=@kod1 AND kod2=@kod2 AND kod3<>'000') LIMIT 0;
	      END IF;
      END IF;
      SET @num_rows= FOUND_ROWS();
      UPDATE okato SET exist=@num_rows WHERE id=@i;
  END IF;
  SET @i=@i+1;
END WHILE;
END
//

CALL deep_okato()//

DROP PROCEDURE deep_okato//