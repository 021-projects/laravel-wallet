# Reliability

## Balance Calculation Methodology
The balance value is dynamically computed using data from the `transactions` table.
This computation is triggered whenever a transaction is either added or updated within the system. 
This approach ensures that the balance reflects the most current state of transactions, thereby mitigating the risk associated with direct modifications to the balance record itself.

The formula for calculating the balance is as follows: `funds_received - funds_sent`, where:

`funds_received` is calculated using the query:
```sql
SELECT SUM(transactions.received)
FROM transactions
WHERE transactions.status IN (<accounting-statuses>)
```

`funds_sent` is derived from:
```sql 
SELECT SUM(transactions.amount)
FROM transactions
WHERE transactions.status IN (<accounting-statuses>)
```
<br>

For detailed information on the specific `accounting-statuses` referenced, please consult the [configuration](configuration.md#accounting-transaction-statuses) documentation.

## Ensuring Balance Accuracy
To safeguard against data inconsistencies during concurrent transaction processing, the system employs database-level locking on the balance record. This lock remains until the balance has been recalculated, ensuring transactional integrity. Furthermore, the [`TransactionCreator`](./interfaces.md#transaction-creator) is designed to fetch the latest balance data immediately upon initiating a transaction. This guarantees that transaction processing is based on the most current balance information, thereby enhancing the reliability of the balance calculation process.

## Rollback Mechanism Enhancement
The system incorporates a sophisticated rollback mechanism designed to automatically revert any modifications made to the database, should an exception occur. This capability is indispensable, particularly during the execution of before and after callbacks as defined within the [`TransactionCreator`](./interfaces.md#transaction-creator). By utilizing this mechanism, the system ensures the database's integrity remains intact, safeguarding against data corruption or inconsistency resulting from unforeseen errors. This proactive approach to error handling enhances system reliability and trustworthiness, ensuring smooth and secure operations.
