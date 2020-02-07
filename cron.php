<?php

if (PHP_SAPI != 'cli') {
    header("HTTP/1.0 403 Forbidden");
    die('RUN in CLI');
}

require_once("include/bittorrent.php");
dbconn();

function autoclean()
{
    global $argv;
    global $autoclean_interval_one, $rootpath;
    $now = TIMENOW;

    $force_all = PHP_SAPI == 'cli' ? in_array('--force_all', $argv) : false;
    $print = PHP_SAPI == 'cli' ? in_array('--print', $argv) : false;

    $res = sql_query("SELECT value_u FROM avps WHERE arg = 'lastcleantime'");
    $row = mysql_fetch_array($res);
    if (!$row) {
        sql_query("INSERT INTO avps (arg, value_u) VALUES ('lastcleantime',$now)") or sqlerr(__FILE__, __LINE__);
        return false;
    }
    $ts = $row[0];
    if ($ts + $autoclean_interval_one > $now) {
        return false;
    }
    sql_query("UPDATE avps SET value_u=$now WHERE arg='lastcleantime' AND value_u = $ts") or sqlerr(__FILE__, __LINE__);
    if (!mysql_affected_rows()) {
        return false;
    }
    require_once($rootpath . 'include/cleanup.php');
    return docleanup($force_all, $print);
}

if ($useCronTriggerCleanUp) {
    $return = autoclean();
    if ($return) {
        echo $return."\n";
    } else {
        echo "Clean-up not triggered.\n";
    }
} else {
    echo "Forbidden. Clean-up is set to be browser-triggered.\n";
}
