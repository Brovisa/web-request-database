<?php

require_once('php/web-request-database.php');
$db = connect_to_webrequest_db();

print('<pre>');
print("&lt;?php\n");
print("header('Content-Type: text/plain');\n");
print("require_once('php/web-request-database.php');\n");

$stmt = $db->query('select * from request_v');
while ($rec = $stmt->fetch()) {

  printf("insert_webrequest_(%s, %d, %s, %s, %s); print('.');\n",
    dbValue($rec['uri'     ]),
    $rec['t'],
    dbValue($rec['addr'    ]),
    dbValue($rec['ua'      ]),
    dbValue($rec['referrer'])
  );

}
print('?&gt;</pre>');

function dbValue($v) {

   if ($v == NULL) {
      return 'NULL';
   }

   $v = str_replace("'", "''", $v);

   return htmlspecialchars("'$v'");

}

?>
