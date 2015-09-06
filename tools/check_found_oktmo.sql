DELIMITER //

DROP PROCEDURE IF EXISTS check_found_oktmo//

CREATE PROCEDURE check_found_oktmo()
BEGIN
SELECT SQL_CALC_FOUND_ROWS 1 FROM oktmo LIMIT 0;
SET @num_codes= FOUND_ROWS();
SET @i=1;
WHILE @i <= @num_codes DO
  SELECT @merged_code:=mergedcode, @checked:=checked FROM oktmo WHERE id=@i;
  IF EXISTS (SELECT 1 FROM wikidata WHERE oktmo=@merged_code)
    THEN
      UPDATE LOW_PRIORITY oktmo SET found=1 WHERE id=@i;
      UPDATE LOW_PRIORITY wikidata SET oktmopassed=1 WHERE oktmo=@merged_code;
  END IF;
  SET @i=@i+1;
END WHILE;
END
//

CALL check_found_oktmo()//

DROP PROCEDURE check_found_oktmo//