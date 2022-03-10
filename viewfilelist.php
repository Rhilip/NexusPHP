<?php
require "include/bittorrent.php";
dbconn();

// 文件列表在上传种子后就绝对不会改变了（因为没有办法重新上传种子），由此我们可以给它一个365天的缓存
header("Cache-Control: max-age=31536000");
header("Content-Type: text/json; charset=utf-8");

$id = 0 + $_GET['id'];
if (isset($CURUSER)) {
    $res = sql_query("SELECT files FROM files WHERE torrent = $id");
    if ($row = mysql_fetch_row($res)) {
        echo $row[0];
    }
}