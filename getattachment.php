<?php
require_once("include/bittorrent.php");
dbconn();
loggedinorreturn();
parked();
$id = (int)$_GET["id"];

if (!$id) {
    die('Invalid id.');
}
$dlkey = $_GET["dlkey"];

if (!$dlkey) {
    die('Invalid key');
}
$res = \NexusPHP\Components\Database::query("SELECT * FROM attachments WHERE id = ".\NexusPHP\Components\Database::escape($id)." AND dlkey = ".\NexusPHP\Components\Database::escape($dlkey)." LIMIT 1") or sqlerr(__FILE__, __LINE__);
$row = mysqli_fetch_assoc($res);
if (!$row) {
    die('No attachment found.');
}
$filelocation = $httpdirectory_attachment."/".$row['location'];
if (!is_file($filelocation) || !is_readable($filelocation)) {
    die('File not found or cannot be read.');
}
$f = fopen($filelocation, "rb");
if (!$f) {
    die("Cannot open file");
}

header("Content-Length: " . $row['filesize']);
header("Content-Type: application/octet-stream");

if (str_replace("Gecko", "", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) {
    header("Content-Disposition: attachment; filename=\"$row[filename]\" ; charset=utf-8");
} elseif (str_replace("Firefox", "", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) {
    header("Content-Disposition: attachment; filename=\"$row[filename]\" ; charset=utf-8");
} elseif (str_replace("Opera", "", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) {
    header("Content-Disposition: attachment; filename=\"$row[filename]\" ; charset=utf-8");
} elseif (str_replace("IE", "", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) {
    header("Content-Disposition: attachment; filename=".str_replace("+", "%20", rawurlencode($row[filename])));
} else {
    header("Content-Disposition: attachment; filename=".str_replace("+", "%20", rawurlencode($row[filename])));
}

do {
    $s = fread($f, 4096);
    print($s);
} while (!feof($f));
\NexusPHP\Components\Database::query("UPDATE attachments SET downloads = downloads + 1 WHERE id = ".\NexusPHP\Components\Database::escape($id)) or sqlerr(__FILE__, __LINE__);
$Cache->delete_value('attachment_'.$dlkey.'_content');
exit;
