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


List Vacuum-ready table as per current configurations.
Source: http://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/Appendix.PostgreSQL.CommonDBATasks.html

```sql
WITH vbt AS (SELECT setting AS autovacuum_vacuum_threshold FROM 
pg_settings WHERE name = 'autovacuum_vacuum_threshold')
    , vsf AS (SELECT setting AS autovacuum_vacuum_scale_factor FROM 
pg_settings WHERE name = 'autovacuum_vacuum_scale_factor')
    , fma AS (SELECT setting AS autovacuum_freeze_max_age FROM 
pg_settings WHERE name = 'autovacuum_freeze_max_age')
    , sto AS (select opt_oid, split_part(setting, '=', 1) as param, 
split_part(setting, '=', 2) as value from (select oid opt_oid, 
unnest(reloptions) setting from pg_class) opt)
SELECT
    '"'||ns.nspname||'"."'||c.relname||'"' as relation
    , pg_size_pretty(pg_table_size(c.oid)) as table_size
    , age(relfrozenxid) as xid_age
    , coalesce(cfma.value::float, autovacuum_freeze_max_age::float) 
autovacuum_freeze_max_age
    , (coalesce(cvbt.value::float, autovacuum_vacuum_threshold::float) 
+ coalesce(cvsf.value::float,autovacuum_vacuum_scale_factor::float) * 
pg_table_size(c.oid)) as autovacuum_vacuum_tuples
    , n_dead_tup as dead_tuples
FROM pg_class c join pg_namespace ns on ns.oid = c.relnamespace
join pg_stat_all_tables stat on stat.relid = c.oid
join vbt on (1=1) join vsf on (1=1) join fma on (1=1)
left join sto cvbt on cvbt.param = 'autovacuum_vacuum_threshold' and 
c.oid = cvbt.opt_oid
left join sto cvsf on cvsf.param = 'autovacuum_vacuum_scale_factor' and 
c.oid = cvsf.opt_oid
left join sto cfma on cfma.param = 'autovacuum_freeze_max_age' and 
c.oid = cfma.opt_oid
WHERE c.relkind = 'r' and nspname <> 'pg_catalog'
and (
    age(relfrozenxid) >= coalesce(cfma.value::float, 
autovacuum_freeze_max_age::float)
    or
    coalesce(cvbt.value::float, autovacuum_vacuum_threshold::float) + 
coalesce(cvsf.value::float,autovacuum_vacuum_scale_factor::float) * 
pg_table_size(c.oid) <= n_dead_tup
   -- or 1 = 1
)
ORDER BY age(relfrozenxid) DESC LIMIT 50;
```
