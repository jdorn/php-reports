-- NAME: Product Prices 
-- COLUMNS: rpad2,,rpad2
-- CHART: {
--	"x": "buyPrice",
--	"type": "ColumnChart",
--	"buckets": 10,
--	"title": "Buy Price"
-- }
-- CHART: {
--	"x": 1,
--	"y": [3,4],
--	"type": "LineChart"
-- }

SELECT productCode, productName, buyPrice, MSRP
FROM products
UNION
SELECT 'AVERAGE','',AVG(buyPrice),AVG(MSRP)
FROM products
