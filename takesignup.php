<?php
require_once("include/bittorrent.php");
dbconn();
cur_user_check();
require_once(get_langfile_path("", true));
require_once(get_langfile_path("", false, get_langfolder_cookie()));

function bark($msg)
{
    global $lang_takesignup;
    stdhead();
    stdmsg($lang_takesignup['std_signup_failed'], $msg);
    stdfoot();
    exit;
}

$type = $_POST['type'];
if ($type == 'invite') {
    registration_check();
    failedloginscheck("Invite Signup");
    if ($iv == "yes") {
        check_code($_POST['imagehash'], $_POST['imagestring'], 'signup.php?type=invite&invitenumber='.htmlspecialchars($_POST['hash']));
    }
} else {
    registration_check("normal");
    failedloginscheck("Signup");
    if ($iv == "yes") {
        check_code($_POST['imagehash'], $_POST['imagestring']);
    }
}

function isportopen($port)
{
    $sd = @fsockopen($_SERVER["REMOTE_ADDR"], $port, $errno, $errstr, 1);
    if ($sd) {
        fclose($sd);
        return true;
    } else {
        return false;
    }
}

function isproxy()
{
    $ports = array(80, 88, 1075, 1080, 1180, 1182, 2282, 3128, 3332, 5490, 6588, 7033, 7441, 8000, 8080, 8085, 8090, 8095, 8100, 8105, 8110, 8888, 22788);
    for ($i = 0; $i < count($ports); ++$i) {
        if (isportopen($ports[$i])) {
            return true;
        }
    }
    return false;
}
if ($type=='invite') {
    $inviter =  $_POST["inviter"];
    int_check($inviter);
    $code = unesc($_POST["hash"]);

    //check invite code
    $sq = sprintf("SELECT inviter FROM invites WHERE hash ='%s'", \NexusPHP\Components\Database::real_escape_string($code));
    $res = \NexusPHP\Components\Database::query($sq) or sqlerr(__FILE__, __LINE__);
    $inv = mysqli_fetch_assoc($res);
    if (!$inv) {
        bark('invalid invite code');
    }

    $ip = getip();


    $res = \NexusPHP\Components\Database::query("SELECT username FROM users WHERE id = $inviter") or sqlerr(__FILE__, __LINE__);
    $arr = mysqli_fetch_assoc($res);
    $invusername = $arr[username];
}

if (!mkglobal("wantusername:wantpassword:passagain:email")) {
    die();
}

$email = htmlspecialchars(trim($email));
$email = safe_email($email);
if (!check_email($email)) {
    bark($lang_takesignup['std_invalid_email_address']);
}
    
if (EmailBanned($email)) {
    bark($lang_takesignup['std_email_address_banned']);
}

if (!EmailAllowed($email)) {
    bark($lang_takesignup['std_wrong_email_address_domains'].allowedemails());
}

$country = $_POST["country"];
    int_check($country);

if ($showschool == 'yes') {
    $school = $_POST["school"];
    int_check($school);
}

$gender =  htmlspecialchars(trim($_POST["gender"]));
$allowed_genders = array("Male","Female","male","female");
if (!in_array($gender, $allowed_genders, true)) {
    bark($lang_takesignup['std_invalid_gender']);
}
    
if (empty($wantusername) || empty($wantpassword) || empty($email) || empty($country) || empty($gender)) {
    bark($lang_takesignup['std_blank_field']);
}

    
if (strlen($wantusername) > 12) {
    bark($lang_takesignup['std_username_too_long']);
}

if ($wantpassword != $passagain) {
    bark($lang_takesignup['std_passwords_unmatched']);
}

if (strlen($wantpassword) < 6) {
    bark($lang_takesignup['std_password_too_short']);
}

if (strlen($wantpassword) > 40) {
    bark($lang_takesignup['std_password_too_long']);
}

if ($wantpassword == $wantusername) {
    bark($lang_takesignup['std_password_equals_username']);
}

if (!validemail($email)) {
    bark($lang_takesignup['std_wrong_email_address_format']);
}

if (!validusername($wantusername)) {
    bark($lang_takesignup['std_invalid_username']);
}
    
// make sure user agrees to everything...
if ($_POST["rulesverify"] != "yes" || $_POST["faqverify"] != "yes" || $_POST["ageverify"] != "yes") {
    stderr($lang_takesignup['std_signup_failed'], $lang_takesignup['std_unqualified']);
}

// check if email addy is already in use
$a = (@mysqli_fetch_row(@\NexusPHP\Components\Database::query("select count(*) from users where email='".\NexusPHP\Components\Database::real_escape_string($email)."'"))) or sqlerr(__FILE__, __LINE__);
if ($a[0] != 0) {
    bark($lang_takesignup['std_email_address'].$email.$lang_takesignup['std_in_use']);
}
  
/*
// do simple proxy check
if (isproxy())
    bark("You appear to be connecting through a proxy server. Your organization or ISP may use a transparent caching HTTP proxy. Please try and access the site on <a href="." . get_protocol_prefix() . "$BASEURL.":81/signup.php>port 81</a> (this should bypass the proxy server). <p><b>Note:</b> if you run an Internet-accessible web server on the local machine you need to shut it down until the sign-up is complete.");

$res = \NexusPHP\Components\Database::query("SELECT COUNT(*) FROM users") or sqlerr(__FILE__, __LINE__);
$arr = mysqli_fetch_row($res);
*/

$secret = mksecret();
$wantpasshash = md5($secret . $wantpassword . $secret);
$editsecret = ($verification == 'admin' ? '' : $secret);
$invite_count = (int) $invite_count;

$wantusername = \NexusPHP\Components\Database::escape($wantusername);
$wantpasshash = \NexusPHP\Components\Database::escape($wantpasshash);
$secret = \NexusPHP\Components\Database::escape($secret);
$editsecret = \NexusPHP\Components\Database::escape($editsecret);
$send_email = $email;
$email = \NexusPHP\Components\Database::escape($email);
$country = \NexusPHP\Components\Database::escape($country);
$gender = \NexusPHP\Components\Database::escape($gender);
$sitelangid = \NexusPHP\Components\Database::escape(get_langid_from_langcookie());

$res_check_user = \NexusPHP\Components\Database::query("SELECT * FROM users WHERE username = " . $wantusername);

if (mysqli_num_rows($res_check_user) == 1) {
    bark($lang_takesignup['std_username_exists']);
}

$ret = \NexusPHP\Components\Database::query("INSERT INTO users (username, passhash, secret, editsecret, email, country, gender, status, class, invites, ".($type == 'invite' ? "invited_by," : "")." added, last_access, lang, stylesheet".($showschool == 'yes' ? ", school" : "").", uploaded) VALUES (" . $wantusername . "," . $wantpasshash . "," . $secret . "," . $editsecret . "," . $email . "," . $country . "," . $gender . ", 'pending', ".$defaultclass_class.",". $invite_count .", ".($type == 'invite' ? "'$inviter'," : "") ." '". date("Y-m-d H:i:s") ."' , " . " '". date("Y-m-d H:i:s") ."' , ".$sitelangid . ",".$defcss.($showschool == 'yes' ? ",".$school : "").",".($iniupload_main > 0 ? $iniupload_main : 0).")") or sqlerr(__FILE__, __LINE__);
$id = \NexusPHP\Components\Database::insert_id();
$dt = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
$subject = \NexusPHP\Components\Database::escape($lang_takesignup['msg_subject'].$SITENAME."!");
$msg = \NexusPHP\Components\Database::escape($lang_takesignup['msg_congratulations'].htmlspecialchars($wantusername).$lang_takesignup['msg_you_are_a_member']);
\NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, added, msg) VALUES(0, $id, $subject, $dt, $msg)") or sqlerr(__FILE__, __LINE__);

//write_log("User account $id ($wantusername) was created");
$res = \NexusPHP\Components\Database::query("SELECT passhash, secret, editsecret, status FROM users WHERE id = ".\NexusPHP\Components\Database::escape($id)) or sqlerr(__FILE__, __LINE__);
$row = mysqli_fetch_assoc($res);
$psecret = md5($row['secret']);
$ip = getip();
$usern = htmlspecialchars($wantusername);
$title = $SITENAME.$lang_takesignup['mail_title'];
$body = <<<EOD
{$lang_takesignup['mail_one']}$usern{$lang_takesignup['mail_two']}($email){$lang_takesignup['mail_three']}$ip{$lang_takesignup['mail_four']}
<b><a href="javascript:void(null)" onclick="window.open('http://$BASEURL/confirm.php?id=$id&secret=$psecret')">
{$lang_takesignup['mail_this_link']} </a></b><br />
http://$BASEURL/confirm.php?id=$id&secret=$psecret
{$lang_takesignup['mail_four_1']}
<b><a href="javascript:void(null)" onclick="window.open('http://$BASEURL/confirm_resend.php')">{$lang_takesignup['mail_here']}</a></b><br />
http://$BASEURL/confirm_resend.php
<br />
{$lang_takesignup['mail_five']}
EOD;

if ($type == 'invite') {
    //don't forget to delete confirmed invitee's hash code from table invites
    \NexusPHP\Components\Database::query("DELETE FROM invites WHERE hash = '".\NexusPHP\Components\Database::real_escape_string($code)."'");
    $dt = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s"));
    $subject = \NexusPHP\Components\Database::escape($lang_takesignup_target[get_user_lang($inviter)]['msg_invited_user_has_registered']);
    $msg = \NexusPHP\Components\Database::escape($lang_takesignup_target[get_user_lang($inviter)]['msg_user_you_invited'].$usern.$lang_takesignup_target[get_user_lang($inviter)]['msg_has_registered']);
    //\NexusPHP\Components\Database::query("UPDATE users SET uploaded = uploaded + 10737418240 WHERE id = $inviter"); //add 10GB to invitor's uploading credit
    \NexusPHP\Components\Database::query("INSERT INTO messages (sender, receiver, subject, added, msg) VALUES(0, $inviter, $subject, $dt, $msg)") or sqlerr(__FILE__, __LINE__);
    $Cache->delete_value('user_'.$inviter.'_unread_message_count');
    $Cache->delete_value('user_'.$inviter.'_inbox_count');
}

if ($verification == 'admin') {
    if ($type == 'invite') {
        header("Location: " . get_protocol_prefix() . "$BASEURL/ok.php?type=inviter");
    } else {
        header("Location: " . get_protocol_prefix() . "$BASEURL/ok.php?type=adminactivate");
    }
} elseif ($verification == 'automatic' || $smtptype == 'none') {
    header("Location: " . get_protocol_prefix() . "$BASEURL/confirm.php?id=$id&secret=$psecret");
} else {
    sent_mail($send_email, $SITENAME, $SITEEMAIL, change_email_encode(get_langfolder_cookie(), $title), change_email_encode(get_langfolder_cookie(), $body), "signup", false, false, '', get_email_encode(get_langfolder_cookie()));
    header("Location: " . get_protocol_prefix() . "$BASEURL/ok.php?type=signup&email=" . rawurlencode($send_email));
}
