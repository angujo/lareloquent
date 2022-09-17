select  r.REFERENCED_TABLE_NAME, r.REFERENCED_COLUMN_NAME, c.TABLE_NAME, c.COLUMN_NAME, COLUMN_COMMENT, ORDINAL_POSITION, COLUMN_DEFAULT
     , case IS_NULLABLE when 'YES' then true else false end as is_nullable, DATA_TYPE
     , if(1=regexp_like(COLUMN_KEY,'(^|[:space:])PRI([:space:]|$)') ,true,false) is_PRIMARY
     , if(1=regexp_like(COLUMN_KEY,'(^|[:space:])UNI([:space:]|$)') ,true,false) is_UNIQUE
     , if(1=regexp_like(EXTRA,'(^|[:space:])auto_increment([:space:]|$)') ,true,false) INCREMENTS
     , if(1=regexp_like(EXTRA,'[:space:]+on([:space:]+)update([:space:]+)','i') ,true,false) IS_UPDATING
     , IFNULL(CHARACTER_MAXIMUM_LENGTH,NUMERIC_PRECISION) AS CHARACTER_MAXIMUM_LENGTH
     , COLUMN_TYPE, NUMERIC_SCALE
from information_schema.`COLUMNS` c
         left join (select
                        rc.CONSTRAINT_SCHEMA, rc.TABLE_NAME, rc.REFERENCED_TABLE_NAME, kcu.COLUMN_NAME, kcu.REFERENCED_COLUMN_NAME
                    from information_schema.REFERENTIAL_CONSTRAINTS rc
                             join information_schema.KEY_COLUMN_USAGE kcu on kcu.TABLE_SCHEMA =rc.CONSTRAINT_SCHEMA and kcu.CONSTRAINT_NAME =rc.CONSTRAINT_NAME ) r
                   on r.CONSTRAINT_SCHEMA = c.TABLE_SCHEMA and r.TABLE_NAME=c.TABLE_NAME  and r.COLUMN_NAME =c.COLUMN_NAME
where c.TABLE_SCHEMA = '{db}' and c.TABLE_NAME = '{tbl}'
order by c.TABLE_NAME, c.ORDINAL_POSITION