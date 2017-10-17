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

## Autovacuum simulation

List tables elegible for AutoVacuum according to current settings, or simulated ones.

```sql
WITH 
     -- vbt AS (SELECT setting AS autovacuum_vacuum_threshold FROM pg_settings WHERE name = 'autovacuum_vacuum_threshold'),
     vbt AS (SELECT 50 AS autovacuum_vacuum_threshold),
     -- vsf AS (SELECT setting AS autovacuum_vacuum_scale_factor FROM pg_settings WHERE name = 'autovacuum_vacuum_scale_factor'),
     vsf AS (SELECT 0.01 AS autovacuum_vacuum_scale_factor),
     -- fma AS (SELECT setting AS autovacuum_freeze_max_age FROM pg_settings WHERE name = 'autovacuum_freeze_max_age'),
     fma AS (SELECT 200000000 AS autovacuum_freeze_max_age),
     sto AS (
        SELECT opt_oid, 
           split_part(setting, '=', 1) AS param, 
           split_part(setting, '=', 2) AS value FROM (
                SELECT oid opt_oid, 
                unnest(reloptions) setting FROM pg_class
            ) opt
        )

SELECT
    '"'||ns.nspname||'"."'||c.relname||'"' AS relation,
    pg_size_pretty(pg_table_size(c.oid)) AS table_size,
    age(relfrozenxid) AS xid_age,
    coalesce(cfma.value::float, autovacuum_freeze_max_age::float) autovacuum_freeze_max_age,
    pg_stat_get_live_tuples(c.oid) AS n_live_tup,
    pg_stat_get_dead_tuples(c.oid) AS n_dead_tup,
    (n_live_tup + n_dead_tup) AS tuple_total,
    (coalesce(cvbt.value::float, autovacuum_vacuum_threshold::float) + coalesce(cvsf.value::float,autovacuum_vacuum_scale_factor::float) * (n_live_tup + n_dead_tup)) AS autovacuum_vacuum_tuples
FROM pg_class c JOIN pg_namespace ns ON ns.oid = c.relnamespace
JOIN pg_stat_all_tables stat ON stat.relid = c.oid
JOIN vbt ON (1=1) JOIN vsf ON (1=1) join fma ON (1=1)
LEFT JOIN sto cvbt ON cvbt.param = 'autovacuum_vacuum_threshold' AND c.oid = cvbt.opt_oid
LEFT JOIN sto cvsf ON cvsf.param = 'autovacuum_vacuum_scale_factor' AND c.oid = cvsf.opt_oid
LEFT JOIN sto cfma ON cfma.param = 'autovacuum_freeze_max_age' AND c.oid = cfma.opt_oid
WHERE c.relkind = 'r' and nspname <> 'pg_catalog'
AND (
    age(relfrozenxid) >= coalesce(cfma.value::float, autovacuum_freeze_max_age::float)
    OR
    coalesce(cvbt.value::float, autovacuum_vacuum_threshold::float) + coalesce(cvsf.value::float,autovacuum_vacuum_scale_factor::float) * (n_live_tup + n_dead_tup) <= n_dead_tup
)
ORDER BY n_dead_tup DESC, age(relfrozenxid) DESC LIMIT 50;
```
