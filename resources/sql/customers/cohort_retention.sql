/*
 * name: Customer Cohort Retention
 * description: Monthly retention by signup cohort
 * status: review
 * params: cohort_start, periods
 * roles: analyst, admin
 * cache_ttl: 3600
 * timeout: 60
 */

SELECT
    toYYYYMM(signup_date)                        AS cohort,
    dateDiff('month', signup_date, activity_date) AS period,
    count(DISTINCT customer_id)                   AS active_customers
FROM customer_activity
WHERE signup_date >= {cohort_start:Date}
  AND period       <= {periods:UInt8}
GROUP BY cohort, period
ORDER BY cohort ASC, period ASC
