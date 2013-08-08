-- Registration times
--
-- VARIABLE: { 
--      name: "range", 
--      display: "Report Range",
--      type: "daterange", 
--      default: { start: "-1 year", end: "yesterday" }
-- }

SELECT 
	DATE_FORMAT(registraTion_date, '%Y-%m-%d') AS czas, 
	COUNT(*) AS liczba
FROM 
	blocked_accounts
WHERE 
	registration_date BETWEEN '{{ range.start }}' AND '{{ range.end }}'
GROUP BY 
	czas
ORDER BY 
	czas DESC;
