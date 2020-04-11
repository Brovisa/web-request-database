<?php
//
//  https://github.com/ReneNyffenegger/web-request-database
//

function connect_to_webrequest_db() {
   $db = new PDO("sqlite:" . webrequest_db_name());
   $db -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   $db -> exec('pragma foreign_keys=on');
   return $db;
}

function webrequest_db_name() {
#  return 'db/webrequest';
   return '/home/httpd/vhosts/renenyffenegger.ch/db/webrequest';
}

function insert_webrequest() {

   if (array_key_exists('HTTP_REFERER', $_SERVER)) {
      $referrer = $_SERVER['HTTP_REFERER'];
   }
   else {
      $referrer = NULL;
   }

   insert_webrequest_(
      $_SERVER['REQUEST_TIME'   ],
      $_SERVER['REMOTE_ADDR'    ],
      $_SERVER['HTTP_USER_AGENT'],
      $referrer
    );
}


function insert_webrequest_($t, $addr, $ua, $referrer) {

   $db = connect_to_webrequest_db();

   $stmt_id = $db -> prepare('select id from uri where uri = :uri');
   $stmt_id  -> execute(array(':uri' => $_SERVER['REQUEST_URI'] ));
   $row = $stmt_id -> fetch();

   if ($row == NULL) {
      $stmt_ins_uri = $db->prepare('insert into uri (uri) values (:uri)');
      $stmt_ins_uri -> execute(array(':uri' => $_SERVER['REQUEST_URI']));

      $id = $db->lastInsertId();
   }
   else {
      $id = $row['id'];
   }

   $stmt_ins_request = $db->prepare('insert into request (uri_id, t, addr, ua, referrer
    -- , status
    )
    values (:id, :t, :addr, :ua, :referrer
    --, :status
    )');

   $stmt_ins_request -> execute(array(
       ':id'       => $id,
       ':t'        => $t,
       ':addr'     => $addr,
       ':ua'       => $ua,
       ':referrer' => $referrer
   ));

}

?>
