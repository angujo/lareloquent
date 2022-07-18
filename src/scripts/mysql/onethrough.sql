select kcu.TABLE_NAME             THROUGH_TABLE_NAME,
       kcu.COLUMN_NAME            THROUGH_COLUMN_NAME,
       r.COLUMN_NAME              THROUGH_REF_COLUMN_NAME,
       kcu.REFERENCED_TABLE_NAME AS TABLE_NAME,
       kcu.REFERENCED_COLUMN_NAME AS COLUMN_NAME,
       r.REFERENCED_TABLE_NAME,
       r.REFERENCED_COLUMN_NAME
from information_schema.REFERENTIAL_CONSTRAINTS rc
         join information_schema.KEY_COLUMN_USAGE kcu
              on kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME and kcu.TABLE_NAME = rc.TABLE_NAME and kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
         join
     (select kcu.TABLE_NAME, kcu.COLUMN_NAME, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME, rc.CONSTRAINT_SCHEMA
      from information_schema.REFERENTIAL_CONSTRAINTS rc
               join information_schema.KEY_COLUMN_USAGE kcu
                    on kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME and kcu.TABLE_NAME = rc.TABLE_NAME and
                       kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA) r
     on r.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA and rc.TABLE_NAME = r.TABLE_NAME and r.COLUMN_NAME <> kcu.COLUMN_NAME
where rc.TABLE_NAME = 'payment'
  and rc.CONSTRAINT_SCHEMA = '{db}';