-- 1) Convert legacy 'pdf' values to 'excel' and normalize empty/nulls
UPDATE reports SET format = 'excel' WHERE LOWER(TRIM(format)) = 'pdf';
UPDATE reports SET format = 'excel' WHERE format IS NULL OR TRIM(format) = '';

-- 2) Optionally verify results:
SELECT format, COUNT(*) AS cnt FROM reports GROUP BY format;

-- 3) Alter column to allowed values (run only after step 1 succeeds)
ALTER TABLE reports MODIFY COLUMN `format` ENUM('excel','csv') NOT NULL DEFAULT 'excel';
