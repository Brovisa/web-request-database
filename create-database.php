<?php

require_once('php/web-request-database.php');

if (file_exists(webrequest_db_name())) {
   unlink(webrequest_db_name());
}

$db = connect_to_webrequest_db();

$db->exec('create table uri (
   id          integer primary key,
   val         not null unique
)');

$db->exec('create table ua(
   id          integer primary key,
   val         not null unique
)');

$db->exec('create table referrer(
   id          integer primary key,
   val         not null unique
)');

# $db->exec('create table addr(
#    id          integer primary key,
#    val         not null unique
# )');

$db->exec('create table request(
   uri_id      integer not null references uri,
   t           integer not null,
   addr        text            ,
-- addr_id     integer          references addr,
   ua_id       integer          references ua,
   referrer_id integer          references referrer
-- status      integer
)');


$db->exec('create view request_v as
select
   uri.val           as uri,
   req.t             as t,
   addr              as addr,
   ua.val            as ua,
   ref.val           as referrer
-- req.status
from
   request  req                                  join
   uri      uri on req.uri_id      = uri.id left join
   ua       ua  on req.ua_id       = ua.id  left join
   referrer ref on req.referrer_id = ref.id
-- addr     adr on req.addr_id     = adr.id
');

?>
