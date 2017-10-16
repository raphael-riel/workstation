## Database size

```sql
SELECT
    table_name,
    pg_size_pretty(table_size) AS table_size,
    pg_size_pretty(indexes_size) AS indexes_size,
    pg_size_pretty(total_size) AS total_size
FROM (
    SELECT
        table_name,
        pg_table_size(table_name) AS table_size,
        pg_indexes_size(table_name) AS indexes_size,
        pg_total_relation_size(table_name) AS total_size
    FROM (
        SELECT ('"' || table_schema || '"."' || table_name || '"') AS table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
    ) AS all_tables
    ORDER BY table_name ASC
) AS pretty_sizes;





SELECT
    table_name,
    pg_size_pretty(table_size) AS table_size,
    pg_size_pretty(indexes_size) AS indexes_size,
    pg_size_pretty(total_size) AS total_size
FROM (
    SELECT
        table_name,
        pg_table_size(table_name) AS table_size,
        pg_indexes_size(table_name) AS indexes_size,
        pg_total_relation_size(table_name) AS total_size
    FROM (
        SELECT ('"' || table_schema || '"."' || table_name || '"') AS table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
    ) AS all_tables
    ORDER BY total_size DESC
) AS pretty_sizes LIMIT 10;


### SUM


SELECT
    pg_size_pretty(SUM(total_size)) AS total_size
FROM (
    SELECT
        table_name,
        pg_total_relation_size(table_name) AS total_size
    FROM (
        SELECT ('"' || table_schema || '"."' || table_name || '"') AS table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
    ) AS all_tables
) AS total;
```


## Auto Vacuum status

Config: 

```sql
SELECT name, setting, context FROM pg_settings WHERE category ~ 'Autovacuum';
```

Last run:

```sql
SELECT last_autovacuum FROM "pg_catalog"."pg_stat_all_tables" WHERE last_autovacuum IS NOT NULL AND schemaname = 'public' ORDER BY last_autovacuum DESC LIMIT 1;
```

```sql
select * from "pg_catalog"."pg_stat_all_tables" ORDER BY autovacuum_count DESC;


SELECT schemaname, relname, last_autovacuum, last_autoanalyze, autovacuum_count, autoanalyze_count 
FROM "pg_catalog"."pg_stat_all_tables" 
WHERE (last_autovacuum IS NOT NULL OR last_autoanalyze IS NOT NULL) 
  AND schemaname = 'public' 
ORDER BY last_autovacuum DESC, last_autoanalyze DESC;
```
