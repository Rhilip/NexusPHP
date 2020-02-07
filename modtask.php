<?php
require "include/bittorrent.php";
dbconn();
require(get_langfile_path("", true));
loggedinorreturn();

function puke()
{
    $msg = "User ".$CURUSER["username"]." (id: ".$CURUSER["id"].") is hacking user's profile. IP : ".getip();
    write_log($msg, 'mod');
    stderr("Error", "Permission denied. For security reason, we logged this action");
}

if (get_user_class() < $prfmanage_class) {
    puke();
}

$action = $_POST["action"];
if ($action == "confirmuser") {
    $userid = $_POST["userid"];
    $confirm = $_POST["confirm"];
    \NexusPHP\Components\Database::query('UPDATE `users` SET `status` = \''.\NexusPHP\Components\Database::real_escape_string($confirm).'\', `info` = NULL WHERE `id` = '.\NexusPHP\Components\Database::real_escape_string($userid).' LIMIT 1;') or sqlerr(__FILE__, __LINE__);
    header("Location: " . get_protocol_prefix() . "$BASEURL/unco.php?status=1");
    die;
}
if ($action == "edituser") {
    $userid = $_POST["userid"];
    $class = 0 + $_POST["class"];
    $vip_added = ($_POST["vip_added"] == 'yes' ? 'yes' : 'no');
    $vip_until = ($_POST["vip_until"] ? $_POST["vip_until"] : '0000-00-00 00:00:00');
    
    $warned = $_POST["warned"];
    $warnlength = 0 + $_POST["warnlength"];
    $warnpm = $_POST["warnpm"];
    $title = $_POST["title"];
    $avatar = $_POST["avatar"];
    $signature = $_POST["signature"];

    $enabled = $_POST["enabled"];
    $uploadpos = $_POST["uploadpos"];
    $downloadpos = $_POST["downloadpos"];
    $noad = $_POST["noad"];
    $noaduntil = $_POST["noaduntil"];
    $privacy = $_POST["privacy"];
    $forumpost = $_POST["forumpost"];
    $chpassword = $_POST["chpassword"];
    $passagain = $_POST["passagain"];
    
    $supportlang = $_POST["supportlang"];
    $support = $_POST["support"];
    $supportfor = $_POST["supportfor"];
    
    $moviepicker = $_POST["moviepicker"];
    $pickfor = $_POST["pickfor"];
    $stafffor = $_POST["staffduties"];
    
    if (!is_valid_id($userid) || !is_valid_user_class($class)) {
        stderr("Error", "Bad user ID or class ID.");
    }
    if (get_user_class() <= $class) {
        stderr("Error", "You have no permission to change user's class to ".get_user_class_name($class, false, false, true).". BTW, how do you get here?");
    }
    $res = \NexusPHP\Components\Database::query("SELECT * FROM users WHERE id = ".\NexusPHP\Components\Database::escape($userid)) or sqlerr(__FILE__, __LINE__);
    $arr = mysqli_fetch_assoc($res) or puke();
    
    $curenabled = $arr["enabled"];
    $curparked = $arr["parked"];
    $curuploadpos = $arr["uploadpos"];
    $curdownloadpos = $arr["downloadpos"];
    $curforumpost = $arr["forumpost"];
    $curclass = $arr["class"];
    $curwarned = $arr["warned"];
    
    $updateset[] = "stafffor = " . \NexusPHP\Components\Database::escape($stafffor);
    $updateset[] = "pickfor = " . \NexusPHP\Components\Database::escape($pickfor);
    $updateset[] = "picker = " . \NexusPHP\Components\Database::escape($moviepicker);
    $updateset[] = "enabled = " . \NexusPHP\Components\Database::escape($enabled);
    $updateset[] = "uploadpos = " . \NexusPHP\Components\Database::escape($uploadpos);
    $updateset[] = "downloadpos = " . \NexusPHP\Components\Database::escape($downloadpos);
    $updateset[] = "forumpost = " . \NexusPHP\Components\Database::escape($forumpost);
    $updateset[] = "avatar = " . \NexusPHP\Components\Database::escape($avatar);
    $updateset[] = "signature = " . \NexusPHP\Components\Database::escape($signature);
    $updateset[] = "title = " . \NexusPHP\Components\Database::escape($title);
    $updateset[] = "support = " . \NexusPHP\Components\Database::escape($support);
    $updateset[] = "supportfor = " . \NexusPHP\Components\Database::escape($supportfor);
    $updateset[] = "supportlang = ".\NexusPHP\Components\Database::escape($supportlang);
    
    if (get_user_class<=$cruprfmanage_class) {
        $modcomment = $arr["modcomment"];
    }
    if (get_user_class() >= $cruprfmanage_class) {
        $email = $_POST["email"];
        $username = $_POST["username"];
        $modcomment = $_POST["modcomment"];
        $downloaded = $_POST["downloaded"];
        $ori_downloaded = $_POST["ori_downloaded"];
        $uploaded = $_POST["uploaded"];
        $ori_uploaded = $_POST["ori_uploaded"];
        $bonus = $_POST["bonus"];
        $ori_bonus = $_POST["ori_bonus"];
        $invites = $_POST["invites"];
        $added = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
        if ($arr['email'] != $email) {
            $updateset[] = "email = " . \NexusPHP\Components\Database::escape($email);
            $modcomment = date("Y-m-d") . " - Email changed from $arr[email] to $email by $CURUSER[username].\n". $modcomment;
            $subject = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_email_change']);
            $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_your_email_changed_from'].$arr['email'].$lang_modtask_target[get_user_lang($userid)]['msg_to_new'] . $email .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER[username]);
            \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, msg, added) VALUES(0, $userid, $subject, $msg, $added)") or sqlerr(__FILE__, __LINE__);
        }
        if ($arr['username'] != $username) {
            $updateset[] = "username = " . \NexusPHP\Components\Database::escape($username);
            $modcomment = date("Y-m-d") . " - Usernmae changed from $arr[username] to $username by $CURUSER[username].\n". $modcomment;
            $subject = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_username_change']);
            $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_your_username_changed_from'].$arr['username'].$lang_modtask_target[get_user_lang($userid)]['msg_to_new'] . $username .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER[username]);
            \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, msg, added) VALUES(0, $userid, $subject, $msg, $added)") or sqlerr(__FILE__, __LINE__);
        }
        if ($ori_downloaded != $downloaded) {
            $updateset[] = "downloaded = " . \NexusPHP\Components\Database::escape($downloaded);
            $modcomment = date("Y-m-d") . " - Downloaded amount changed from $arr[downloaded] to $downloaded by $CURUSER[username].\n". $modcomment;
            $subject = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_downloaded_change']);
            $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_your_downloaded_changed_from'].mksize($arr['downloaded']).$lang_modtask_target[get_user_lang($userid)]['msg_to_new'] . mksize($downloaded) .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER[username]);
            \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, msg, added) VALUES(0, $userid, $subject, $msg, $added)") or sqlerr(__FILE__, __LINE__);
        }
        if ($ori_uploaded != $uploaded) {
            $updateset[] = "uploaded = " . \NexusPHP\Components\Database::escape($uploaded);
            $modcomment = date("Y-m-d") . " - Uploaded amount changed from $arr[uploaded] to $uploaded by $CURUSER[username].\n". $modcomment;
            $subject = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_uploaded_change']);
            $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_your_uploaded_changed_from'].mksize($arr['uploaded']).$lang_modtask_target[get_user_lang($userid)]['msg_to_new'] . mksize($uploaded) .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER[username]);
            \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, msg, added) VALUES(0, $userid, $subject, $msg, $added)") or sqlerr(__FILE__, __LINE__);
        }
        if ($ori_bonus != $bonus) {
            $updateset[] = "seedbonus = " . \NexusPHP\Components\Database::escape($bonus);
            $modcomment = date("Y-m-d") . " - Bonus amount changed from $arr[seedbonus] to $bonus by $CURUSER[username].\n". $modcomment;
            $subject = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_bonus_change']);
            $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_your_bonus_changed_from'].$arr['seedbonus'].$lang_modtask_target[get_user_lang($userid)]['msg_to_new'] . $bonus .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER[username]);
            \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, msg, added) VALUES(0, $userid, $subject, $msg, $added)") or sqlerr(__FILE__, __LINE__);
        }
        if ($arr['invites'] != $invites) {
            $updateset[] = "invites = " . \NexusPHP\Components\Database::escape($invites);
            $modcomment = date("Y-m-d") . " - Invite amount changed from $arr[invites] to $invites by $CURUSER[username].\n". $modcomment;
            $subject = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_invite_change']);
            $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_your_invite_changed_from'].$arr['invites'].$lang_modtask_target[get_user_lang($userid)]['msg_to_new'] . $invites .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER[username]);
            \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, msg, added) VALUES(0, $userid, $subject, $msg, $added)") or sqlerr(__FILE__, __LINE__);
        }
    }
    if (get_user_class() == UC_STAFFLEADER) {
        $donor = $_POST["donor"];
        $donated = $_POST["donated"];
        $donated_cny = $_POST["donated_cny"];
        $this_donated_usd = $donated - $arr["donated"];
        $this_donated_cny = $donated_cny - $arr["donated_cny"];
        $memo = \NexusPHP\Components\Database::escape(htmlspecialchars($_POST["donation_memo"]));
        
        if ($donated != $arr[donated] || $donated_cny != $arr[donated_cny]) {
            $added = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
            \NexusPHP\Components\Database::query("INSERT INTO funds (usd, cny, user, added, memo) VALUES ($this_donated_usd, $this_donated_cny, $userid, $added, $memo)") or sqlerr(__FILE__, __LINE__);
            $updateset[] = "donated = " . \NexusPHP\Components\Database::escape($donated);
            $updateset[] = "donated_cny = " . \NexusPHP\Components\Database::escape($donated_cny);
        }

        $updateset[] = "donor = " . \NexusPHP\Components\Database::escape($donor);
    }
    
    if ($chpassword != "" and $passagain != "") {
        unset($passupdate);
        $passupdate=false;
        
        if ($chpassword ==  $username or strlen($chpassword) > 40 or strlen($chpassword) < 6 or $chpassword != $passagain) {
            $passupdate=false;
        } else {
            $passupdate=true;
        }
    }
    
    if ($passupdate) {
        $sec = mksecret();
        $passhash = md5($sec . $chpassword . $sec);
        $updateset[] = "secret = " . \NexusPHP\Components\Database::escape($sec);
        $updateset[] = "passhash = " . \NexusPHP\Components\Database::escape($passhash);
    }

    if ($curclass >= get_user_class()) {
        puke();
    }

    if ($curclass != $class) {
        $what = ($class > $curclass ? $lang_modtask_target[get_user_lang($userid)]['msg_promoted'] : $lang_modtask_target[get_user_lang($userid)]['msg_demoted']);
        $subject = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_class_change']);
        $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_you_have_been'].$what.$lang_modtask_target[get_user_lang($userid)]['msg_to'] . get_user_class_name($class) .$lang_modtask_target[get_user_lang($userid)]['msg_by'].$CURUSER[username]);
        $added = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
        \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, msg, added) VALUES(0, $userid, $subject, $msg, $added)") or sqlerr(__FILE__, __LINE__);
        $updateset[] = "class = $class";
        $what = ($class > $curclass ? "Promoted" : "Demoted");
        $modcomment = date("Y-m-d") . " - $what to '" . get_user_class_name($class) . "' by $CURUSER[username].\n". $modcomment;
    }
    if ($class == UC_VIP) {
        $updateset[] = "vip_added = ".\NexusPHP\Components\Database::escape($vip_added);
        if ($vip_added == 'yes') {
            $updateset[] = "vip_until = ".\NexusPHP\Components\Database::escape($vip_until);
        }
        $subject = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_your_vip_status_changed']);
        $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_vip_status_changed_by'].$CURUSER[username]);
        $added = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
        \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, msg, added) VALUES (0, $userid, $subject, $msg, $added)") or sqlerr(__FILE__, __LINE__);
        $modcomment = date("Y-m-d") . " - VIP status changed by $CURUSER[username]. VIP added: ".$vip_added.($vip_added == 'yes' ? "; VIP until: ".$vip_until : "").".\n". $modcomment;
    }
    
    if ($warned && $curwarned != $warned) {
        $updateset[] = "warned = " . \NexusPHP\Components\Database::escape($warned);
        $updateset[] = "warneduntil = '0000-00-00 00:00:00'";

        if ($warned == 'no') {
            $modcomment = date("Y-m-d") . " - Warning removed by $CURUSER[username].\n". $modcomment;
            $subject = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_warn_removed']);
            $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_your_warning_removed_by'] . $CURUSER['username'] . ".");
        }

        $added = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
        \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, msg, added) VALUES (0, $userid, $subject, $msg, $added)") or sqlerr(__FILE__, __LINE__);
    } elseif ($warnlength) {
        if ($warnlength == 255) {
            $modcomment = date("Y-m-d") . " - Warned by " . $CURUSER['username'] . ".\nReason: $warnpm.\n". $modcomment;
            $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_you_are_warned_by'].$CURUSER[username]."." . ($warnpm ? $lang_modtask_target[get_user_lang($userid)]['msg_reason'].$warnpm : ""));
            $updateset[] = "warneduntil = '0000-00-00 00:00:00'";
        } else {
            $warneduntil = date("Y-m-d H:i:s", (strtotime(date("Y-m-d H:i:s")) + $warnlength * 604800));
            $dur = $warnlength . $lang_modtask_target[get_user_lang($userid)]['msg_week'] . ($warnlength > 1 ? $lang_modtask_target[get_user_lang($userid)]['msg_s'] : "");
            $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_you_are_warned_for'].$dur.$lang_modtask_target[get_user_lang($userid)]['msg_by']  . $CURUSER['username'] . "." . ($warnpm ? $lang_modtask_target[get_user_lang($userid)]['msg_reason'].$warnpm : ""));
            $modcomment = date("Y-m-d") . " - Warned for $dur by " . $CURUSER['username'] .  ".\nReason: $warnpm.\n". $modcomment;
            $updateset[] = "warneduntil = '$warneduntil'";
        }
        $subject = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_you_are_warned']);
        $added = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
        \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, msg, added) VALUES (0, $userid, $subject, $msg, $added)") or sqlerr(__FILE__, __LINE__);
        $updateset[] = "warned = 'yes', timeswarned = timeswarned+1, lastwarned=$added, warnedby=$CURUSER[id]";
    }
    if ($enabled != $curenabled) {
        if ($enabled == 'yes') {
            $modcomment = date("Y-m-d") . " - Enabled by " . $CURUSER['username']. ".\n". $modcomment;
            if (\NexusPHP\Components\Database::single("users", "class", "WHERE id = ".\NexusPHP\Components\Database::escape($userid)) == UC_PEASANT) {
                $length = 30*86400; // warn users until 30 days
                $until = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s", (strtotime(date("Y-m-d H:i:s")) + $length)));
                \NexusPHP\Components\Database::query("UPDATE users SET enabled='yes', leechwarn='yes', leechwarnuntil=$until WHERE id = ".\NexusPHP\Components\Database::escape($userid));
            } else {
                \NexusPHP\Components\Database::query("UPDATE users SET enabled='yes', leechwarn='no' WHERE id = ".\NexusPHP\Components\Database::escape($userid)) or sqlerr(__FILE__, __LINE__);
            }
        } else {
            $modcomment = date("Y-m-d") . " - Disabled by " . $CURUSER['username']. ".\n". $modcomment;
        }
    }
    if ($arr['noad'] != $noad) {
        $updateset[]='noad = '.\NexusPHP\Components\Database::escape($noad);
        $modcomment = date("Y-m-d") . " - No Ad set to ".$noad." by ". $CURUSER['username']. ".\n". $modcomment;
    }
    if ($arr['noaduntil'] != $noaduntil) {
        $updateset[]='noaduntil = '.\NexusPHP\Components\Database::escape($noaduntil);
        $modcomment = date("Y-m-d") . " - No Ad Until set to ".$noaduntil." by ". $CURUSER['username']. ".\n". $modcomment;
    }
    if ($privacy == "low" or $privacy == "normal" or $privacy == "strong") {
        $updateset[] = "privacy = " . \NexusPHP\Components\Database::escape($privacy);
    }
    
    if ($_POST["resetkey"] == "yes") {
        $newpasskey = md5($arr['username'].date("Y-m-d H:i:s").$arr['passhash']);
        $updateset[] = "passkey = ".\NexusPHP\Components\Database::escape($newpasskey);
    }
    if ($forumpost != $curforumpost) {
        if ($forumpost == 'yes') {
            $modcomment = date("Y-m-d") . " - Posting enabled by " . $CURUSER['username'] . ".\n" . $modcomment;
            $subject = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_posting_rights_restored']);
            $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_your_posting_rights_restored']. $CURUSER['username'] . $lang_modtask_target[get_user_lang($userid)]['msg_you_can_post']);
            $added = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
            \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, msg, added) VALUES (0, $userid, $subject, $msg, $added)") or sqlerr(__FILE__, __LINE__);
        } else {
            $modcomment = date("Y-m-d") . " - Posting disabled by " . $CURUSER['username'] . ".\n" . $modcomment;
            $subject = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_posting_rights_removed']);
            $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_your_posting_rights_removed'] . $CURUSER['username'] . $lang_modtask_target[get_user_lang($userid)]['msg_probable_reason']);
            $added = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
            \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, msg, added) VALUES (0, $userid, $subject, $msg, $added)") or sqlerr(__FILE__, __LINE__);
        }
    }
    if ($uploadpos != $curuploadpos) {
        if ($uploadpos == 'yes') {
            $modcomment = date("Y-m-d") . " - Upload enabled by " . $CURUSER['username'] . ".\n" . $modcomment;
            $subject = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_upload_rights_restored']);
            $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_your_upload_rights_restored'] . $CURUSER['username'] . $lang_modtask_target[get_user_lang($userid)]['msg_you_upload_can_upload']);
            $added = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
            \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, msg, added) VALUES (0, $userid, $subject, $msg, $added)") or sqlerr(__FILE__, __LINE__);
        } else {
            $modcomment = date("Y-m-d") . " - Upload disabled by " . $CURUSER['username'] . ".\n" . $modcomment;
            $subject = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_upload_rights_removed']);
            $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_your_upload_rights_removed'] . $CURUSER['username'] . $lang_modtask_target[get_user_lang($userid)]['msg_probably_reason_two']);
            $added = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
            \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, msg, added) VALUES (0, $userid, $subject, $msg, $added)") or sqlerr(__FILE__, __LINE__);
        }
    }
    if ($downloadpos != $curdownloadpos) {
        if ($downloadpos == 'yes') {
            $modcomment = date("Y-m-d") . " - Download enabled by " . $CURUSER['username'] . ".\n" . $modcomment;
            $subject = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_download_rights_restored']);
            $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_your_download_rights_restored']. $CURUSER['username'] . $lang_modtask_target[get_user_lang($userid)]['msg_you_can_download']);
            $added = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
            \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, msg, added) VALUES (0, $userid, $subject, $msg, $added)") or sqlerr(__FILE__, __LINE__);
        } else {
            $modcomment = date("Y-m-d") . " - Download disabled by " . $CURUSER['username'] . ".\n" . $modcomment;
            $subject = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_download_rights_removed']);
            $msg = \NexusPHP\Components\Database::escape($lang_modtask_target[get_user_lang($userid)]['msg_your_download_rights_removed'] . $CURUSER['username'] . $lang_modtask_target[get_user_lang($userid)]['msg_probably_reason_three']);
            $added = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
            \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, msg, added) VALUES (0, $userid, $subject, $msg, $added)") or sqlerr(__FILE__, __LINE__);
        }
    }
    
    $updateset[] = "modcomment = " . \NexusPHP\Components\Database::escape($modcomment);
    
    \NexusPHP\Components\Database::query("UPDATE users SET  " . implode(", ", $updateset) . " WHERE id=$userid") or sqlerr(__FILE__, __LINE__);

    $returnto = htmlspecialchars($_POST["returnto"]);
    header("Location: " . get_protocol_prefix() . "$BASEURL/$returnto");
    die;
}
puke();
