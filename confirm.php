<?php
require_once("include/bittorrent.php");
header("Content-Type: text/html; charset=utf-8");
$id = (int) $_GET["id"];
$confirm_md5 = $_GET["secret"];

if (!$id) {
    httperr();
}

dbconn();

$res = \NexusPHP\Components\Database::query("SELECT passhash, secret, editsecret, status FROM users WHERE id = ".\NexusPHP\Components\Database::escape($id)) or sqlerr(__FILE__, __LINE__);
$row = mysqli_fetch_assoc($res);

if (!$row) {
    httperr();
}

if ($row["status"] != "pending") {
    header("Refresh: 0; url=ok.php?type=confirmed");
    exit();
}

$confirm_sec = hash_pad($row["secret"]);
if ($confirm_md5 != md5($confirm_sec)) {
    httperr();
}

\NexusPHP\Components\Database::query("UPDATE users SET status='confirmed', editsecret='' WHERE id=".\NexusPHP\Components\Database::escape($id)." AND status='pending'") or sqlerr(__FILE__, __LINE__);

if (!\NexusPHP\Components\Database::affected_rows()) {
    httperr();
}

    
if ($securelogin == "yes") {
    $securelogin_indentity_cookie = true;
    $passh = md5($row["passhash"].$_SERVER["REMOTE_ADDR"]);
} else {	// when it's op, default is not use secure login
    $securelogin_indentity_cookie = false;
    $passh = md5($row["passhash"]);
}
logincookie($row["id"], $passh, 1, 0x7fffffff, $securelogin_indentity_cookie);
//sessioncookie($row["id"], $passh,false);

header("Refresh: 0; url=ok.php?type=confirm");
