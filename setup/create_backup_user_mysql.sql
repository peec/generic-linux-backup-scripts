CREATE USER 'backup'@'localhost' IDENTIFIED BY  'backup';

GRANT INSERT ,
SELECT,
LOCK TABLES,
SHOW DATABASES,
CREATE ,
DROP ,
RELOAD ,
SUPER ,
CREATE TEMPORARY TABLES ,
REPLICATION CLIENT ON * . * TO  'backup'@'localhost' IDENTIFIED BY  'backup' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;
