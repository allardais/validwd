DELIMITER //

DROP PROCEDURE IF EXISTS merge_oktmo//

CREATE PROCEDURE merge_oktmo()
BEGIN
SELECT SQL_CALC_FOUND_ROWS 1 FROM oktmo LIMIT 0;
SET @num_codes= FOUND_ROWS();
SET @i=1;
WHILE @i <= @num_codes DO
  SELECT (@ter:=ter), (@kod1:=kod1), (@kod2:=kod2), (@kod3:=kod3) FROM oktmo WHERE id=@i;
  SET @merged_code = CONCAT (@ter, @kod1, @kod2);
  IF @kod3 <> '000'
  THEN
    SET @merged_code = CONCAT (@merged_code, @kod3);
  END IF;
  UPDATE oktmo SET mergedcode=@merged_code WHERE id=@i;
  SET @i=@i+1;
END WHILE;
END
//

CALL merge_oktmo()//

DROP PROCEDURE merge_oktmo//