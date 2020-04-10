<?php

require_once('php/web-request-database.php');

if (file_exists(webrequest_db_name())) {
   unlink(webrequest_db_name());
}

$db = connect_to_webrequest_db();

$db->exec('create table uri (
   id    integer primary key,
   uri   not null unique
)');

$db->exec('create table request(
   uri_id   integer not null references uri,
   t        integer not null,
   addr     text,
   ua       text,
   referrer text
-- status   integer
)');

?>
