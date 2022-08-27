select rc.REFERENCED_TABLE_NAME   AS TABLE_NAME,
       kcu.REFERENCED_COLUMN_NAME AS COLUMN_NAME,
       kcu.TABLE_NAME             AS REFERENCED_TABLE_NAME,
       kcu.COLUMN_NAME            AS REFERENCED_COLUMN_NAME
from information_schema.REFERENTIAL_CONSTRAINTS rc
         join information_schema.KEY_COLUMN_USAGE kcu
              on kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME and kcu.TABLE_NAME = rc.TABLE_NAME and kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
where not exists
    (select 1
     from information_schema.KEY_COLUMN_USAGE cu
              join information_schema.KEY_COLUMN_USAGE ku
                   on cu.CONSTRAINT_NAME = ku.CONSTRAINT_NAME and cu.TABLE_NAME = ku.TABLE_NAME and ku.TABLE_SCHEMA = cu.TABLE_SCHEMA
              join information_schema.TABLE_CONSTRAINTS tc
                   on tc.TABLE_SCHEMA = ku.TABLE_SCHEMA and tc.TABLE_NAME = ku.TABLE_NAME and tc.CONSTRAINT_NAME = ku.CONSTRAINT_NAME and
                      tc.CONSTRAINT_TYPE = 'UNIQUE'
     where kcu.COLUMN_NAME = cu.COLUMN_NAME
       and kcu.TABLE_NAME = cu.TABLE_NAME
       and kcu.TABLE_SCHEMA = cu.CONSTRAINT_SCHEMA
     group by ku.CONSTRAINT_NAME, ku.TABLE_NAME, ku.TABLE_SCHEMA
     having count(1) < 2)
  and rc.REFERENCED_TABLE_NAME = '{tbl}'
  and kcu.TABLE_SCHEMA = '{db}'
