-- NAME: Customers by Number of Orders
-- DESCRIPTION: This lists all customers by the number of orders they have made.
-- Clicking on a customer's name will list all the orders for that particular customer.
-- 
-- COLUMNS: rpad2,,rpad2
-- VARIABLE: {
--		"default":1,
--		"name": "min_num",
--		"display":"Minimum Number"
--	}
-- VARIABLE: {
--		"empty":true,
--		"display":"Maximum Number",
--		"name": "max_num",
--		"description":"(optional)"
--	}

SELECT c.customerNumber as `Customer Number`, customerName as `Customer Name`, COUNT(orderNumber) as `Number of Orders`
FROM customers c
LEFT JOIN orders o ON c.customerNumber = o.customerNumber
GROUP BY c.customerNumber
HAVING `Number of Orders` >= {min_num} 
{% if max_num %}
	AND `Number of Orders` < {{max_num}}
{% endif %}
ORDER BY `Number of Orders` DESC;
