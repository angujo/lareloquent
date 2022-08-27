select
    rc.REFERENCED_TABLE_NAME AS TABLE_NAME,a.REFERENCED_COLUMN_NAME as COLUMN_NAME,
    rc.TABLE_NAME through_table_name, a.column_name as through_column_name, t.column_name as through_ref_column_name,
    t.REFERENCED_TABLE_NAME, t.REFERENCED_COLUMN_NAME,
    (select group_concat(c.COLUMN_NAME separator ',') from information_schema.`COLUMNS` c
     where c.TABLE_SCHEMA=rc.CONSTRAINT_SCHEMA and c.TABLE_NAME = rc.TABLE_NAME and c.COLUMN_NAME not in (t.column_name,a.column_name)) other_columns
from information_schema.REFERENTIAL_CONSTRAINTS rc
         join (
    select r.constraint_name, r.constraint_schema, r.table_name, r.referenced_table_name,
           kcu.REFERENCED_TABLE_NAME ref_table_name, kcu.REFERENCED_COLUMN_NAME, kcu.COLUMN_NAME, kcu.TABLE_NAME k_table_name
    from information_schema.REFERENTIAL_CONSTRAINTS r
             join information_schema.KEY_COLUMN_USAGE kcu
                  on kcu.CONSTRAINT_NAME = r.CONSTRAINT_NAME and kcu.TABLE_NAME = r.TABLE_NAME and kcu.TABLE_SCHEMA = r.CONSTRAINT_SCHEMA
) t on t.constraint_schema=rc.CONSTRAINT_SCHEMA and t.table_name=rc.TABLE_NAME and t.referenced_table_name<>rc.REFERENCED_TABLE_NAME
         join (
    select r.constraint_name, r.constraint_schema, r.table_name, r.referenced_table_name,
           kcu.REFERENCED_TABLE_NAME ref_table_name, kcu.REFERENCED_COLUMN_NAME, kcu.COLUMN_NAME, kcu.TABLE_NAME k_table_name
    from information_schema.REFERENTIAL_CONSTRAINTS r
             join information_schema.KEY_COLUMN_USAGE kcu
                  on kcu.CONSTRAINT_NAME = r.CONSTRAINT_NAME and kcu.TABLE_NAME = r.TABLE_NAME and kcu.TABLE_SCHEMA = r.CONSTRAINT_SCHEMA
) a on a.constraint_schema=rc.CONSTRAINT_SCHEMA and a.table_name=rc.TABLE_NAME  and a.referenced_table_name=rc.REFERENCED_TABLE_NAME
where rc.CONSTRAINT_SCHEMA ='{db}' and rc.TABLE_NAME in ({pivots}) and rc.REFERENCED_TABLE_NAME ='{tbl}'