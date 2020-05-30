<?php
if (!defined('IN_TRACKER')) {
    die('Hacking attempt!');
}

function get_global_sp_state()
{
    global $Cache;
    static $global_promotion_state;
    if (!$global_promotion_state) {
        if (!$global_promotion_state = $Cache->get_value('global_promotion_state')) {
            $res = \NexusPHP\Components\Database::query("SELECT * FROM torrents_state");
            $row = mysqli_fetch_assoc($res);
            $global_promotion_state = $row["global_sp_state"];
            $Cache->cache_value('global_promotion_state', $global_promotion_state, 57226);
        }
    }
    return $global_promotion_state;
}

// IP Validation
function validip($ip)
{
    /**
     * FILTER_FLAG_NO_PRIV_RANGE :  private IPv4 ranges: 10.0.0.0/8, 172.16.0.0/12 and 192.168.0.0/16
     *                              the IPv6 addresses starting with FD or FC.
     *
     * FILTER_FLAG_NO_RES_RANGE : reserved IPv4 ranges: 0.0.0.0/8, 169.254.0.0/16, 127.0.0.0/8 and 240.0.0.0/4.
     *                            reserved IPv6 ranges: ::1/128, ::/128, ::ffff:0:0/96 and fe80::/10
     */
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
}

function getip()
{
    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && validip($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && validip($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } else {
        if (getenv('HTTP_X_FORWARDED_FOR') && validip(getenv('HTTP_X_FORWARDED_FOR'))) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENT_IP') && validip(getenv('HTTP_CLIENT_IP'))) {
            $ip = getenv('HTTP_CLIENT_IP');
        } else {
            $ip = getenv('REMOTE_ADDR');
        }
    }

    return $ip;
}

function hash_pad($hash)
{
    return str_pad($hash, 20);
}

function hash_where($name, $hash)
{
    $shhash = preg_replace('/ *$/s', "", $hash);
    return "($name = " . \NexusPHP\Components\Database::escape($hash) . " OR $name = " . \NexusPHP\Components\Database::escape($shhash) . ")";
}
