-- ==========================================================
-- Migration: adds support for saving a parcel's drawn boundary
-- (used by the new "Add Parcel" self-service map picker)
--
-- HOW TO RUN THIS IN phpMyAdmin (WAMP):
-- 1. Start WAMP, open http://localhost/phpmyadmin
-- 2. Click on the "land_acquisition_db" database in the left sidebar
-- 3. Click the "SQL" tab at the top
-- 4. Paste everything below and click "Go"
--
-- This is optional: the Add Parcel page works without it (it just
-- won't store the drawn shape, only the point + area), and detects
-- automatically whether this column exists.
-- ==========================================================

ALTER TABLE parcels
ADD COLUMN boundary_geojson TEXT NULL AFTER longitude;
