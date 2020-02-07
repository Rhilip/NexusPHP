<?php
require "include/bittorrent.php";
dbconn();
require_once(get_langfile_path());
loggedinorreturn();

if (get_user_class() < $userprofile_class) {
    permissiondenied();
}

$userid = 0 + $_GET["id"];
if (!is_valid_id($userid)) {
    stderr($lang_iphistory['std_error'], $lang_iphistory['std_invalid_id']);
}

$res = \NexusPHP\Components\Database::query("SELECT username FROM users WHERE id = $userid") or sqlerr(__FILE__, __LINE__);
if (mysqli_num_rows($res) == 0) {
    stderr($lang_iphistory['error'], $lang_iphistory['text_user_not_found']);
}

$arr = mysqli_fetch_array($res);
$username = $arr["username"];

$perpage = 20;

$ipcountres = \NexusPHP\Components\Database::query("SELECT COUNT(DISTINCT(access)) FROM iplog WHERE userid = $userid");
$ipcountres = mysqli_fetch_row($ipcountres);
$countrows = $ipcountres[0]+1;
$order = $_GET['order'];

list($pagertop, $pagerbottom, $limit) = pager($perpage, $countrows, "iphistory.php?id=$userid&order=$order&");

$query = "SELECT u.id, u.ip AS ip, last_access AS access FROM users as u WHERE u.id = $userid
UNION DISTINCT SELECT u.id, iplog.ip as ip, iplog.access as access FROM users AS u
RIGHT JOIN iplog on u.id = iplog.userid WHERE u.id = $userid ORDER BY access DESC $limit";

$res = \NexusPHP\Components\Database::query($query) or sqlerr(__FILE__, __LINE__);

stdhead($lang_iphistory['head_ip_history_log_for'].$username);
begin_main_frame();

print("<h1 align=\"center\">".$lang_iphistory['text_historical_ip_by'] . get_username($userid)."</h1>");

if ($countrows > $perpage) {
    echo $pagertop;
}

print("<table width=500 border=1 cellspacing=0 cellpadding=5 align=center>\n");
print("<tr>\n
<td class=colhead>".$lang_iphistory['col_last_access']."</td>\n
<td class=colhead>".$lang_iphistory['col_ip']."</td>\n
<td class=colhead>".$lang_iphistory['col_hostname']."</td>\n
</tr>\n");
while ($arr = mysqli_fetch_array($res)) {
    $addr = "";
    $ipshow = "";
    if ($arr["ip"]) {
        $ip = $arr["ip"];
        $dom = @gethostbyaddr($arr["ip"]);
        if ($dom == $arr["ip"] || @gethostbyname($dom) != $arr["ip"]) {
            $addr = $lang_iphistory['text_not_available'];
        } else {
            $addr = $dom;
        }

        $queryc = "SELECT COUNT(*) FROM
(
SELECT u.id FROM users AS u WHERE u.ip = " . \NexusPHP\Components\Database::escape($ip) . "
UNION SELECT u.id FROM users AS u RIGHT JOIN iplog ON u.id = iplog.userid WHERE iplog.ip = " . \NexusPHP\Components\Database::escape($ip) . "
GROUP BY u.id
) AS ipsearch";
        $resip = \NexusPHP\Components\Database::query($queryc) or sqlerr(__FILE__, __LINE__);
        $arrip = mysqli_fetch_row($resip);
        $ipcount = $arrip[0];

        if ($ipcount > 1) {
            $ipshow = "<a href=\"ipsearch.php?ip=". $arr['ip'] ."\">" . $arr['ip'] ."</a> <b>(<font class='striking'>".$lang_iphistory['text_duplicate']."</font>)</b>";
        } else {
            $ipshow = "<a href=\"ipsearch.php?ip=". $arr['ip'] ."\">" . $arr['ip'] ."</a>";
        }
    }
    $date = gettime($arr["access"]);
    print("<tr><td>".$date."</td>\n");
    print("<td>".$ipshow."</td>\n");
    print("<td>".$addr."</td></tr>\n");
}

print("</table>");

echo $pagerbottom;

end_main_frame();
stdfoot();
die;
