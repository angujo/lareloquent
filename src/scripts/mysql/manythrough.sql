select
    rc.TABLE_NAME REFERENCED_TABLE_NAME, kcu.COLUMN_NAME as REFERENCED_COLUMN_NAME,rc.REFERENCED_TABLE_NAME as THROUGH_TABLE_NAME,kcu.REFERENCED_COLUMN_NAME as THROUGH_COLUMN_NAME,
    r.COLUMN_NAME as THROUGH_REF_COLUMN_NAME,
    r.REFERENCED_TABLE_NAME as table_name, r.REFERENCED_COLUMN_NAME as COLUMN_NAME
from information_schema.REFERENTIAL_CONSTRAINTS rc
join information_schema.KEY_COLUMN_USAGE kcu  on kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME and kcu.TABLE_NAME = rc.TABLE_NAME and kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
join (
    select rc2.CONSTRAINT_SCHEMA,rc2.TABLE_NAME,rc2.REFERENCED_TABLE_NAME,kcu2.TABLE_SCHEMA,kcu2.REFERENCED_TABLE_SCHEMA ,kcu2.COLUMN_NAME,kcu2.REFERENCED_COLUMN_NAME from information_schema.REFERENTIAL_CONSTRAINTS rc2
    join information_schema.KEY_COLUMN_USAGE kcu2  on kcu2.CONSTRAINT_NAME = rc2.CONSTRAINT_NAME and kcu2.TABLE_NAME = rc2.TABLE_NAME and kcu2.TABLE_SCHEMA = rc2.CONSTRAINT_SCHEMA) r
    on rc.REFERENCED_TABLE_NAME =r.TABLE_NAME and rc.CONSTRAINT_SCHEMA =r.CONSTRAINT_SCHEMA and rc.TABLE_NAME <> rc.REFERENCED_TABLE_NAME and r.referenced_table_name = '{tbl}'
where rc.CONSTRAINT_SCHEMA ='{db}'