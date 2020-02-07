<?php
require_once("include/bittorrent.php");
dbconn();
loggedinorreturn();


if ($_GET['id']) {
    stderr("Party is over!", "This trick doesn't work anymore. You need to click the button!");
}
$userid = $CURUSER["id"];
$torrentid = $_POST["id"];
$tsql = \NexusPHP\Components\Database::query("SELECT owner FROM torrents where id=".\NexusPHP\Components\Database::escape($torrentid));
$arr = mysqli_fetch_array($tsql);
if (!$arr) {
    stderr("Error", "Invalid torrent id!");
}
$torrentowner = $arr['owner'];
$tsql = \NexusPHP\Components\Database::query("SELECT COUNT(*) FROM thanks where torrentid=".\NexusPHP\Components\Database::escape($torrentid)." and userid=".\NexusPHP\Components\Database::escape($userid));
$trows = mysqli_fetch_array($tsql);
$t_ab = $trows[0];
if ($t_ab != 0) {
    stderr("Error", "You already said thanks!");
}
if (isset($userid) && isset($torrentid)) {
    $res = \NexusPHP\Components\Database::query("INSERT INTO thanks (torrentid, userid) VALUES (".\NexusPHP\Components\Database::escape($torrentid).", ".\NexusPHP\Components\Database::escape($userid).")");
    KPS("+", $saythanks_bonus, $CURUSER['id']);//User gets bonus for saying thanks
KPS("+", $receivethanks_bonus, $torrentowner);//Thanks receiver get bonus
}
