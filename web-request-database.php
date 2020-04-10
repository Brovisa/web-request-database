<?php
//
//  https://github.com/ReneNyffenegger/web-request-database
//

function connect_to_webrequest_db() {
   $db = new PDO("sqlite:" . webrequest_db_name());
   return $db;
}

function webrequest_db_name() {
   return '/home/httpd/vhosts/renenyffenegger.ch/db/webrequest';
}

function insert_webrequest() {
   
   $db = connect_to_webrequest_db();

   $db -> exec('pragma foreign_keys=on');

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
       ':t'        => $_SERVER['REQUEST_TIME'   ],
       ':addr'     => $_SERVER['REMOTE_ADDR'    ],
       ':ua'       => $_SERVER['HTTP_USER_AGENT'],
       ':referrer' => $_SERVER['HTTP_REFERER'   ]
    #  ':status'   => $_SERVER['REDIRECT_STATUS']
   ));
   
}

?>
