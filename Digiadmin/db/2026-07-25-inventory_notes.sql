-- Additive: per-part editable Note on both inventory lists.
ALTER TABLE inventory             ADD COLUMN IF NOT EXISTS note VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE inventory_refurbished ADD COLUMN IF NOT EXISTS note VARCHAR(255) NOT NULL DEFAULT '';
