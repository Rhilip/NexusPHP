<?php

namespace NexusPHP;

class Advertisement
{
    public $userid;
    public $showad;
    public $userrow = array();
    public $adrow = array();

    public function __construct($userid)
    {
        $this->userid = $userid;
        $this->set_userrow();
        $this->set_showad();
        $this->set_adrow();
    }

    public function set_userrow()
    {
        $userid = $this->userid;
        $row = get_user_row($userid);
        $this->userrow = $row;
    }

    public function enable_ad()
    {
        global $enablead_advertisement;
        if ($enablead_advertisement == 'yes') {
            return true;
        } else {
            return false;
        }
    }

    public function show_ad()
    {
        if (!$this->enable_ad()) {
            return false;
        } else {
            if ($this->userrow['noad'] == 'yes') {
                return false;
            } else {
                return true;
            }
        }
    }

    public function set_showad()
    {
        $showad = $this->show_ad();
        $this->showad = $showad;
    }

    public function set_adrow()
    {
        global $Cache;
        if (!$arr = $Cache->get_value('current_ad_array')) {
            $now = date("Y-m-d H:i:s");
            $validpos = $this->get_validpos();
            foreach ($validpos as $pos) {
                $res = 	sql_query("SELECT code FROM advertisements WHERE enabled=1 AND position=".sqlesc($pos)." AND (starttime IS NULL OR starttime < ".sqlesc($now).") AND (endtime IS NULL OR endtime > ".sqlesc($now).") ORDER BY displayorder ASC, id DESC LIMIT 10") or sqlerr(__FILE__, __LINE__);
                $adarray = array();
                while ($row = mysql_fetch_array($res)) {
                    $adarray[]=$row['code'];
                }
                $arr[$pos]=$adarray;
            }
            $Cache->cache_value('current_ad_array', $arr, 3600);
        }
        $this->adrow=$arr;
    }

    public function get_validpos()
    {
        return array('header', 'footer', 'belownav', 'belowsearchbox', 'torrentdetail', 'comment', 'interoverforums', 'forumpost', 'popup');
    }
    public function get_ad($pos)
    {
        $validpos = $this->get_validpos();
        if (in_array($pos, $validpos) && $this->showad) {
            return $this->adrow[$pos];
        } else {
            return "";
        }
    }
}
