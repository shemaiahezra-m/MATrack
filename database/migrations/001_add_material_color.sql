-- Run once on the existing MATrack PostgreSQL database.
ALTER TABLE materials
ADD COLUMN IF NOT EXISTS color VARCHAR(30);
