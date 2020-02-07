<?php
require_once("include/bittorrent.php");
function bark($msg)
{
    stdhead();
    stdmsg("Update Has Failed !", $msg);
    stdfoot();
    exit;
}
dbconn();
loggedinorreturn();

if (isset($_POST["nowarned"])&&($_POST["nowarned"]=="nowarned")) {
    //if (get_user_class() >= UC_SYSOP) {
    if (get_user_class() < UC_MODERATOR) {
        stderr("Sorry", "Access denied.");
    }
    {
if (empty($_POST["usernw"]) && empty($_POST["desact"]) && empty($_POST["delete"])) {
    bark("You Must Select A User To Edit.");
}

if (!empty($_POST["usernw"])) {
    $msg = \NexusPHP\Components\Database::escape("Your Warning Has Been Removed By: " . $CURUSER['username'] . ".");
    $added = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
    $userid = implode(", ", $_POST[usernw]);
    //\NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, msg, added) VALUES (0, $userid, $msg, $added)") or sqlerr(__FILE__, __LINE__);

    $r = \NexusPHP\Components\Database::query("SELECT modcomment FROM users WHERE id IN (" . implode(", ", $_POST[usernw]) . ")")or sqlerr(__FILE__, __LINE__);
    $user = mysqli_fetch_array($r);
    $exmodcomment = $user["modcomment"];
    $modcomment = date("Y-m-d") . " - Warning Removed By " . $CURUSER['username'] . ".\n". $modcomment . $exmodcomment;
    \NexusPHP\Components\Database::query("UPDATE users SET modcomment=" . \NexusPHP\Components\Database::escape($modcomment) . " WHERE id IN (" . implode(", ", $_POST[usernw]) . ")") or sqlerr(__FILE__, __LINE__);

    $do="UPDATE users SET warned='no', warneduntil='0000-00-00 00:00:00' WHERE id IN (" . implode(", ", $_POST[usernw]) . ")";
    $res=\NexusPHP\Components\Database::query($do);
}

if (!empty($_POST["desact"])) {
    $do="UPDATE users SET enabled='no' WHERE id IN (" . implode(", ", $_POST['desact']) . ")";
    $res=\NexusPHP\Components\Database::query($do);
}
}
}
header("Refresh: 0; url=warned.php");
