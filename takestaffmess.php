<?php
require "include/bittorrent.php";
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    stderr("Error", "Permission denied!");
}
dbconn();
loggedinorreturn();

if (get_user_class() < UC_ADMINISTRATOR) {
    stderr("Sorry", "Permission denied.");
}

$sender_id = ($_POST['sender'] == 'system' ? 0 : (int)$CURUSER['id']);
$dt = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
$msg = trim($_POST['msg']);
if (!$msg) {
    stderr("Error", "Don't leave any fields blank.");
}
$updateset = $_POST['clases'];
if (is_array($updateset)) {
    foreach ($updateset as $class) {
        if (!is_valid_id($class) && $class != 0) {
            stderr("Error", "Invalid Class");
        }
    }
} else {
    if (!is_valid_id($updateset) && $updateset != 0) {
        stderr("Error", "Invalid Class");
    }
}
$subject = trim($_POST['subject']);
$query = \NexusPHP\Components\Database::query("SELECT id FROM users WHERE class IN (".implode(",", $updateset).")");
while ($dat=mysqli_fetch_assoc($query)) {
    \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, added,  subject, msg) VALUES ($sender_id, $dat[id], $dt, " . \NexusPHP\Components\Database::escape($subject) .", " . \NexusPHP\Components\Database::escape($msg) .")") or sqlerr(__FILE__, __LINE__);
}

header("Refresh: 0; url=staffmess.php?sent=1");
