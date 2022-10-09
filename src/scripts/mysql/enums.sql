select c.TABLE_NAME
     , c.COLUMN_NAME
     , COLUMN_TYPE
     , IF(IS_NULLABLE = 'YES', true, false) as is_nullable
from information_schema.`COLUMNS` c
where c.TABLE_SCHEMA = '{db}'
  and c.DATA_TYPE = 'enum'