<?php
require "include/bittorrent.php";
dbconn();

//Send some headers to keep the user's browser from caching the response.
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-Type: text/xml; charset=utf-8");

$torrentid = 0 + $_GET['torrentid'];
if (isset($CURUSER)) {
    $res_bookmark = \NexusPHP\Components\Database::query("SELECT * FROM bookmarks WHERE torrentid=" . \NexusPHP\Components\Database::escape($torrentid) . " AND userid=" . \NexusPHP\Components\Database::escape($CURUSER[id]));
    if (mysqli_num_rows($res_bookmark) == 1) {
        \NexusPHP\Components\Database::query("DELETE FROM bookmarks WHERE torrentid=" . \NexusPHP\Components\Database::escape($torrentid) . " AND userid=" . \NexusPHP\Components\Database::escape($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
        $Cache->delete_value('user_'.$CURUSER['id'].'_bookmark_array');
        echo "deleted";
    } else {
        \NexusPHP\Components\Database::query("INSERT INTO bookmarks (torrentid, userid) VALUES (" . \NexusPHP\Components\Database::escape($torrentid) . "," . \NexusPHP\Components\Database::escape($CURUSER['id']) . ")") or sqlerr(__FILE__, __LINE__);
        $Cache->delete_value('user_'.$CURUSER['id'].'_bookmark_array');
        echo "added";
    }
} else {
    echo "failed";
}
