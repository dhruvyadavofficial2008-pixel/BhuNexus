-- ==========================================================
-- FIX: run this if you already imported the old schema.sql
-- and can't log in with the demo accounts.
--
-- The original schema.sql shipped with a placeholder password
-- hash that does NOT actually match "password123". This script
-- replaces it with a real bcrypt hash of "password123" that
-- PHP's password_verify() will accept.
--
-- How to run:
--   1. Go to http://localhost/phpmyadmin
--   2. Click on the "land_acquisition_db" database
--   3. Click the "SQL" tab
--   4. Paste this whole file's contents and click "Go"
-- ==========================================================

USE land_acquisition_db;

UPDATE users
SET password = '$2b$10$R.TSSExM.2bvgnbXKftabuvGFSoqGZveyI4DZwuhuVWmAHHyRc3QK'
WHERE email IN ('admin@land.gov', 'ramesh@example.com', 'sita@example.com');

-- After running this, log in with:
--   admin@land.gov       / password123
--   ramesh@example.com   / password123
--   sita@example.com     / password123
