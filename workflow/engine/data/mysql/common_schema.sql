SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `common_schema`
--
CREATE DATABASE IF NOT EXISTS `common_schema` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `common_schema`;

DELIMITER $$
--
-- Functions
--
DROP FUNCTION IF EXISTS `unserialize_column`$$
CREATE DEFINER=`root`@`%` FUNCTION `unserialize_column` (`_input_string` LONGTEXT, `_key` MEDIUMTEXT) RETURNS LONGTEXT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci BEGIN
	
		DECLARE __output_part,__output,__extra_byte_counter,__extra_byte_number,__value_type,__array_part_temp LONGTEXT;
	DECLARE __value_length,__char_ord,__start,__char_counter,__non_multibyte_length,__array_close_bracket_counter,__array_open_bracket_counter INT SIGNED;
	SET __output := NULL;
	
		IF LOCATE(CONCAT('s:',LENGTH(_key),':"',_key,'";'), _input_string) != 0 THEN
	
				SET __output_part := SUBSTRING_INDEX(_input_string,CONCAT('s:',LENGTH(_key),':"',_key,'";'),-1);
		
				SET __value_type := SUBSTRING(SUBSTRING(__output_part, 1, CHAR_LENGTH(SUBSTRING_INDEX(__output_part,';',1))), 1, 1);
		
				CASE 	
		WHEN __value_type = 'a' THEN
						SET __array_open_bracket_counter := 1;
			SET __array_close_bracket_counter := 0;
						SET __array_part_temp := SUBSTRING(__output_part FROM LOCATE('{',__output_part)+1);
			
						WHILE (__array_open_bracket_counter > 0 OR LENGTH(__array_part_temp) = 0) DO
								IF LOCATE('{',__array_part_temp) > 0 AND (LOCATE('{',__array_part_temp) < LOCATE('}',__array_part_temp)) THEN
										SET __array_open_bracket_counter := __array_open_bracket_counter + 1;
					SET __array_part_temp := SUBSTRING(__array_part_temp FROM LOCATE('{',__array_part_temp) + 1);					
				ELSE
										SET __array_open_bracket_counter := __array_open_bracket_counter - 1;
					SET __array_close_bracket_counter := __array_close_bracket_counter + 1;
					SET __array_part_temp := SUBSTRING(__array_part_temp FROM LOCATE('}',__array_part_temp) + 1);					
				END IF;
			END WHILE;
						SET __output := CONCAT(SUBSTRING_INDEX(__output_part,'}',__array_close_bracket_counter),'}');
			
		WHEN __value_type = 'd' OR __value_type = 'i' OR __value_type = 'b' THEN
			
						SET __output := SUBSTRING_INDEX(SUBSTRING_INDEX(__output_part,';',1),':',-1);
			
		WHEN __value_type = 'O' THEN			
			
						SET __output := CONCAT(SUBSTRING_INDEX(__output_part,';}',1),';}');
			
		WHEN __value_type = 'N' THEN 
                        SET __output := NULL;		
		ELSE
			
						SET __value_length := SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(__output_part, ':', 2),':',-1),';',1);
			
												SET __output_part := SUBSTRING(__output_part, 5+LENGTH(__value_length));
			
			SET __char_counter := 1;
			
						SET __non_multibyte_length := 0;
			
			SET __start := 0;
						WHILE __start < __value_length DO
			
				SET __char_ord := ORD(SUBSTR(__output_part,__char_counter,1));
				
				SET __extra_byte_number := 0;
				SET __extra_byte_counter := FLOOR(__char_ord / 256);
				
												WHILE __extra_byte_counter > 0 DO
					SET __extra_byte_counter := FLOOR(__extra_byte_counter / 256);
					SET __extra_byte_number := __extra_byte_number+1;
				END WHILE;
				
								SET __start := __start + 1 + __extra_byte_number;			
				SET __char_counter := __char_counter + 1;
				SET __non_multibyte_length := __non_multibyte_length +1;
								
			END WHILE;
			
			SET __output :=  SUBSTRING(__output_part,1,__non_multibyte_length);
					
		END CASE;		
	END IF;
	RETURN __output;
	END$$

DELIMITER ;

COMMIT;
