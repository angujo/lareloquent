select regexp_replace(c.COLUMN_NAME,'^([a-zA-Z][a-zA-Z0-9_]{0,})(_type)$','$1') morph_name, c.TABLE_NAME, c.COLUMN_NAME as type_column,  i.COLUMN_NAME as id_column, c.COLUMN_COMMENT
from information_schema.`COLUMNS` c
         join (select ci.table_schema, ci.table_name,ci.COLUMN_NAME  from information_schema.`COLUMNS` ci ) i
              on i.TABLE_NAME =c.TABLE_NAME and i.TABLE_SCHEMA =c.TABLE_SCHEMA and i.COLUMN_NAME = concat(regexp_replace(c.COLUMN_NAME,'^([a-zA-Z][a-zA-Z0-9_]{0,})(_type)$','$1'),'_id')
where c.TABLE_SCHEMA ='{db}' and regexp_like(c.COLUMN_NAME,'^[a-zA-Z][a-zA-Z0-9_]{0,}_type$')