-- Sales
-- This report lets a non sales rep, look at a specific sales reps orders within a specified time period,
-- however a sales rep will only be able to see their own orders.
-- You can click on a customer name to drill down into all orders for that customer.
-- VARIABLE: { 
--      name: "range", 
--      display: "Report Range",
--      type: "daterange", 
--      default: { start: "yesterday", end: "yesterday" }
-- }
-- DYNAMIC: {
--      file: "sales_rep_header.php"
-- }
-- FILTER: {
--      column: "Customer Name", 
--      filter: "drilldown",
--      params: {
--          macros: { "id": { column: "Customer Id" } },
--          report: "drilldown/customer-orders.sql"
--      }
-- }

SELECT
    order_id as `Order Id`,
    created_at as `Order Date`,
    CONCAT(customer_fname, " ", customer_lname) as `Customer Name`,
    customer_id as `Customer Id`,
    grand_total as `Grand Total`,
    status as `Order Status`
FROM
    orders
WHERE
    created_at BETWEEN "{{ range.start }}" AND "{{ range.end }}"
    AND
    (CASE
      WHEN 'ALL' = '{{ sales_rep_id }}'
      THEN 1 = 1
      ELSE sales_rep_id = '{{ sales_rep_id }}'
    END)
