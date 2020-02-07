<?php
require_once('include/bittorrent_announce.php');
dbconn_announce();

// BLOCK ACCESS WITH WEB BROWSERS AND CHEATS!
block_browser();

preg_match_all('/info_hash=([^&]*)/i', $_SERVER["QUERY_STRING"], $info_hash_array);
$fields = "info_hash, times_completed, seeders, leechers";

if (count($info_hash_array[1]) < 1) {
    $query = "SELECT $fields FROM torrents ORDER BY id";
} else {
    $query = "SELECT $fields FROM torrents WHERE " . hash_where_arr('info_hash', $info_hash_array[1]);
}

$res = \NexusPHP\Components\Database::query($query);
if (mysqli_num_rows($res) < 1) {
    err("Torrent not registered with this tracker.");
} else {
    $torrent_details = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $torrent_details[$row['info_hash']] = [
            'complete' => (int)$row['seeders'],
            'downloaded' => (int)$row['times_completed'],
            'incomplete' => (int)$row['leechers']
        ];
    }

    $d = ['files' => $torrent_details];
    benc_resp($d);
}
