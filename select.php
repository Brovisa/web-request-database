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

   print('<!DOCTYPE>
<html><head><title>Select count of URIs for the last 7 deays</title>
<style>
  td.abs {
    background-color:#eef
  }
  td.rel {
    background-color:#efe
  }
</style>
<body>');

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
   cnt_0 desc
--   cnt_0 - cnt_7 desc
");

   $stmt -> execute();
   print("<table border='1'>");
   while ($row = $stmt->fetch()) {

      printf("<tr><td>%s</td>
      <td>%d</td><td>%d</td><td>%d</td><td>%d</td><td>%d</td><td>%d</td><td>%d</td> <td>%d</td>
      <td class='rel'>%s</td>
      <td class='abs'>%d</td>
      </tr>", $row['uri'], $row['cnt_7'], $row['cnt_6'], $row['cnt_5'], $row['cnt_4'], $row['cnt_3'], $row['cnt_2'], $row['cnt_1'], $row['cnt_0'],
      $row['cnt_7'] ? sprintf("%3.1f", 1.0/$row['cnt_7'] * $row['cnt_0']) : '-',
      $row['cnt_0'] - $row['cnt_7']
      );
   }

   print("</table>");

   print('</body></html>');

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
   referrer not like 'https://translate.googleusercontent.com/%' and
   referrer not like 'https://www.bing.com%' and
   referrer not like 'https://renenyffenegger.ch/%' and
   referrer not like 'https://duckduckgo.com%' and
   referrer not like 'https://baidu.com/%' and
   referrer not like 'http://baidu.com/%' and
   referrer not like 'http://www.adp-gmbh.ch/%' and
   referrer not like 'https://www.ecosia.org/%' and
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
   referrer not like 'https://%.search.ask.com/web?%' and
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
   referrer not like 'http%://www.so.com/%' and
   referrer not like 'http%://www.seeres.com/%' and
   referrer not like 'http%://seeres.com/%' and
   referrer not like 'https://www.startsiden.no/sok/%' and
   referrer not like 'https://www.startsiden.no/sok/%' and
   referrer not like 'https://oceanhero.today/web?q=%' and
   referrer not like 'http://doc.oopsystemhk.com/Search/Default.aspx?%' and
   referrer not like 'https://gibiru.com/results.html?q%' and
   referrer not like 'https://www.gle-search.com/%' and
   referrer not like 'https://gateway.zscalertwo.net/%' and
   referrer not like 'https://dsearch.com/%' and
   referrer not like 'https://suche.t-online.de/%' and
   referrer not like 'https://search.lilo.org/%' and
   referrer not like 'http://wenote.huawei.com/%' and
   referrer not in (
      'https://renenyffenegger.ch',                                          -- XXX
      'http://renenyffenegger.ch',                                           -- XXX
      'https://7ooo.ru/',
      'https://metager.de/',
      'http://m.facebook.com',
      'https://l.facebook.com/',
      'https://github.com/',
      'http://surf-es.com/',
      'http://www.surf-es.com/',
      'https://web.skype.com/',
      'https://getpocket.com/',
      'https://g3.luciaz.me/',
      'https://github.ibm.com/',
      'http://localhost:8888/',
      'https://devtalk.blender.org/',                                        -- ???
      'https://search.becovi.com/',
      'https://gl.duoyioa.com/',
      'https://adfs.colt.net/adfs/ls/wia',
      'https://results.searchlock.com/',
      'https://cbsearch.site/',
      'https://www.developpez.net/',
      'https://away.vk.com/',
      'https://gg0.chn.moe/',
      'https://l.messenger.com/',
      'https://m.vk.com/',
      'https://adguard.com/referrer.html',
      'http://go.mail.ru/search_images',
      'https://daynhauhoc.com/',
      'https://www.kadaza.com/',
      'https://www.gog-info.com/',
      'https://www.qop-home.com/',
      'https://start.duckduckgo.com/',
      'https://workona.com/redirect/',
      'https://www.ardanlabs.com/',
      'https://sso.bah.com/',
      'https://sirius.na.sas.com/Sirius/GSTS/ShowTrack.aspx?trknum=7613050096',
      'https://route92a3pi42-vladimirkv-che.8a09.starter-us-east-2.openshiftapps.com/?uid=724079',
      'http://rsdn.org/forum/db/2446832.all',
      'https://ec58f1e1-ec46-4ede-9c32-9f8ae8ae7fb3.ws-us02.gitpod.io/',
      'https://gerrit.ericsson.se/',
      'android-app://com.linkedin.android',
      'android-app://com.linkedin.android/',
      'https://int.search.myway.com/',
      'https://t.co/',
      'https://www.accueil-search.com/',
      'https://www.reddit.com/',
      'https://dev.to/',
      'https://www.twitch.tv/',
      'https://app.raindrop.io/',
      'https://jit.ozon.ru/browse/RE-1999',
      'http://172.17.7.22:15871/cgi-bin/blockOptions.cgi?ws-session=1485309825',
      'http://biosc.xyz/results.php?wd=select%20only%20top%20row%20oracle'
   )
   " 
   . 
   "
   and referrer not in (
     'https://github.com/ReneNyffenegger/cpp-base64',
     'https://github.com/ReneNyffenegger/gcc-create-library',
     'https://github.com/ReneNyffenegger/winsqlite3.dll-4-VBA',
     'https://github.com/ReneNyffenegger/winsqlite3.dll-4-VBA/blob/master/README.md',
     'https://github.com/ReneNyffenegger/winsqlite3.dll-PowerShell',
     'https://github.com/marksisson/ReneNyffenegger.cpp-base64',
     'https://github.com/dedmen/cpp-base64/tree/0aaaf66785558807da1c331be114f8727f7f5a2b',
     'https://github.com/wnxd/cpp-base64',
     'http://mqjing.blogspot.com/2009/04/c-gcc-library.html',
     'https://dnupaseventeentwo.elfiny.top/pa-17-2-unix/21_5.html',
     'https://github.com/m-ab-s/media-autobuild_suite/pull/1465',
     'https://answers.opencv.org/question/174328/base64-to-mat-and-mat-to-base64/',
     'http://october388.blogspot.com/2009/04/mingwdll.html',
     'https://github.com/zangelus/smartgcc',
     'https://github.com/igordevM/MietStudy/blob/master/lab2/text/lab2.md',
     'https://towardsdatascience.com/machine-learning-model-deployment-with-c-fad31d5fe04',
     'https://github.com/ReneNyffenegger/winsqlite3.dll-PowerShell/blob/master/README.md',
     'https://my.oschina.net/VenusV/blog/2946245',
     'https://www.eclipse.org/forums/index.php/t/1102166/',
     'https://www.figma.com/open-source/',
     'https://www.linuxquestions.org/questions/linux-newbie-8/implementing-bluetooth-in-c-4175643687-print/',
     'http://www.cplusplus.com/forum/beginner/35554/',
     'https://medium.com/@fanzongshaoxing/tensorflow-c-api-to-run-a-object-detection-model-4d5928893b02',
     'https://medium.com/androidiots/the-magic-of-kotlin-native-part-2-49097c2dea1a',
     'https://bitbucket.org/jeewhanchoi/uoregon-cis330-s20-assignments/src/master/lab04/REAMDE.md',
     'https://social.technet.microsoft.com/Forums/en-US/ee048512-1b36-40e1-92cf-9f64e2f52299/windows-10-manual?forum=win10itprogeneral',
     'https://rich-v01.bluewin.ch/cp/applink/mail/LoadMessageContent?cKey=1587324117250-5071&iframeID=x-mail-msg-iframe-box-1587324117107&cw=792',
     'https://www.developpez.net/forums/d2074018/c-cpp/c/bibliotheques-systemes-outils/bibliotheque-standard/ellipse-passage-d-arguments-multiples-stdarg/',
     'https://route7fxcqz4s-thenoobest-che.b542.starter-us-east-2a.openshiftapps.com/?uid=965884',
     'https://github.com/ReneNyffenegger/cpp-base64/',
     'https://github.com/ReneNyffenegger/cpp-base64/blob/master/test.cpp',
     'http://october388.blogspot.com/2009/04/mingwdll.html?m=1',
     'https://routekpsxxkl6-nikita19992022-che.b542.starter-us-east-2a.openshiftapps.com/?uid=238554',
     'http://xsstorweb56srs3a.onion/threads/18099/',
     'http://www.cse.iitd.ac.in/~srsarangi/courses/2019/col_331_2019/index.html',
     'https://bitbucket.org/lednesday/uoregon-cis330-s20/src/master/lab04/',                             -- ???
     'https://pc-newtab.maxthon.com/?&ln=en-us&mxver=5.3.8.2000&mxpn=max5'                               -- ???
   ) and
   referrer not like 'https://medium.com/@hussaini.faisal/hackthebox-writeup-control-370ff9ec01bb%' and
   referrer not like 'https://github.com/ReneNyffenegger/cpp-base64/%' and
   referrer not like 'https://github.com/ReneNyffenegger/WinAPI-4-VBA%' and
   referrer not like 'http%://renenyffenegger.ch/notes%' and
   referrer not like 'https://%.kutu66.com/GitHub/article_150480' and
   referrer not like 'http://disclaimer.airbusafran-launchers.com/notify-Disclaimer?https/renenyffenegger.ch/%' and
   referrer not like 'https://github.com/%/os_lab_2019/blob/master/lab2/text/lab2.md' and
   referrer not like 'https://blog.csdn.net/weixin_34341117/article/details/91741856%' and
   referrer not like 'https://t.co/2fklLJl2Fl%'
   ";


}

?>
