<?php
  require "include/bittorrent.php";
  $id = $_GET["id"];
  if (!is_numeric($id) || $id < 1 || floor($id) != $id) {
      die("Invalid ID");
  }

  $type = $_GET["type"];

  dbconn();
  require_once(get_langfile_path());
  loggedinorreturn();
  if ($type == 'in') {
      // make sure message is in CURUSER's Inbox
      $res = \NexusPHP\Components\Database::query("SELECT receiver, location FROM messages WHERE id=" . \NexusPHP\Components\Database::escape($id)) or die("barf");
      $arr = mysqli_fetch_array($res) or die($lang_deletemessage['std_bad_message_id']);
      if ($arr["receiver"] != $CURUSER["id"]) {
          die($lang_deletemessage['std_not_suggested']);
      }
      if ($arr["location"] == 'in') {
          \NexusPHP\Components\Database::query("DELETE FROM messages WHERE id=" . \NexusPHP\Components\Database::escape($id)) or die('delete failed (error code 1).. this should never happen, contact an admin.');
      } elseif ($arr["location"] == 'both') {
          \NexusPHP\Components\Database::query("UPDATE messages SET location = 'out' WHERE id=" . \NexusPHP\Components\Database::escape($id)) or die('delete failed (error code 2).. this should never happen, contact an admin.');
      } else {
          die($lang_deletemessage['std_not_in_inbox']);
      }
  } elseif ($type == 'out') {
        // make sure message is in CURUSER's Sentbox
        $res = \NexusPHP\Components\Database::query("SELECT sender, location FROM messages WHERE id=" . \NexusPHP\Components\Database::escape($id)) or die("barf");
        $arr = mysqli_fetch_array($res) or die($lang_deletemessage['std_bad_message_id']);
        if ($arr["sender"] != $CURUSER["id"]) {
            die($lang_deletemessage['std_not_suggested']);
        }
        if ($arr["location"] == 'out') {
            \NexusPHP\Components\Database::query("DELETE FROM messages WHERE id=" . \NexusPHP\Components\Database::escape($id)) or die('delete failed (error code 3).. this should never happen, contact an admin.');
        } elseif ($arr["location"] == 'both') {
            \NexusPHP\Components\Database::query("UPDATE messages SET location = 'in' WHERE id=" . \NexusPHP\Components\Database::escape($id)) or die('delete failed (error code 4).. this should never happen, contact an admin.');
        } else {
            die($lang_deletemessage['std_not_in_sentbox']);
        }
    } else {
      die($lang_deletemessage['std_unknown_pm_type']);
  }
  header("Location: " . get_protocol_prefix() . "$BASEURL/messages.php".($type == 'out'?"?out=1":""));
