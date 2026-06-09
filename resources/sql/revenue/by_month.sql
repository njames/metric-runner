/*
 * name: Revenue by Month
 * description: Total order revenue grouped by calendar month
 * status: approved
 * params: date_from, date_to
 * roles: analyst, admin
 * cache_ttl: 600
 */

SELECT
    toYYYYMM(created_at)        AS month,
    sum(amount)                  AS revenue,
    count()                      AS order_count,
    avg(amount)                  AS avg_order_value
FROM orders
WHERE created_at >= {date_from:Date}
  AND created_at <  {date_to:Date}
GROUP BY month
ORDER BY month ASC
