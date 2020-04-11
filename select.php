<?php

require_once('php/web-request-database.php');
$db = connect_to_webrequest_db();

$where = '';
if ($_SERVER['QUERY_STRING'] == 'all') {
   # do nothing special
}
elseif (array_key_exists('count', $_REQUEST)) {
   if ($_REQUEST['count'] == 'uri') {
      selectCount($db, 'uri');
   }
   elseif ($_REQUEST['count'] == 'ua') {
      selectCount($db, 'ua');
   }
   elseif ($_REQUEST['count'] == 'addr') {
      selectCount($db, 'addr');
   }
   elseif ($_REQUEST['count'] == 'referrer') {
      selectCount($db, 'referrer');
   }
   else {
      print("Unknown col: " . $_REQUEST['count']);
   }

   return 0;
}
elseif ($_SERVER['QUERY_STRING'] == 'referrer') {
   $where =referrerNotIn();
}
else {
   print("<a href='select.php?all'>select *</a><br>
   <a href='select.php?count=uri'>select count(*), uri…</a><br>
   <a href='select.php?count=ua'>select count(*), ua…</a><br>
   <a href='select.php?count=addr'>select count(*), addr…</a><br>
   <a href='select.php?count=referrer'>select count(*), referrer…</a><br>
   <a href='select.php?referrer'>referrer</a>");
   return 0;
}

$stmt = $db -> prepare("select
   uri,
   t,
   addr,
   ua,
   referrer
-- status
from
   request_v
where
   1 = 1 $where
order by
   t
   ");
$stmt->execute();

print("<table border=1>");
   while ($row = $stmt->fetch()) {
       print("<tr><td>" . $row['uri'     ] . "</td>" .
                 "<td>" . date("Y-m-d H:i:s", $row['t']) . "</td>" .
                 "<td>" . $row['addr'    ] . "</td>" .
                 "<td>" . $row['ua'      ] . "</td>" .
                 "<td><a href='" . $row['referrer'] . "' target='_blank'>" . $row['referrer'] . "</a></td>" .
              #  "<td>" . $row['status'  ] . "</td>" .
              "</tr>");
   }
print("</table>");

function selectCount($db, $colName) {

   $where = '';
   if ($colName == 'referrer') {
      $where = referrerNotIn();
   }

   $stmt = $db -> prepare("select
      count(*) cnt,
      $colName
   from
      request_v 
   where
      1 = 1 $where
   group by
      $colName
   order by
      count(*) desc");

   $stmt->execute();
   print("<table border='1'>");
   while ($row = $stmt->fetch()) {
       print("<tr><td>" . $row['cnt']    . "</td>");

         if ($colName == 'referrer') {
            print("<td><a href='" . $row[$colName] . "' target='_blank'>" . $row[$colName] . "</a></td>");
         }
         else {
            print("<td>" . $row[$colName] . "</td>");
         }


         # if ($colName == 'addr') {
         #    print("<td>" . gethostbyaddr($row['addr']) . "</td>");
         # }

       print("</tr>");
   }
   print("</table>");

}

function referrerNotIn() {
   return "and referrer is not null and 
   referrer not like 'https://www.google.%' and
   referrer not like 'https://www.bing.com%' and
   referrer not like 'https://renenyffenegger.ch/%' and
   referrer not like 'https://duckduckgo.com%' and
   referrer not like 'https://baidu.com/%' and
   referrer not like 'http://baidu.com/%' and
   referrer not like 'http://www.adp-gmbh.ch/%' and
   referrer not like 'https://www.ecosia.org/'
   ";


}

?>
