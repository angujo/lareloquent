select
    kcu.REFERENCED_TABLE_NAME AS TABLE_NAME,
    kcu.REFERENCED_COLUMN_NAME AS COLUMN_NAME,
    kcu.TABLE_NAME         AS THROUGH_TABLE_NAME,
    kcu.COLUMN_NAME        AS THROUGH_COLUMN_NAME,
    ft.REFERENCED_COLUMN_NAME  THROUGH_REF_COLUMN_NAME,
    ft.TABLE_NAME          AS REFERENCED_TABLE_NAME,
    ft.COLUMN_NAME         AS REFERENCED_COLUMN_NAME
from information_schema.REFERENTIAL_CONSTRAINTS rc
         join information_schema.KEY_COLUMN_USAGE kcu on kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME and kcu.TABLE_NAME = rc.TABLE_NAME and kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
         join
     (select
          kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME, kcu.TABLE_NAME, kcu.COLUMN_NAME, rc.CONSTRAINT_SCHEMA
      from information_schema.REFERENTIAL_CONSTRAINTS rc
               join information_schema.KEY_COLUMN_USAGE kcu on kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME and kcu.TABLE_NAME = rc.TABLE_NAME and kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
               join information_schema.TABLE_CONSTRAINTS tc on tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME and tc.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA and tc.TABLE_NAME = kcu.TABLE_NAME and tc.CONSTRAINT_TYPE IN ('UNIQUE','PRIMARY KEY')
     )	ft on ft.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA and ft.REFERENCED_TABLE_NAME=kcu.TABLE_NAME
where kcu.TABLE_SCHEMA='{db}' and kcu.REFERENCED_TABLE_NAME='{tbl}'