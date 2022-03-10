<?php
require_once("include/bittorrent.php");

use Rhilip\Bencode;

ini_set("upload_max_filesize", $max_torrent_size);
dbconn();
require_once(get_langfile_path());
require(get_langfile_path("", true));
loggedinorreturn();

function bark($msg)
{
    global $lang_takeupload;
    genbark($msg, $lang_takeupload['std_upload_failed']);
    die;
}


if ($CURUSER["uploadpos"] == 'no') {
    die;
}

foreach (explode(":", "descr:type:name") as $v) {
    if (!isset($_POST[$v])) {
        bark($lang_takeupload['std_missing_form_data']);
    }
}

if (!isset($_FILES["file"])) {
    bark($lang_takeupload['std_missing_form_data']);
}

$f = $_FILES["file"];
$fname = $f["name"];
if (empty($fname)) {
    bark($lang_takeupload['std_empty_filename']);
}
if (get_user_class()>=$beanonymous_class && $_POST['uplver'] == 'yes') {
    $anonymous = "yes";
    $anon = "Anonymous";
} else {
    $anonymous = "no";
    $anon = $CURUSER["username"];
}

$url = parse_imdb_id($_POST['url']);

$nfo = '';
if ($enablenfo_main=='yes') {
    $nfofile = $_FILES['nfo'];
    if ($nfofile['name'] != '') {
        if ($nfofile['size'] == 0) {
            bark($lang_takeupload['std_zero_byte_nfo']);
        }

        if ($nfofile['size'] > 65535) {
            bark($lang_takeupload['std_nfo_too_big']);
        }

        $nfofilename = $nfofile['tmp_name'];

        if (@!is_uploaded_file($nfofilename)) {
            bark($lang_takeupload['std_nfo_upload_failed']);
        }
        $nfo = str_replace("\x0d\x0d\x0a", "\x0d\x0a", @file_get_contents($nfofilename));
    }
}


$small_descr = $_POST["small_descr"];

$descr = $_POST["descr"];
if (!$descr) {
    bark($lang_takeupload['std_blank_description']);
}

$catid = (0 + $_POST["type"]);
$sourceid = (0 + $_POST["source_sel"]);
$mediumid = (0 + $_POST["medium_sel"]);
$codecid = (0 + $_POST["codec_sel"]);
$standardid = (0 + $_POST["standard_sel"]);
$processingid = (0 + $_POST["processing_sel"]);
$teamid = (0 + $_POST["team_sel"]);
$audiocodecid = (0 + $_POST["audiocodec_sel"]);

if (!is_valid_id($catid)) {
    bark($lang_takeupload['std_category_unselected']);
}

if (!validfilename($fname)) {
    bark($lang_takeupload['std_invalid_filename']);
}
if (!preg_match('/^(.+)\.torrent$/si', $fname, $matches)) {
    bark($lang_takeupload['std_filename_not_torrent']);
}
$shortfname = $torrent = $matches[1];
if (!empty($_POST["name"])) {
    $torrent = $_POST["name"];
}
if ($f['size'] > $max_torrent_size) {
    bark($lang_takeupload['std_torrent_file_too_big'].number_format($max_torrent_size).$lang_takeupload['std_remake_torrent_note']);
}
$tmpname = $f["tmp_name"];
if (!is_uploaded_file($tmpname)) {
    bark("eek");
}
if (!filesize($tmpname)) {
    bark($lang_takeupload['std_empty_file']);
}

try {
    $dict = Bencode\Bencode::load($tmpname);
} catch (Bencode\ParseErrorException $e) {
    bark($lang_takeupload['std_not_bencoded_file']);
}

function checkTorrentDict($dict, $key, $type = null)
{
    global $lang_takeupload;

    if (!is_array($dict)) {
        bark($lang_takeupload['std_not_a_dictionary']);
    }
    $value = $dict[$key];
    if (!isset($value)) {
        bark($lang_takeupload['std_dictionary_is_missing_key']);
    }
    if (!is_null($type)) {
        $isFunction = 'is_' . $type;
        if (function_exists($isFunction) && !$isFunction($value)) {
            bark($lang_takeupload['std_invalid_entry_in_dictionary']);
        }
    }
    return $value;
}

$info = checkTorrentDict($dict, 'info');

// 屏蔽Bittorrent v2种子上传
if (isset($dict['piece layers']) || isset($info['files tree']) || (isset($info['meta version']) && $info['meta version'] == 2)) {
    bark('Torrent files created with Bittorrent Protocol v2, or hybrid torrents are not suppored.');
}

$plen = checkTorrentDict($info, 'piece length', 'integer');  // Only Check without use
$dname = checkTorrentDict($info, 'name', 'string');
$pieces = checkTorrentDict($info, 'pieces', 'string');

if (strlen($pieces) % 20 != 0) {
    bark($lang_takeupload['std_invalid_pieces']);
}

$filelist = array();
$totallen = $info['length'];
if (isset($totallen)) {
    $filelist[$dname] = $totallen;
    $type = "single";
} else {
    $flist = checkTorrentDict($info, 'files', 'array');

    if (!isset($flist)) {
        bark($lang_takeupload['std_missing_length_and_files']);
    }
    if (!count($flist)) {
        bark("no files");
    }

    $totallen = 0;
    foreach ($flist as $fn) {
        $ll = checkTorrentDict($fn, 'length', 'integer');
        $path_key = isset($fn['path.utf-8']) ? 'path.utf-8' : 'path';
        $ff = checkTorrentDict($fn, $path_key, 'list');

        $totallen += $ll;
        $ffa = array();
        foreach ($ff as $ffe) {
            if (!is_string($ffe)) {
                bark($lang_takeupload['std_filename_errors']);
            }
            $ffa[] = $ffe["value"];
        }

        if (!count($ffa)) {
            bark($lang_takeupload['std_filename_errors']);
        }
        $ffe = implode("/", $ffa);
        $filelist[$ffe] = $ll;
    }
    $type = "multi";
}

$dict['announce'] = get_protocol_prefix() . $announce_urls[0];  // change announce url to local
$dict['info']['private'] = 1;

//The following line requires uploader to re-download torrents after uploading
//even the torrent is set as private and with uploader's passkey in it.
$dict['info']['source'] = "[$BASEURL] $SITENAME";
unset($dict['announce-list']); // remove multi-tracker capability
unset($dict['nodes']); // remove cached peers (Bitcomet & Azareus)

$infohash = pack("H*", sha1(Bencode\Bencode::encode($dict['info'])));   // double up on the becoding solves the occassional misgenerated infohash

// ------------- start: check upload authority ------------------//
$allowtorrents = user_can_upload("torrents");
$allowspecial = user_can_upload("music");

$catmod = \NexusPHP\Components\Database::single("categories", "mode", "WHERE id=".\NexusPHP\Components\Database::escape($catid));
$offerid = $_POST['offer'];
$is_offer=false;
if ($browsecatmode != $specialcatmode && $catmod == $specialcatmode) {//upload to special section
    if (!$allowspecial) {
        bark($lang_takeupload['std_unauthorized_upload_freely']);
    }
} elseif ($catmod == $browsecatmode) {//upload to torrents section
    if ($offerid) {//it is a offer
        $allowed_offer_count = \NexusPHP\Components\Database::count("offers", "WHERE allowed='allowed' AND userid=".\NexusPHP\Components\Database::escape($CURUSER["id"]));
        if ($allowed_offer_count && $enableoffer == 'yes') {
            $allowed_offer = \NexusPHP\Components\Database::count("offers", "WHERE id=".\NexusPHP\Components\Database::escape($offerid)." AND allowed='allowed' AND userid=".\NexusPHP\Components\Database::escape($CURUSER["id"]));
            if ($allowed_offer != 1) {//user uploaded torrent that is not an allowed offer
                bark($lang_takeupload['std_uploaded_not_offered']);
            } else {
                $is_offer = true;
            }
        } else {
            bark($lang_takeupload['std_uploaded_not_offered']);
        }
    } elseif (!$allowtorrents) {
        bark($lang_takeupload['std_unauthorized_upload_freely']);
    }
} else { //upload to unknown section
    die("Upload to unknown section.");
}
// ------------- end: check upload authority ------------------//

// Replace punctuation characters with spaces

//$torrent = str_replace("_", " ", $torrent);

if ($largesize_torrent && $totallen > ($largesize_torrent * 1073741824)) { //Large Torrent Promotion
    switch ($largepro_torrent) {
        case 2: //Free
        {
            $sp_state = 2;
            break;
        }
        case 3: //2X
        {
            $sp_state = 3;
            break;
        }
        case 4: //2X Free
        {
            $sp_state = 4;
            break;
        }
        case 5: //Half Leech
        {
            $sp_state = 5;
            break;
        }
        case 6: //2X Half Leech
        {
            $sp_state = 6;
            break;
        }
        case 7: //30% Leech
        {
            $sp_state = 7;
            break;
        }
        default: //normal
        {
            $sp_state = 1;
            break;
        }
    }
} else { //ramdom torrent promotion
    $sp_id = mt_rand(1, 100);
    if ($sp_id <= ($probability = $randomtwoupfree_torrent)) { //2X Free
        $sp_state = 4;
    } elseif ($sp_id <= ($probability += $randomtwoup_torrent)) { //2X
        $sp_state = 3;
    } elseif ($sp_id <= ($probability += $randomfree_torrent)) { //Free
        $sp_state = 2;
    } elseif ($sp_id <= ($probability += $randomhalfleech_torrent)) { //Half Leech
        $sp_state = 5;
    } elseif ($sp_id <= ($probability += $randomtwouphalfdown_torrent)) { //2X Half Leech
        $sp_state = 6;
    } elseif ($sp_id <= ($probability += $randomthirtypercentdown_torrent)) { //30% Leech
        $sp_state = 7;
    } else {
        $sp_state = 1;
    } //normal
}

if ($altname_main == 'yes') {
    $cnname_part = trim($_POST["cnname"]);
    $size_part = str_replace(" ", "", mksize($totallen));
    $date_part = date("m.d.y");
    $category_part = \NexusPHP\Components\Database::single("categories", "name", "WHERE id = ".\NexusPHP\Components\Database::escape($catid));
    $torrent = "【".$date_part."】".($_POST["name"] ? "[".$_POST["name"]."]" : "").($cnname_part ? "[".$cnname_part."]" : "");
}

// some ugly code of automatically promoting torrents based on some rules
if ($prorules_torrent == 'yes') {
    foreach ($promotionrules_torrent as $rule) {
        if (!array_key_exists('catid', $rule) || in_array($catid, $rule['catid'])) {
            if (!array_key_exists('sourceid', $rule) || in_array($sourceid, $rule['sourceid'])) {
                if (!array_key_exists('mediumid', $rule) || in_array($mediumid, $rule['mediumid'])) {
                    if (!array_key_exists('codecid', $rule) || in_array($codecid, $rule['codecid'])) {
                        if (!array_key_exists('standardid', $rule) || in_array($standardid, $rule['standardid'])) {
                            if (!array_key_exists('processingid', $rule) || in_array($processingid, $rule['processingid'])) {
                                if (!array_key_exists('teamid', $rule) || in_array($teamid, $rule['teamid'])) {
                                    if (!array_key_exists('audiocodecid', $rule) || in_array($audiocodecid, $rule['audiocodecid'])) {
                                        if (!array_key_exists('pattern', $rule) || preg_match($rule['pattern'], $torrent)) {
                                            if (is_numeric($rule['promotion'])) {
                                                $sp_state = $rule['promotion'];
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

$ret = \NexusPHP\Components\Database::query("INSERT INTO torrents (filename, owner, visible, anonymous, name, size, numfiles, type, url, small_descr, descr, ori_descr, category, source, medium, codec, audiocodec, standard, processing, team, save_as, sp_state, added, last_action, nfo, info_hash) VALUES (".\NexusPHP\Components\Database::escape($fname).", ".\NexusPHP\Components\Database::escape($CURUSER["id"]).", 'yes', ".\NexusPHP\Components\Database::escape($anonymous).", ".\NexusPHP\Components\Database::escape($torrent).", ".\NexusPHP\Components\Database::escape($totallen).", ".count($filelist).", ".\NexusPHP\Components\Database::escape($type).", ".\NexusPHP\Components\Database::escape($url).", ".\NexusPHP\Components\Database::escape($small_descr).", ".\NexusPHP\Components\Database::escape($descr).", ".\NexusPHP\Components\Database::escape($descr).", ".\NexusPHP\Components\Database::escape($catid).", ".\NexusPHP\Components\Database::escape($sourceid).", ".\NexusPHP\Components\Database::escape($mediumid).", ".\NexusPHP\Components\Database::escape($codecid).", ".\NexusPHP\Components\Database::escape($audiocodecid).", ".\NexusPHP\Components\Database::escape($standardid).", ".\NexusPHP\Components\Database::escape($processingid).", ".\NexusPHP\Components\Database::escape($teamid).", ".\NexusPHP\Components\Database::escape($dname).", ".\NexusPHP\Components\Database::escape($sp_state) .
", " . \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s")) . ", " . \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s")) . ", ".\NexusPHP\Components\Database::escape($nfo).", " . \NexusPHP\Components\Database::escape($infohash). ")");
if (!$ret) {
    if (\NexusPHP\Components\Database::errno() == 1062) {
        bark($lang_takeupload['std_torrent_existed']);
    }
    bark("mysql puked: ".\NexusPHP\Components\Database::error());
    //bark("mysql puked: ".preg_replace_callback('/./s', "hex_esc2", \NexusPHP\Components\Database::error()));
}
$id = \NexusPHP\Components\Database::insert_id();

function getFileTree($array, $delimiter = '/')
{
    if (!is_array($array)) {
        return [];
    }

    $splitRE = '/' . preg_quote($delimiter, '/') . '/';
    $returnArr = [];
    foreach ($array as $key => $val) {
        // Get parent parts and the current leaf
        $parts = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
        $leafPart = array_pop($parts);

        // Build parent structure
        // Might be slow for really deep and large structures
        $parentArr = &$returnArr;
        foreach ($parts as $part) {
            if (!isset($parentArr[$part])) {
                $parentArr[$part] = [];
            } elseif (!is_array($parentArr[$part])) {
                $parentArr[$part] = [];
            }
            $parentArr = &$parentArr[$part];
        }

        // Add the final part to the structure
        if (empty($parentArr[$leafPart])) {
            $parentArr[$leafPart] = $val;
        }
    }
    return $returnArr;
}

$fileTreeJson = getFileTree($filelist);
if ($type === "multi") {
    $fileTreeJson = [$dname => $fileTreeJson];
}

\NexusPHP\Components\Database::query("INSERT INTO files (torrent, files) VALUES ($id, " . sqlesc(json_encode($fileTreeJson)) . ")");

Bencode\Bencode::dump("$torrent_dir/$id.torrent", $dict);

//===add karma
KPS("+", $uploadtorrent_bonus, $CURUSER["id"]);
//===end


write_log("Torrent $id ($torrent) was uploaded by $anon");

//===notify people who voted on offer thanks CoLdFuSiOn :)
if ($is_offer) {
    $res = \NexusPHP\Components\Database::query("SELECT `userid` FROM `offervotes` WHERE `userid` != " . $CURUSER["id"] . " AND `offerid` = ". \NexusPHP\Components\Database::escape($offerid)." AND `vote` = 'yeah'") or sqlerr(__FILE__, __LINE__);

    while ($row = mysqli_fetch_assoc($res)) {
        $pn_msg = $lang_takeupload_target[get_user_lang($row["userid"])]['msg_offer_you_voted'].$torrent.$lang_takeupload_target[get_user_lang($row["userid"])]['msg_was_uploaded_by']. $CURUSER["username"] .$lang_takeupload_target[get_user_lang($row["userid"])]['msg_you_can_download'] ."[url=" . get_protocol_prefix() . "$BASEURL/details.php?id=$id&hit=1]".$lang_takeupload_target[get_user_lang($row["userid"])]['msg_here']."[/url]";
        
        //=== use this if you DO have subject in your PMs
        $subject = $lang_takeupload_target[get_user_lang($row["userid"])]['msg_offer'].$torrent.$lang_takeupload_target[get_user_lang($row["userid"])]['msg_was_just_uploaded'];
        //=== use this if you DO NOT have subject in your PMs
        //$some_variable .= "(0, $row[userid], '" . date("Y-m-d H:i:s") . "', " . \NexusPHP\Components\Database::escape($pn_msg) . ")";

        //=== use this if you DO have subject in your PMs
        \NexusPHP\Components\Database::query("INSERT INTO messages (sender, subject, receiver, added, msg) VALUES (0, ".\NexusPHP\Components\Database::escape($subject).", $row[userid], ".\NexusPHP\Components\Database::escape(date("Y-m-d H:i:s")).", " . \NexusPHP\Components\Database::escape($pn_msg) . ")") or sqlerr(__FILE__, __LINE__);
        //=== use this if you do NOT have subject in your PMs
        //\NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, added, msg) VALUES ".$some_variable."") or sqlerr(__FILE__, __LINE__);
        //===end
    }
    //=== delete all offer stuff
    \NexusPHP\Components\Database::query("DELETE FROM offers WHERE id = ". $offerid);
    \NexusPHP\Components\Database::query("DELETE FROM offervotes WHERE offerid = ". $offerid);
    \NexusPHP\Components\Database::query("DELETE FROM comments WHERE offer = ". $offerid);
}
//=== end notify people who voted on offer

/* Email notifs */
if ($emailnotify_smtp=='yes' && $smtptype != 'none') {
    $cat = \NexusPHP\Components\Database::single("categories", "name", "WHERE id=".\NexusPHP\Components\Database::escape($catid));
    $res = \NexusPHP\Components\Database::query("SELECT id, email, lang FROM users WHERE enabled='yes' AND parked='no' AND status='confirmed' AND notifs LIKE '%[cat$catid]%' AND notifs LIKE '%[email]%' ORDER BY lang ASC") or sqlerr(__FILE__, __LINE__);

    $uploader = $anon;

    $size = mksize($totallen);

    $description = format_comment($descr);

    //dirty code, change later

    $langfolder_array = array("en", "chs", "cht", "ko", "ja");
    $body_arr = array("en" => "", "chs" => "", "cht" => "", "ko" => "", "ja" => "");
    $i = 0;
    foreach ($body_arr as $body) {
        $body_arr[$langfolder_array[$i]] = <<<EOD
{$lang_takeupload_target[$langfolder_array[$i]]['mail_hi']}

{$lang_takeupload_target[$langfolder_array[$i]]['mail_new_torrent']}

{$lang_takeupload_target[$langfolder_array[$i]]['mail_torrent_name']}$torrent
{$lang_takeupload_target[$langfolder_array[$i]]['mail_torrent_size']}$size
{$lang_takeupload_target[$langfolder_array[$i]]['mail_torrent_category']}$cat
{$lang_takeupload_target[$langfolder_array[$i]]['mail_torrent_uppedby']}$uploader

{$lang_takeupload_target[$langfolder_array[$i]]['mail_torrent_description']}
-------------------------------------------------------------------------------------------------------------------------
$description
-------------------------------------------------------------------------------------------------------------------------

{$lang_takeupload_target[$langfolder_array[$i]]['mail_torrent']}<b><a href="javascript:void(null)" onclick="window.open('http://$BASEURL/details.php?id=$id&hit=1')">{$lang_takeupload_target[$langfolder_array[$i]]['mail_here']}</a></b><br />
http://$BASEURL/details.php?id=$id&hit=1

------{$lang_takeupload_target[$langfolder_array[$i]]['mail_yours']}
{$lang_takeupload_target[$langfolder_array[$i]]['mail_team']}
EOD;

        $body_arr[$langfolder_array[$i]] = str_replace("<br />", "<br />", nl2br($body_arr[$langfolder_array[$i]]));
        $i++;
    }

    while ($arr = mysqli_fetch_array($res)) {
        $current_lang = $arr["lang"];
        $to = $arr["email"];

        sent_mail($to, $SITENAME, $SITEEMAIL, $lang_takeupload_target[validlang($current_lang)]['mail_title'].$torrent, $body_arr[validlang($current_lang)], "torrent upload", false, false, '', get_email_encode(validlang($current_lang)), "eYou");
    }
}

header("Location: " . get_protocol_prefix() . "$BASEURL/details.php?id=".htmlspecialchars($id)."&uploaded=1");
