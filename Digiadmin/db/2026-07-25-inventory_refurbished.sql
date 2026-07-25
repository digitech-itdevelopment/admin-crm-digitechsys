-- Additive: separate "Refurbished" parts list, seeded from the current (New) inventory at qty 0.
CREATE TABLE IF NOT EXISTS inventory_refurbished (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_key  VARCHAR(64)  NOT NULL COLLATE utf8mb4_general_ci,
  part_name VARCHAR(150) NOT NULL,
  quantity  INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_part_key (part_key)
);

INSERT IGNORE INTO inventory_refurbished (part_key, part_name, quantity)
  SELECT part_key, part_name, 0 FROM inventory;
