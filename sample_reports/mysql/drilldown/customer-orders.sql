-- All Orders For a Customer
-- This is a drilldown report that lists all orders for a given customer
-- VARIABLE: { name: "id", display: "Customer Id" }

SELECT
    order_id as `Order Id`,
    created_at as `Order Date`,
    grand_total as `Grand Total`,
    status as `Order Status`
FROM
    orders
WHERE
    customer_id = "{{ id }}"
