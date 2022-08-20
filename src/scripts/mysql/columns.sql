select TABLE_NAME, COLUMN_NAME, COLUMN_COMMENT, ORDINAL_POSITION, COLUMN_DEFAULT, case IS_NULLABLE when 'YES' then true else false end as is_nullable, DATA_TYPE
     , if(1=regexp_like(COLUMN_KEY,'(^|[:space:])PRI([:space:]|$)') ,true,false) is_PRIMARY
     , if(1=regexp_like(COLUMN_KEY,'(^|[:space:])UNI([:space:]|$)') ,true,false) is_UNIQUE, if(1=regexp_like(EXTRA,'(^|[:space:])auto_increment([:space:]|$)') ,true,false) INCREMENTS
     , if(1=regexp_like(EXTRA,'[:space:]+on([:space:]+)update([:space:]+)','i') ,true,false) IS_UPDATING
from information_schema.`COLUMNS` c
where c.TABLE_SCHEMA = '{db}' and c.TABLE_NAME = '{tbl}'
order by c.TABLE_NAME, c.ORDINAL_POSITION;