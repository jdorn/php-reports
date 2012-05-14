-- Name: Customers by Number of Orders
-- Description: This lists all customers by the number of orders they have made.
-- Clicking on a customer's name will list all the orders for that particular customer.
-- 
-- Columns: rpad2,,rpad2
-- Variable: min_num, {
--		"type":"number",
--		"default":1,
--		"name":"Minimum Number"
--	}
-- Variable: max_num, {
--		"type":"number",
--		"empty":true,
--		"name":"Maximum Number",
--		"description":"(optional)"
--	}

SELECT c.customerNumber as `Customer Number`, customerName as `Customer Name`, COUNT(orderNumber) as `Number of Orders`
FROM customers c
LEFT JOIN orders o ON c.customerNumber = o.customerNumber
GROUP BY c.customerNumber
HAVING `Number of Orders` >= {{min_num}} 
{{#max_num}}
	AND `Number of Orders` < {{max_num}}
{{/max_num}}
ORDER BY `Number of Orders` DESC;
