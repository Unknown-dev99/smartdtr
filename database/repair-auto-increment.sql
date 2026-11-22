-- SMART-DTR: repair id columns that lost AUTO_INCREMENT during a phpMyAdmin import.
--
-- Symptom: registration (or any "add" form) fails with
--   SQLSTATE[HY000]: General error: 1364 Field 'id' doesn't have a default value
--
-- How to run (Hostinger hPanel -> Databases -> phpMyAdmin):
--   1. Select the SMART-DTR database on the left.
--   2. Open the SQL tab, paste this whole file, and click Go.
--   3. The last result lists every table that was repaired.
--
-- Safe to run more than once. It only touches integer `id` columns that are
-- missing AUTO_INCREMENT; it adds the PRIMARY KEY too when that is missing.
-- Existing rows and their ids are kept.

SET FOREIGN_KEY_CHECKS = 0;

DROP PROCEDURE IF EXISTS smartdtr_repair_auto_increment;

DELIMITER $$

CREATE PROCEDURE smartdtr_repair_auto_increment()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE tbl VARCHAR(64);
    DECLARE col_type VARCHAR(64);
    DECLARE has_pk INT;

    DECLARE cur CURSOR FOR
        SELECT c.TABLE_NAME,
               c.COLUMN_TYPE,
               (SELECT COUNT(*)
                  FROM information_schema.TABLE_CONSTRAINTS k
                 WHERE k.TABLE_SCHEMA = c.TABLE_SCHEMA
                   AND k.TABLE_NAME = c.TABLE_NAME
                   AND k.CONSTRAINT_TYPE = 'PRIMARY KEY')
          FROM information_schema.COLUMNS c
          JOIN information_schema.TABLES t
            ON t.TABLE_SCHEMA = c.TABLE_SCHEMA
           AND t.TABLE_NAME = c.TABLE_NAME
           AND t.TABLE_TYPE = 'BASE TABLE'
         WHERE c.TABLE_SCHEMA = DATABASE()
           AND c.COLUMN_NAME = 'id'
           AND c.DATA_TYPE IN ('tinyint', 'smallint', 'mediumint', 'int', 'bigint')
           AND c.EXTRA NOT LIKE '%auto_increment%';

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    DROP TEMPORARY TABLE IF EXISTS smartdtr_repaired;
    CREATE TEMPORARY TABLE smartdtr_repaired (table_name VARCHAR(64), added_primary_key VARCHAR(3));

    OPEN cur;
    repair_loop: LOOP
        FETCH cur INTO tbl, col_type, has_pk;
        IF done THEN
            LEAVE repair_loop;
        END IF;

        SET @smartdtr_sql = CONCAT(
            'ALTER TABLE `', tbl, '` MODIFY `id` ', col_type, ' NOT NULL AUTO_INCREMENT',
            IF(has_pk = 0, ', ADD PRIMARY KEY (`id`)', '')
        );
        PREPARE stmt FROM @smartdtr_sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

        INSERT INTO smartdtr_repaired VALUES (tbl, IF(has_pk = 0, 'yes', 'no'));
    END LOOP;
    CLOSE cur;

    SELECT * FROM smartdtr_repaired ORDER BY table_name;
    DROP TEMPORARY TABLE smartdtr_repaired;
END$$

DELIMITER ;

CALL smartdtr_repair_auto_increment();

DROP PROCEDURE IF EXISTS smartdtr_repair_auto_increment;

SET FOREIGN_KEY_CHECKS = 1;
