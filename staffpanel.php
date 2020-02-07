<?php
ob_start();
require_once("include/bittorrent.php");
dbconn();
loggedinorreturn();
stdhead("Administration");
print("<h1 align=center>Administration</h1>");
if (get_user_class() < UC_MODERATOR) {
    stdmsg("Error", "Access denied!!!");
    stdfoot();
    exit;
}
begin_main_frame();

///////////////////// SysOp Only \\\\\\\\\\\\\\\\\\\\\\\\\\\\
if (get_user_class() >= UC_SYSOP) {
    echo("<h1 align=center>..:: For SysOp Only  ::..</h1>");
    print("<br />");
    print("<br />");
    print("<table width=80% border=1 cellspacing=0 cellpadding=5 align=center>");
    echo("<td class=colhead align=left>Option Name</td><td class=colhead align=left>Info</td>");
    $query = "SELECT * FROM sysoppanel";
    $sql = \NexusPHP\Components\Database::query($query);
    while ($row = mysqli_fetch_array($sql)) {
        $id = $row['id'];
        $name = $row['name'];
        $url = $row['url'];
        $info = $row['info'];

        echo("<tr><td class=rowfollow align=left><strong><a href=$url>$name</a></strong></td> <td class=rowfollow align=left>$info</td></tr>");
    }
    print("</table>");
    print("<br />");
    print("<br />");
}
///////////////////// Admin Only \\\\\\\\\\\\\\\\\\\\\\\\\\\\
if (get_user_class() >= UC_ADMINISTRATOR) {
    echo("<h1 align=center>..:: For Administrator Only :..</h1>");
    print("<br />");
    print("<br />");
    print("<table width=80% border=1 cellspacing=0 cellpadding=5 align=center>");
    echo("<td class=colhead align=left>Option Name</td><td class=colhead align=left>Info</td>");
    $query = "SELECT * FROM adminpanel";
    $sql = \NexusPHP\Components\Database::query($query);
    while ($row = mysqli_fetch_array($sql)) {
        $id = $row['id'];
        $name = $row['name'];
        $url = $row['url'];
        $info = $row['info'];

        echo("<tr><td class=rowfollow align=left><strong><a href=$url>$name</a></strong></td> <td class=rowfollow align=left>$info</td></tr>");
    }
    print("</table>");
    print("<br />");
    print("<br />");
}
///////////////////// Moderator Only \\\\\\\\\\\\\\\\\\\\\\\\\\\\
if (get_user_class() >= UC_MODERATOR) {
    echo("<h1 align=center>..:: For Moderator Only  ::..</h1>");
    print("<br />");
    print("<br />");
    print("<table width=80% border=1 cellspacing=0 cellpadding=5 align=center>");
    echo("<td class=colhead align=left>Option Name</td><td class=colhead align=left>Info</td>");
    $query = "SELECT * FROM modpanel";
    $sql = \NexusPHP\Components\Database::query($query);
    while ($row = mysqli_fetch_array($sql)) {
        $id = $row['id'];
        $name = $row['name'];
        $url = $row['url'];
        $info = $row['info'];

        echo("<tr><td class=rowfollow align=left><strong><a href=$url>$name</a></strong></td> <td class=rowfollow align=left>$info</td></tr>");
    }

    print("</table>");
    print("<br />");
    print("<br />");
}
end_main_frame();
stdfoot();
