<?php

use Rhilip\Bencode\Bencode;

require_once("include/bittorrent.php");
dbconn();
$id = (int)$_GET["id"];
if (!$id) {
    httperr();
}
$passkey = $_GET['passkey'];
if ($passkey) {
    $res = sql_query("SELECT * FROM users WHERE passkey=". sqlesc($passkey)." LIMIT 1");
    $user = mysql_fetch_array($res);
    if (!$user) {
        die("invalid passkey");
    } elseif ($user['enabled'] == 'no' || $user['parked'] == 'yes') {
        die("account disabed or parked");
    }
    $oldip = $user['ip'];
    $user['ip'] = getip();
    $CURUSER = $user;
} else {
    loggedinorreturn();
    parked();
    $letdown = $_GET['letdown'];
    if (!$letdown && $CURUSER['showdlnotice'] == 1) {
        header("Location: " . get_protocol_prefix() . "$BASEURL/downloadnotice.php?torrentid=".$id."&type=firsttime");
    } elseif (!$letdown && $CURUSER['showclienterror'] == 'yes') {
        header("Location: " . get_protocol_prefix() . "$BASEURL/downloadnotice.php?torrentid=".$id."&type=client");
    } elseif (!$letdown && $CURUSER['leechwarn'] == 'yes') {
        header("Location: " . get_protocol_prefix() . "$BASEURL/downloadnotice.php?torrentid=".$id."&type=ratio");
    }
}
//User may choose to download torrent from RSS. So log ip changes when downloading torrents.
if ($iplog1 == "yes") {
    if (($oldip != $CURUSER["ip"]) && $CURUSER["ip"]) {
        sql_query("INSERT INTO iplog (ip, userid, access) VALUES (" . sqlesc($CURUSER['ip']) . ", " . $CURUSER['id'] . ", '" . $CURUSER['last_access'] . "')");
    }
}
//User may choose to download torrent from RSS. So update his last_access and ip when downloading torrents.
sql_query("UPDATE users SET last_access = ".sqlesc(date("Y-m-d H:i:s")).", ip = ".sqlesc($CURUSER['ip'])."  WHERE id = ".sqlesc($CURUSER['id']));

/*
@ini_set('zlib.output_compression', 'Off');
@set_time_limit(0);

if (@ini_get('output_handler') == 'ob_gzhandler' AND @ob_get_length() !== false)
{	// if output_handler = ob_gzhandler, turn it off and remove the header sent by PHP
    @ob_end_clean();
    header('Content-Encoding:');
}
*/
if ($_COOKIE["c_secure_tracker_ssl"] == base64("yeah")) {
    $tracker_ssl = true;
} else {
    $tracker_ssl = false;
}
if ($tracker_ssl == true) {
    $ssl_torrent = "https://";
    if ($https_announce_urls[0] != "") {
        $base_announce_url = $https_announce_urls[0];
    } else {
        $base_announce_url = $announce_urls[0];
    }
} else {
    $ssl_torrent = "http://";
    $base_announce_url = $announce_urls[0];
}



$res = sql_query("SELECT name, filename, save_as,  size, owner,banned FROM torrents WHERE id = ".sqlesc($id)) or sqlerr(__FILE__, __LINE__);
$row = mysql_fetch_assoc($res);
$fn = "$torrent_dir/$id.torrent";
if ($CURUSER['downloadpos']=="no") {
    permissiondenied();
}
if (!$row || !is_file($fn) || !is_readable($fn)) {
    httperr();
}
if ($row['banned'] == 'yes' && get_user_class() < $seebanned_class) {
    permissiondenied();
}
sql_query("UPDATE torrents SET hits = hits + 1 WHERE id = ".sqlesc($id)) or sqlerr(__FILE__, __LINE__);

if (strlen($CURUSER['passkey']) != 32) {
    $CURUSER['passkey'] = md5($CURUSER['username'].date("Y-m-d H:i:s").$CURUSER['passhash']);
    sql_query("UPDATE users SET passkey=".sqlesc($CURUSER[passkey])." WHERE id=".sqlesc($CURUSER[id]));
}

$dict = Bencode::load($fn);
$dict['announce'] = $ssl_torrent . $base_announce_url . '?passkey=' . $CURUSER['passkey'];

// add multi-tracker
if (count($announce_urls) > 1) {
    foreach ($announce_urls as $announce_url) {
        /** d['announce-list'] = [[ tracker1, tracker2, tracker3 ]] */
        $dict['announce-list'][0][] = $ssl_torrent . $announce_url . '?passkey=' . $CURUSER['passkey'];

        /** d['announce-list'] = [ [tracker1], [backup1], [backup2] ] */
        //$dict['announce-list'][] = [$ssl_torrent . $announce_url . "?passkey=" . $CURUSER['passkey']];
    }
}

header("Content-Type: application/x-bittorrent");

if (str_replace("Gecko", "", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) {
    header("Content-Disposition: attachment; filename=\"$torrentnameprefix." . $row["save_as"] . ".torrent\" ; charset=utf-8");
} elseif (str_replace("Firefox", "", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) {
    header("Content-Disposition: attachment; filename=\"$torrentnameprefix." . $row["save_as"] . ".torrent\" ; charset=utf-8");
} elseif (str_replace("Opera", "", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) {
    header("Content-Disposition: attachment; filename=\"$torrentnameprefix." . $row["save_as"] . ".torrent\" ; charset=utf-8");
} elseif (str_replace("IE", "", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) {
    header("Content-Disposition: attachment; filename=" . str_replace("+", "%20", rawurlencode("$torrentnameprefix." . $row["save_as"] . ".torrent")));
} else {
    header("Content-Disposition: attachment; filename=" . str_replace("+", "%20", rawurlencode("$torrentnameprefix." . $row["save_as"] . ".torrent")));
}

print(Bencode::encode($dict));
