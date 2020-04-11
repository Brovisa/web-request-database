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
   if (is_dir('/home/httpd/vhosts/renenyffenegger.ch/db')) {
      return '/home/httpd/vhosts/renenyffenegger.ch/db/webrequest';
   }
   return 'db/webrequest';
#  return ;
}

function insert_webrequest() {

   if (array_key_exists('HTTP_REFERER', $_SERVER)) {
      $referrer = $_SERVER['HTTP_REFERER'];
   }
   else {
      $referrer = NULL;
   }

   insert_webrequest_(
      $_SERVER['REQUEST_URI'    ],
      $_SERVER['REQUEST_TIME'   ],
      $_SERVER['REMOTE_ADDR'    ],
      $_SERVER['HTTP_USER_AGENT'],
      $referrer
    );
}

function id_of($db, $tab, $val) {

   if ($val == NULL) {
      return NULL;
   }

   $stmt_id = $db -> prepare("select id from $tab where val = :val");
   $stmt_id  -> execute(array(':val' => $val));
   $row = $stmt_id -> fetch();

   if ($row == NULL) {
      $stmt_ins_uri = $db->prepare("insert into $tab (val) values (:val)");
      $stmt_ins_uri -> execute(array(':val' => $val));

      $id = $db->lastInsertId();
   }
   else {
      $id = $row['id'];
   }

   return $id;
}

function insert_webrequest_($uri, $t, $addr, $ua, $referrer) {

   $db = connect_to_webrequest_db();

   $uri_id      = id_of($db, 'uri'     , $uri     );
   $ua_id       = id_of($db, 'ua'      , $ua      );
   $referrer_id = id_of($db, 'referrer', $referrer);
#  $addr_id     = id_of($db, 'addr'    , $addr    );

   $stmt_ins_request = $db->prepare('insert into request (uri_id, t, addr, ua_id, referrer_id
    -- , status
    )
    values (:uri_id, :t, :addr, :ua_id, :referrer_id
    --, :status
    )');

   $stmt_ins_request -> execute(array(
       ':uri_id'      => $uri_id,
       ':t'           => $t,
       ':addr'        => $addr,
#      ':addr_id'     => $addr_id,
       ':ua_id'       => $ua_id,
       ':referrer_id' => $referrer_id
   ));

}

?>
