<?php
require "include/bittorrent.php";
dbconn();
require_once(get_langfile_path());
if ($enablead_advertisement != 'yes') {
    stderr($lang_adredir['std_error'], $lang_adredir['std_ad_system_disabled']);
}
$id=0+$_GET['id'];
if (!$id) {
    stderr($lang_adredir['std_error'], $lang_adredir['std_invalid_ad_id']);
}
$redir=htmlspecialchars_decode(urldecode($_GET['url']));
if (!$redir) {
    stderr($lang_adredir['std_error'], $lang_adredir['std_no_redirect_url']);
}
$adcount=\NexusPHP\Components\Database::count("advertisements", "WHERE id=".\NexusPHP\Components\Database::escape($id));
if (!$adcount) {
    stderr($lang_adredir['std_error'], $lang_adredir['std_invalid_ad_id']);
}
if ($adclickbonus_advertisement) {
    $clickcount=\NexusPHP\Components\Database::count("adclicks", "WHERE adid=".\NexusPHP\Components\Database::escape($id)." AND userid=".\NexusPHP\Components\Database::escape($CURUSER['id']));
    if (!$clickcount) {
        KPS("+", $adclickbonus_advertisement, $CURUSER['id']);
    }
}
\NexusPHP\Components\Database::query("INSERT INTO adclicks (adid, userid, added) VALUES (".\NexusPHP\Components\Database::escape($id).", ".\NexusPHP\Components\Database::escape($CURUSER['id']).", ".\NexusPHP\Components\Database::escape(date("Y-m-d H:i:s")).")");
header("Location: $redir");
