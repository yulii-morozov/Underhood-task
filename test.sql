SELECT
p.date,
sum(p.quantity * tmp.price) AS total_price
FROM products p
INNER JOIN (
    SELECT pl.product_id, pl.`date` start_date,
           MIN(IFNULL(pl1.`date`, CURRENT_DATE())) end_date, pl.price
    FROM price_log pl
             LEFT JOIN price_log pl1 ON pl.product_id = pl1.product_id AND pl.`date` < pl1.`date`
    GROUP BY product_id, start_date
    ORDER BY pl.product_id
) tmp ON p.product_id = tmp.product_id
WHERE p.date >= tmp.start_date AND p.date < tmp.end_date AND p.date >= '2020-01-01' AND p.date <= '2020-01-10'
GROUP BY
p.date
ORDER BY
p.date;