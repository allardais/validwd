DELIMITER //

DROP PROCEDURE IF EXISTS merge_okato//

CREATE PROCEDURE merge_okato()
BEGIN
SELECT SQL_CALC_FOUND_ROWS 1 FROM okato LIMIT 0;
SET @num_codes= FOUND_ROWS();
SET @i=1;
WHILE @i <= @num_codes DO
  SELECT (@ter:=ter), (@kod1:=kod1), (@kod2:=kod2), (@kod3:=kod3) FROM okato WHERE id=@i;
  SET @merged_code = @ter;
  IF @kod1 <> '000'
  THEN
    SET @merged_code = CONCAT (@merged_code, @kod1);
  END IF;
  IF ((@kod2 <> '000') OR (@kod3 <> '000'))
  THEN
    SET @merged_code = CONCAT (@merged_code, @kod2);
  END IF;
  IF @kod3 <> '000'
  THEN
    SET @merged_code = CONCAT (@merged_code, @kod3);
  END IF;
  UPDATE okato SET mergedcode=@merged_code WHERE id=@i;
  SET @i=@i+1;
END WHILE;
END
//

CALL merge_okato()//

DROP PROCEDURE merge_okato//