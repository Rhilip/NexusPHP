<?php
require_once("include/bittorrent.php");
function bark($msg)
{
    stdhead();
    stdmsg("Failed", $msg);
    stdfoot();
    exit;
}
dbconn();
loggedinorreturn();
if (get_user_class() < $staffmem_class) {
    permissiondenied();
}
if ($_POST['setdealt']) {
    $res = \NexusPHP\Components\Database::query("SELECT id FROM reports WHERE dealtwith=0 AND id IN (" . implode(", ", $_POST[delreport]) . ")");
    while ($arr = mysqli_fetch_assoc($res)) {
        \NexusPHP\Components\Database::query("UPDATE reports SET dealtwith=1, dealtby = $CURUSER[id] WHERE id = $arr[id]") or sqlerr();
    }
    $Cache->delete_value('staff_new_report_count');
} elseif ($_POST['delete']) {
    $res = \NexusPHP\Components\Database::query("SELECT id FROM reports WHERE id IN (" . implode(", ", $_POST[delreport]) . ")");
    while ($arr = mysqli_fetch_assoc($res)) {
        \NexusPHP\Components\Database::query("DELETE from reports WHERE id = $arr[id]") or sqlerr();
    }
    $Cache->delete_value('staff_new_report_count');
    $Cache->delete_value('staff_report_count');
}

header("Refresh: 0; url=reports.php");
