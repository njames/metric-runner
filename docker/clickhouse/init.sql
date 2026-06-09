CREATE TABLE IF NOT EXISTS orders (
    id UInt64,
    customer_id UInt64,
    amount Decimal(18, 2),
    status String,
    created_at DateTime
) ENGINE = MergeTree()
ORDER BY (created_at, id);

INSERT INTO orders (id, customer_id, amount, status, created_at) VALUES
(1, 101, 50.00, 'completed', '2024-01-15 10:00:00'),
(2, 102, 120.50, 'completed', '2024-01-20 12:00:00'),
(3, 101, 75.00, 'completed', '2024-02-05 09:00:00'),
(4, 103, 200.00, 'pending', '2024-02-10 15:30:00'),
(5, 104, 45.25, 'completed', '2024-02-25 11:00:00');
