DELIMITER //

DROP PROCEDURE IF EXISTS check_found_okato//

CREATE PROCEDURE check_found_okato()
BEGIN
SELECT SQL_CALC_FOUND_ROWS 1 FROM okato LIMIT 0;
SET @num_codes= FOUND_ROWS();
SET @i=1;
WHILE @i <= @num_codes DO
  SELECT @merged_code:=mergedcode, @checked:=checked FROM okato WHERE id=@i;
    IF EXISTS (SELECT 1 FROM wikidata WHERE okato=@merged_code)
      THEN
	UPDATE LOW_PRIORITY okato SET found=1 WHERE id=@i;
	UPDATE LOW_PRIORITY wikidata SET okatopassed=1 WHERE okato=@merged_code;
    END IF;
  SET @i=@i+1;
END WHILE;
END
//

CALL check_found_okato()//

DROP PROCEDURE check_found_okato//