-- NAME: Product Prices 
-- COLUMNS: rpad2,,rpad2
-- CHART: {
--	"columns": ["buyPrice"],
--	"type": "ColumnChart",
--	"title": "Buy Price",
--	"xhistogram": true,
--	"buckets": 10
-- }
-- CHART: {
--	"columns": [1,3,4],
--	"type": "LineChart"
-- }

SELECT productCode, productName, buyPrice, MSRP
FROM products
UNION
SELECT 'AVERAGE','',AVG(buyPrice),AVG(MSRP)
FROM products
