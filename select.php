<?php

require_once('php/web-request-database.php');
$db = connect_to_webrequest_db();

$db->sqliteCreateFunction('regexp_like', 'preg_match', 2);

$where = '';
if ($_SERVER['QUERY_STRING'] == 'all') {
   # do nothing special
}
elseif (array_key_exists('count', $_REQUEST)) {
   if ($_REQUEST['count'] == 'uri') {
      selectCount($db, 'uri');
   }
   if ($_REQUEST['count'] == 'uri-last-7-days') {
      selectCountUriLast7Days($db);
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
   <a href='select.php?count=uri-last-7-days'>select count(*), uri… (last 7 days)</a><br>
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

function selectCountUriLast7Days($db) {

    $stmt = $db->prepare("
select
   uri,
   count(case when t between strftime('%s', 'now', '-8 days') and strftime('%s', 'now', '-7 days') then 1 end) cnt_7,
   count(case when t between strftime('%s', 'now', '-7 days') and strftime('%s', 'now', '-6 days') then 1 end) cnt_6,
   count(case when t between strftime('%s', 'now', '-6 days') and strftime('%s', 'now', '-5 days') then 1 end) cnt_5,
   count(case when t between strftime('%s', 'now', '-5 days') and strftime('%s', 'now', '-4 days') then 1 end) cnt_4,
   count(case when t between strftime('%s', 'now', '-4 days') and strftime('%s', 'now', '-3 days') then 1 end) cnt_3,
   count(case when t between strftime('%s', 'now', '-3 days') and strftime('%s', 'now', '-2 days') then 1 end) cnt_2,
   count(case when t between strftime('%s', 'now', '-2 days') and strftime('%s', 'now', '-1 days') then 1 end) cnt_1,
   count(case when t between strftime('%s', 'now', '-1 days') and strftime('%s', 'now', '-0 days') then 1 end) cnt_0
from
   request_v
group by
   uri
order by
   cnt_0 desc");

   $stmt -> execute();
   print("<table border='1'>");
   while ($row = $stmt->fetch()) {

      printf("<tr><td>%s</td>
      <td>%d</td><td>%d</td><td>%d</td><td>%d</td><td>%d</td><td>%d</td><td>%d</td> <td>%d</td>
      <td>%s</td>
      </tr>", $row['uri'], $row['cnt_7'], $row['cnt_6'], $row['cnt_5'], $row['cnt_4'], $row['cnt_3'], $row['cnt_2'], $row['cnt_1'], $row['cnt_0'],
      $row['cnt_7'] ? sprintf("%3.1f", 1.0/$row['cnt_7'] * $row['cnt_0']) : '-'
      );
   }

   print("</table>");

}

function referrerNotIn() {
   return "and referrer is not null and 
-- referrer not like 'https://www.google.%' and
-- not regexp_like('/https:[^\/]*(yahoo|google)\.com\//', referrer) and
   not regexp_like('/^https:\/\/"          .
                   "[^.]+\."               . 
                   "(bing|yahoo|google)\." .
                   "[^.]+"                 .
                   "\/?/', referrer) and

   not regexp_like('/^https:\/\/" . 
                   "[^.]+\."  .
                   "[^.]+\."  .
                   "(bing|yahoo|google)\.[^.]+\/?/', referrer) and

   not regexp_like('/\/\/www\.google\.[^.]+\.[^.]+\//', referrer) and
   referrer not like 'https://translate.googleusercontent.com/translate_p%' and
   referrer not like 'https://www.bing.com%' and
   referrer not like 'https://renenyffenegger.ch/%' and
   referrer not like 'https://duckduckgo.com%' and
   referrer not like 'https://baidu.com/%' and
   referrer not like 'http://baidu.com/%' and
   referrer not like 'http://www.adp-gmbh.ch/%' and
   referrer not like 'https://www.ecosia.org/' and
   referrer not like 'https://www.qwant.com/' and
   referrer not like 'https://www.dogedoge.com/'
   ";


}

?>
