-- Additive migration: inventory table (extensible parts library) + partdemand column.
-- Safe to re-run (IF NOT EXISTS / INSERT IGNORE / additive column via separate ALTER).

CREATE TABLE IF NOT EXISTS inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_key  VARCHAR(64)  NOT NULL COLLATE utf8mb4_general_ci,
  part_name VARCHAR(150) NOT NULL,
  quantity  INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_part_key (part_key)
);

-- Migrate the 11 existing parts; CAST guards against blank/non-numeric VARCHAR counts.
INSERT INTO inventory (part_key, part_name, quantity)
  SELECT 'cardreader','Card Reader',CAST(cardreader AS UNSIGNED)               FROM stocklist WHERE id=1
  UNION ALL SELECT 'cassette','Currency Cassette',CAST(cassette AS UNSIGNED)             FROM stocklist WHERE id=1
  UNION ALL SELECT 'shuttercable','Shutter Cable',CAST(shuttercable AS UNSIGNED)         FROM stocklist WHERE id=1
  UNION ALL SELECT 'hooddoorlock','Hood Door Lock',CAST(hooddoorlock AS UNSIGNED)        FROM stocklist WHERE id=1
  UNION ALL SELECT 'softkeys','Softkeys',CAST(softkeys AS UNSIGNED)                      FROM stocklist WHERE id=1
  UNION ALL SELECT 'divertcassette','Divert Cassette',CAST(divertcassette AS UNSIGNED)  FROM stocklist WHERE id=1
  UNION ALL SELECT 'belts','High Belts',CAST(belts AS UNSIGNED)                          FROM stocklist WHERE id=1
  UNION ALL SELECT 'roller','Roller',CAST(roller AS UNSIGNED)                            FROM stocklist WHERE id=1
  UNION ALL SELECT 'cpufan','CPU Fan',CAST(cpufan AS UNSIGNED)                           FROM stocklist WHERE id=1
  UNION ALL SELECT 'clamp','Clamp',CAST(clamp AS UNSIGNED)                               FROM stocklist WHERE id=1
  UNION ALL SELECT 'ddu','DDU',CAST(ddu AS UNSIGNED)                                     FROM stocklist WHERE id=1
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

INSERT IGNORE INTO inventory (part_key, part_name, quantity) VALUES
  ('epp','EPP',0),('Stacker','Stacker',0),('k80printer','K80 Printer',0),
  ('printerhead','Printer Head',0),('rejectstopelement','Reject Stop Element',0),
  ('shuttermotor','Shutter Motor',0),('stackerunit4','Stacker Unit4',0),
  ('receiptprinter','Receipt Printer',0),('camslock','Cams Lock',0),
  ('doubleextractor','Double Extractor',0),('notepickingroller','Note Picking Roller',0),
  ('safelock','Safe Lock',0),('smallbelts','Small Belts',0),
  ('controllercard4x','4x Controller Card',0),('atmscreen','ATM Screen',0),
  ('motherboard','Motherboard',0),('fascia','Fascia',0),
  ('horizontaltransport','Horizontal Transport',0),('housingunit','Housing Unit',0),
  ('shutter','Shutter',0),('smps','Power Supply (SMPS)',0);
