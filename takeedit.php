<?php
require_once("include/bittorrent.php");
dbconn();
require_once(get_langfile_path());
loggedinorreturn();

function bark($msg)
{
    global $lang_takeedit;
    genbark($msg, $lang_takeedit['std_edit_failed']);
}

if (!mkglobal("id:name:descr:type")) {
    global $lang_takeedit;
    bark($lang_takeedit['std_missing_form_data']);
}

$id = 0 + $id;
if (!$id) {
    die();
}


$res = \NexusPHP\Components\Database::query("SELECT category, owner, filename, save_as, anonymous, picktype, picktime, added FROM torrents WHERE id = ".\NexusPHP\Components\Database::real_escape_string($id));
$row = mysqli_fetch_array($res);
$torrentAddedTimeString = $row['added'];
if (!$row) {
    die();
}

if ($CURUSER["id"] != $row["owner"] && get_user_class() < $torrentmanage_class) {
    bark($lang_takeedit['std_not_owner']);
}
$oldcatmode = \NexusPHP\Components\Database::single("categories", "mode", "WHERE id=".\NexusPHP\Components\Database::escape($row['category']));

$updateset = array();

//$fname = $row["filename"];
//preg_match('/^(.+)\.torrent$/si', $fname, $matches);
//$shortfname = $matches[1];
//$dname = $row["save_as"];

$url = parse_imdb_id($_POST['url']);

if ($enablenfo_main=='yes') {
    $nfoaction = $_POST['nfoaction'];
    if ($nfoaction == "update") {
        $nfofile = $_FILES['nfo'];
        if (!$nfofile) {
            die("No data " . var_dump($_FILES));
        }
        if ($nfofile['size'] > 65535) {
            bark($lang_takeedit['std_nfo_too_big']);
        }
        $nfofilename = $nfofile['tmp_name'];
        if (@is_uploaded_file($nfofilename) && @filesize($nfofilename) > 0) {
            $updateset[] = "nfo = " . \NexusPHP\Components\Database::escape(str_replace("\x0d\x0d\x0a", "\x0d\x0a", file_get_contents($nfofilename)));
        }
        $Cache->delete_value('nfo_block_torrent_id_'.$id);
    } elseif ($nfoaction == "remove") {
        $updateset[] = "nfo = ''";
        $Cache->delete_value('nfo_block_torrent_id_'.$id);
    }
}

$catid = (0 + $type);
if (!is_valid_id($catid)) {
    bark($lang_takeedit['std_missing_form_data']);
}
if (!$name || !$descr) {
    bark($lang_takeedit['std_missing_form_data']);
}
$newcatmode = \NexusPHP\Components\Database::single("categories", "mode", "WHERE id=".\NexusPHP\Components\Database::escape($catid));
if ($enablespecial == 'yes' && get_user_class() >= $movetorrent_class) {
    $allowmove = true;
} //enable moving torrent to other section
else {
    $allowmove = false;
}
if ($oldcatmode != $newcatmode && !$allowmove) {
    bark($lang_takeedit['std_cannot_move_torrent']);
}
$updateset[] = "anonymous = '" . ($_POST["anonymous"] ? "yes" : "no") . "'";
$updateset[] = "name = " . \NexusPHP\Components\Database::escape($name);
$updateset[] = "descr = " . \NexusPHP\Components\Database::escape($descr);
$updateset[] = "url = " . \NexusPHP\Components\Database::escape($url);
$updateset[] = "small_descr = " . \NexusPHP\Components\Database::escape($_POST["small_descr"]);
//$updateset[] = "ori_descr = " . \NexusPHP\Components\Database::escape($descr);
$updateset[] = "category = " . \NexusPHP\Components\Database::escape($catid);
$updateset[] = "source = " . \NexusPHP\Components\Database::escape(0 + $_POST["source_sel"]);
$updateset[] = "medium = " . \NexusPHP\Components\Database::escape(0 + $_POST["medium_sel"]);
$updateset[] = "codec = " . \NexusPHP\Components\Database::escape(0 + $_POST["codec_sel"]);
$updateset[] = "standard = " . \NexusPHP\Components\Database::escape(0 + $_POST["standard_sel"]);
$updateset[] = "processing = " . \NexusPHP\Components\Database::escape(0 + $_POST["processing_sel"]);
$updateset[] = "team = " . \NexusPHP\Components\Database::escape(0 + $_POST["team_sel"]);
$updateset[] = "audiocodec = " . \NexusPHP\Components\Database::escape(0 + $_POST["audiocodec_sel"]);

if (get_user_class() >= $torrentmanage_class) {
    if ($_POST["banned"]) {
        $updateset[] = "banned = 'yes'";
        $_POST["visible"] = 0;
    } else {
        $updateset[] = "banned = 'no'";
    }
}
$updateset[] = "visible = '" . ($_POST["visible"] ? "yes" : "no") . "'";
if (get_user_class()>=$torrentonpromotion_class) {
    if (!isset($_POST["sel_spstate"]) || $_POST["sel_spstate"] == 1) {
        $updateset[] = "sp_state = 1";
    } elseif ((0 + $_POST["sel_spstate"]) == 2) {
        $updateset[] = "sp_state = 2";
    } elseif ((0 + $_POST["sel_spstate"]) == 3) {
        $updateset[] = "sp_state = 3";
    } elseif ((0 + $_POST["sel_spstate"]) == 4) {
        $updateset[] = "sp_state = 4";
    } elseif ((0 + $_POST["sel_spstate"]) == 5) {
        $updateset[] = "sp_state = 5";
    } elseif ((0 + $_POST["sel_spstate"]) == 6) {
        $updateset[] = "sp_state = 6";
    } elseif ((0 + $_POST["sel_spstate"]) == 7) {
        $updateset[] = "sp_state = 7";
    }

    //promotion expiration type
    if (!isset($_POST["promotion_time_type"]) || $_POST["promotion_time_type"] == 0) {
        $updateset[] = "promotion_time_type = 0";
        $updateset[] = "promotion_until = '0000-00-00 00:00:00'";
    } elseif ($_POST["promotion_time_type"] == 1) {
        $updateset[] = "promotion_time_type = 1";
        $updateset[] = "promotion_until = '0000-00-00 00:00:00'";
    } elseif ($_POST["promotion_time_type"] == 2) {
        if ($_POST["promotionuntil"] && strtotime($torrentAddedTimeString) <= strtotime($_POST["promotionuntil"])) {
            $updateset[] = "promotion_time_type = 2";
            $updateset[] = "promotion_until = ".\NexusPHP\Components\Database::escape($_POST["promotionuntil"]);
        } else {
            $updateset[] = "promotion_time_type = 0";
            $updateset[] = "promotion_until = '0000-00-00 00:00:00'";
        }
    }
}
if (get_user_class()>=$torrentsticky_class) {
    if ((0 + $_POST["sel_posstate"]) == 0) {
        $updateset[] = "pos_state = 'normal'";
    } elseif ((0 + $_POST["sel_posstate"]) == 1) {
        $updateset[] = "pos_state = 'sticky'";
    }
}

$pick_info = "";
if (get_user_class()>=$torrentmanage_class && $CURUSER['picker'] == 'yes') {
    if ((0 + $_POST["sel_recmovie"]) == 0) {
        if ($row["picktype"] != 'normal') {
            $pick_info = ", recomendation canceled!";
        }
        $updateset[] = "picktype = 'normal'";
        $updateset[] = "picktime = '0000-00-00 00:00:00'";
    } elseif ((0 + $_POST["sel_recmovie"]) == 1) {
        if ($row["picktype"] != 'hot') {
            $pick_info = ", recommend as hot movie";
        }
        $updateset[] = "picktype = 'hot'";
        $updateset[] = "picktime = ". \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
    } elseif ((0 + $_POST["sel_recmovie"]) == 2) {
        if ($row["picktype"] != 'classic') {
            $pick_info = ", recommend as classic movie";
        }
        $updateset[] = "picktype = 'classic'";
        $updateset[] = "picktime = ". \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
    } elseif ((0 + $_POST["sel_recmovie"]) == 3) {
        if ($row["picktype"] != 'recommended') {
            $pick_info = ", recommend as recommended movie";
        }
        $updateset[] = "picktype = 'recommended'";
        $updateset[] = "picktime = ". \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
    }
}
\NexusPHP\Components\Database::query("UPDATE torrents SET " . join(",", $updateset) . " WHERE id = $id") or sqlerr(__FILE__, __LINE__);

if ($CURUSER["id"] == $row["owner"]) {
    if ($row["anonymous"]=='yes') {
        write_log("Torrent $id ($name) was edited by Anonymous" . $pick_info . $place_info);
    } else {
        write_log("Torrent $id ($name) was edited by $CURUSER[username]" . $pick_info . $place_info);
    }
} else {
    write_log("Torrent $id ($name) was edited by $CURUSER[username], Mod Edit" . $pick_info . $place_info);
}
$returl = "details.php?id=$id&edited=1";
if (isset($_POST["returnto"])) {
    $returl = $_POST["returnto"];
}
header("Refresh: 0; url=$returl");
