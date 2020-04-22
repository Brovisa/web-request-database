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
      selectCountReferrer($db);
   }
   else {
      print("Unknown col: " . $_REQUEST['count']);
   }

   return 0;
}
elseif ($_SERVER['QUERY_STRING'] == 'referrer') {
   $where =referrerNotIn();
}
elseif (array_key_exists('referrer', $_REQUEST)) {
    printf("REFERRER [%s]", $_REQUEST['referrer']);
    return 0;
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
#  if ($colName == 'referrer') {
#     $where = referrerNotIn();
#  }

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

#        if ($colName == 'referrer') {
#           print("<td><a href='" . $row[$colName] . "' target='_blank'>" . $row[$colName] . "</a></td>");
#        }
#        else {
            print("<td>" . $row[$colName] . "</td>");
#        }


         # if ($colName == 'addr') {
         #    print("<td>" . gethostbyaddr($row['addr']) . "</td>");
         # }

       print("</tr>");
   }
   print("</table>");

}

function selectCountReferrer($db) {

   $where = referrerNotIn();

   $stmt = $db -> prepare("select
      count(*) cnt,
      referrer,
      uri
   from
      request_v 
   where
      1 = 1 $where
   group by
      referrer,
      uri
   order by
      count(*) desc");

   $stmt->execute();
   print("<table border='1'>");
   while ($row = $stmt->fetch()) {
       print("<tr><td>" . $row['cnt']    . "</td>");

         print("<td><a href='" . $row['referrer'] . "' target='_blank'>" . substr($row['referrer'], 0, 150) . "</a></td>");
         print("<td>"          . $row['uri'     ]                                          .     "</td>");


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
   not regexp_like('/^https?:\/\/"          .
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
   referrer not like 'https://%.qwant.com/' and
   referrer not like 'https://www.dogedoge.com/' and
   referrer not like 'android-app://com.google.android.%' and
   referrer not like 'https://m.instasrch.com/search/gcse%' and 
   referrer not like 'https://%search.myway.com/search/GGmain.jhtml?%' and
   referrer not like 'http://%search.myway.com/search/GGmain.jhtml?%' and
   referrer not like 'https://m.search2.co/search/%' and
   referrer not like 'https://adfs.contiwan.com/adfs/ls/%' and
   referrer not like 'https://gateway.zscloud.net/auW?origurl=%' and
   referrer not like 'https://translate.googleusercontent.com/translate_c?%' and
   referrer not like 'https://coccoc.com/search?query=%' and
   referrer not like 'https://search.earthlink.net/search%' and
   referrer not like 'https://nortonsafe.search.ask.com/web?%' and
   referrer not like 'https://swisscows.ch/web?%' and
   referrer not like 'https://tylerjira.tylertech.com/browse/%' and
   referrer not like 'https://int.search.tb.ask.com/search/GGmain.jhtml%' and
   referrer not like 'https://search.xfinity.com/?searchTerm=%' and
   referrer not like 'http://blackle.com/%' and
   referrer not like 'https://yandex.ru/%' and
   referrer not like 'https://translate.wordpress.org/%' and
   referrer not like 'https://start.me/search/google?q=%' and
   referrer not like 'https://r.search.aol.com/%' and
   referrer not like 'https://www.lukol.com/%' and
   referrer not in (
      'https://7ooo.ru/',
      'http://m.facebook.com',
      'https://l.facebook.com/',
      'https://github.com/',
      'https://web.skype.com/',
      'http://localhost:8888/',
      'https://gl.duoyioa.com/',
      'https://adfs.colt.net/adfs/ls/wia',
      'https://away.vk.com/'
   )
   " 
   . 
   "
   and referrer not in (
     'https://github.com/ReneNyffenegger/cpp-base64',
     'https://github.com/ReneNyffenegger/gcc-create-library',
     'https://github.com/ReneNyffenegger/WinAPI-4-VBA',
     'https://github.com/ReneNyffenegger/cpp-base64/blob/master/README.md',
     'http://mqjing.blogspot.com/2009/04/c-gcc-library.html',
     'https://github.com/m-ab-s/media-autobuild_suite/pull/1465',
     'https://answers.opencv.org/question/174328/base64-to-mat-and-mat-to-base64/',
     'http://october388.blogspot.com/2009/04/mingwdll.html',
     'https://github.com/zangelus/smartgcc',
     'https://blog.csdn.net/weixin_34341117/article/details/91741856',
     'https://towardsdatascience.com/machine-learning-model-deployment-with-c-fad31d5fe04',
     'https://github.com/ReneNyffenegger/winsqlite3.dll-PowerShell/blob/master/README.md',
     'https://my.oschina.net/VenusV/blog/2946245',
     'https://www.eclipse.org/forums/index.php/t/1102166/',
     'https://www.figma.com/open-source/',
     'https://www.linuxquestions.org/questions/linux-newbie-8/implementing-bluetooth-in-c-4175643687-print/',
     'http://www.cplusplus.com/forum/beginner/35554/',
     'https://medium.com/@fanzongshaoxing/tensorflow-c-api-to-run-a-object-detection-model-4d5928893b02',
     'https://social.technet.microsoft.com/Forums/en-US/ee048512-1b36-40e1-92cf-9f64e2f52299/windows-10-manual?forum=win10itprogeneral',
     'https://rich-v01.bluewin.ch/cp/applink/mail/LoadMessageContent?cKey=1587324117250-5071&iframeID=x-mail-msg-iframe-box-1587324117107&cw=792',
     'https://route7fxcqz4s-thenoobest-che.b542.starter-us-east-2a.openshiftapps.com/?uid=965884',
     'http://october388.blogspot.com/2009/04/mingwdll.html?m=1',
     'https://routekpsxxkl6-nikita19992022-che.b542.starter-us-east-2a.openshiftapps.com/?uid=238554',
     'http://xsstorweb56srs3a.onion/threads/18099/'
   ) and
   referrer not like 'http%://renenyffenegger.ch/notes%' and
   referrer not like 'http://disclaimer.airbusafran-launchers.com/notify-Disclaimer?https/renenyffenegger.ch/%' and
   referrer not like 'https://github.com/%/os_lab_2019/blob/master/lab2/text/lab2.md'
   ";


}

?>
