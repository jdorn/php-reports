-- All Orders
-- This report lets you view all orders within a specified time period.
-- You can click on a customer name to drill down into all orders for that customer.
-- VARIABLE: { 
--      name: "range", 
--      display: "Report Range",
--      type: "daterange", 
--      default: { start: "yesterday", end: "yesterday" }
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
