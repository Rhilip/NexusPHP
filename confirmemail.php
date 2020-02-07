<?php
require_once("include/bittorrent.php");

if (!preg_match(':^/(\d{1,10})/([\w]{32})/(.+)$:', $_SERVER["PATH_INFO"], $matches)) {
    httperr();
}

$id = 0 + $matches[1];
$md5 = $matches[2];
$email = urldecode($matches[3]);
//print($email);
//die();

if (!$id) {
    httperr();
}
dbconn();

$res = \NexusPHP\Components\Database::query("SELECT editsecret FROM users WHERE id = $id");
$row = mysqli_fetch_array($res);

if (!$row) {
    httperr();
}

$sec = hash_pad($row["editsecret"]);
if (preg_match('/^ *$/s', $sec)) {
    httperr();
}
if ($md5 != md5($sec . $email . $sec)) {
    httperr();
}

\NexusPHP\Components\Database::query("UPDATE users SET editsecret='', email=" . \NexusPHP\Components\Database::escape($email) . " WHERE id=$id AND editsecret=" . \NexusPHP\Components\Database::escape($row["editsecret"]));

if (!\NexusPHP\Components\Database::affected_rows()) {
    httperr();
}

header("Refresh: 0; url=" . get_protocol_prefix() . "$BASEURL/usercp.php?action=security&type=saved");
