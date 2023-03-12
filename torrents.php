<?php
require_once("include/bittorrent.php");
dbconn(true);
require_once(get_langfile_path("torrents.php"));
loggedinorreturn();
parked();
if ($showextinfo['imdb'] == 'yes') {
    require_once("imdb/imdb.class.php");
}

$addparams = [];  // 用于生成 分页所需要的 params
$wherea = [];  // 用于生成SQL语句

// 1. 最先处理分类相关的字段
$sectiontype = $browsecatmode;
$catsperrow = get_searchbox_value($sectiontype, 'catsperrow'); //show how many cats per line in search box
$catpadding = get_searchbox_value($sectiontype, 'catpadding'); //padding space between categories in pixel

/**
 * 定义格式如下： Record<K, {notifs: string, items: Array<{id: number, name: string}>, tk?: string, lang?: string}>
 * 其中 K 为 $_GET中对应的字段， notifs 为 $CURUSER['notifs'] 对应字段， items 为一个至少带有 id, name 的list,
 *     tk 为 构建SQL 时对应的torrents表字段，如果不存在则使用K
 *     lang 为构造html 时对应的i18n key，如果不存在则使用 `text_${K}`
 *
 * NOTICE:
 * 1. 本处的实现可能过于简化，但基本满足NPHP默认的展示要求
 * 2. 此处依然可以进一步简化，因为 notifs 目前永远是对应字段的前3个字符
 * 3. 此处生成的 $all_classification 可以被缓存，进一步减少io
 */
$all_classification = [
    'cat' => ['notifs' => 'cat', 'items' => genrelist($sectiontype), 'tk' => 'category', 'lang' => 'text_category']
];
$classification_gets = [];   // 拿来缓存我们实际获取到的cat值

$showsubcat = get_searchbox_value($sectiontype, 'showsubcat');//whether show subcategory (i.e. sources, codecs) or not
if ($showsubcat) {
    if (get_searchbox_value($sectiontype, 'showsource')) {  //whether show sources or not
        $all_classification['source'] = ['notifs' => 'sou', 'items' => searchbox_item_list("sources")];
    }
    if (get_searchbox_value($sectiontype, 'showmedium')) {  //whether show media or not
        $all_classification['medium'] = ['notifs' => 'med', 'items' => searchbox_item_list("media")];
    }
    if (get_searchbox_value($sectiontype, 'showcodec')) {  //whether show codecs or not
        $all_classification['codec'] = ['notifs' => 'cod', 'items' => searchbox_item_list("codecs")];
    }
    if (get_searchbox_value($sectiontype, 'showstandard')) {  //whether show codecs or not
        $all_classification['standard'] = ['notifs' => 'sta', 'items' => searchbox_item_list("standards")];
    }
    if (get_searchbox_value($sectiontype, 'showprocessing')) {  //whether show processings or not
        $all_classification['processing'] = ['notifs' => 'pro', 'items' => searchbox_item_list("processings")];
    }
    if (get_searchbox_value($sectiontype, 'showteam')) {  //whether show team or not
        $all_classification['team'] = ['notifs' => 'tea', 'items' => searchbox_item_list("processings")];
    }
    if (get_searchbox_value($sectiontype, 'showaudiocodec')) {  //whether show team or not
        $all_classification['audiocodecs'] = ['notifs' => 'aud', 'items' => searchbox_item_list("audiocodecs"), 'lang' => 'text_audio_codec'];
    }
}

$all = 0 + $_GET["all"];  // 存在 &all=1 时不应该考虑cat以及subcat的取值

if (!$all) {
    function filter_classification_input($field): array
    {
        global $CURUSER, $all_subcats;

        $field_details = $all_subcats[$field];
        $valid_field_ids = array_column($field_details['items'], 'id');

        $input_field_value = filter_input(INPUT_GET, $field, FILTER_VALIDATE_INT, FILTER_FORCE_ARRAY);
        if (is_null($input_field_value)) {
            $input_field_value = [];
            $notifs = $field_details['notifs'];
            if ($notifs && !empty($CURUSER['notifs'])) {
                foreach ($valid_field_ids as $valid_field_id) {
                    if (strpos($CURUSER['notifs'], '[' . $notifs . $valid_field_id . ']') !== false) {
                        $input_field_value[] = $valid_field_id;
                    }
                }
            }
        } else {
            $input_field_value = array_values(array_intersect($valid_field_ids, $input_field_value));
        }

        return $input_field_value;
    }

    foreach ($all_classification as $key => $value) {
        $classification_gets[$key] = $class_get = filter_classification_input($key);
        if (count($class_get) > 0) {
            foreach ($class_get as $class) {
                $addparams[] = $key . "[]=" . $class;
            }
            $wherea[] = ($value['tk'] ?? $key) . "IN (" . implode(',', $class_get) . ")";
        }
    }
}

$searchstr_ori = htmlspecialchars(trim($_GET["search"]));
$searchstr = \NexusPHP\Components\Database::real_escape_string(trim($_GET["search"]));
if (empty($searchstr)) {
    unset($searchstr);
}

// sorting by MarkoStamcar
if ($_GET['sort'] && $_GET['type']) {
    $column = '';
    $ascdesc = '';

    switch ($_GET['sort']) {
        case '1': $column = "name"; break;
        case '2': $column = "numfiles"; break;
        case '3': $column = "comments"; break;
        case '4': $column = "added"; break;
        case '5': $column = "size"; break;
        case '6': $column = "times_completed"; break;
        case '7': $column = "seeders"; break;
        case '8': $column = "leechers"; break;
        case '9': $column = "owner"; break;
        default: $column = "id"; break;
    }

    switch ($_GET['type']) {
        case 'asc': $ascdesc = "ASC"; $linkascdesc = "asc"; break;
        case 'desc': $ascdesc = "DESC"; $linkascdesc = "desc"; break;
        default: $ascdesc = "DESC"; $linkascdesc = "desc"; break;
    }

    if ($column == "owner") {
        $orderby = "ORDER BY pos_state DESC, torrents.anonymous, users.username " . $ascdesc;
    } else {
        $orderby = "ORDER BY pos_state DESC, torrents." . $column . " " . $ascdesc;
    }

    $addparams[] = "sort=" . intval($_GET['sort']);
    $addparams[] = "type=" . $linkascdesc;
} else {
    $orderby = "ORDER BY pos_state DESC, torrents.id DESC";
}


/**
 * @param string $field
 * @param int $default
 * @param array|null $allowed_values
 * @param string|null $notifs
 * @param bool $log
 * @return int
 */
function filter_int_input(string $field, int $default = 0, array $allowed_values = null, string $notifs = null, bool $log = true): int
{
    global $CURUSER;

    $value = filter_input(INPUT_GET, $field, FILTER_VALIDATE_INT);
    if (is_null($value)) {  // field is not exist or is not int
        if ($notifs && !empty($CURUSER['notifs'])) {
            foreach ($allowed_values as $valid_field_id) {
                if (strpos($CURUSER['notifs'], '[' . $notifs . '=' . $valid_field_id . ']') !== false) {
                    $value = $valid_field_id;
                    break;
                }
            }
        }
    }

    if (is_null($value) || $value === false) {  // field value is still not exist
        $value = $default;
    }

    if ($allowed_values && !in_array($value, $allowed_values)) {
        $value = $default;
        $log && write_log("User " . ($CURUSER["username"] ?? $CURUSER['id']) . "," . $CURUSER["ip"] . " is hacking {$field} field", 'mod');
    }

    return $value;
}

// ----------------- start bookmarked ---------------------//
if (isset($CURUSER)) {
    $inclbookmarked = filter_int_input('inclbookmarked', 0, [0, 1, 2], 'inclbookmarked');
    $addparams[] = 'inclbookmarked=' . $inclbookmarked;

    if ($inclbookmarked == 1) {        //bookmarked
        $wherea[] = "torrents.id IN (SELECT torrentid FROM bookmarks WHERE userid=" . $CURUSER['id'] . ")";
    } elseif ($inclbookmarked == 2) {        //not bookmarked
        $wherea[] = "torrents.id NOT IN (SELECT torrentid FROM bookmarks WHERE userid=" . $CURUSER['id'] . ")";
    }
}

// ----------------- end bookmarked ---------------------//

if (!isset($CURUSER) || get_user_class() < $seebanned_class) {
    $wherea[] = "banned != 'yes'";
}

// ----------------- start include dead ---------------------//
$include_dead = filter_int_input('incldead', 1, [0 /* all(active, dead) */, 1 /* active */, 2 /* dead */], 'incldead');
$addparams[] = "incldead=" . $include_dead;

if ($include_dead == 1) {        //active
    $wherea[] = "visible = 'yes'";
} elseif ($include_dead == 2) {        //dead
    $wherea[] = "visible = 'no'";
}
// ----------------- end include dead ---------------------//
$special_state = filter_int_input('spstate', 0, range(0, 7), 'spstate');

$addparams[] = "spstate=" . $special_state;
if ($special_state != 0 &&  get_global_sp_state() == 1) {
    $wherea[] = "sp_state = " . $special_state;
}

if (isset($searchstr)) {
    if (!$_GET['notnewword']) {
        insert_suggest($searchstr, $CURUSER['id']);
    } else {
        $addparams[] = "notnewword=1";
    }

    $search_mode = filter_int_input('search_mode', 0, [0, 1, 2]);
    $search_area = filter_int_input('search_area', 0, [0, 1, 3, 4]);

    if ($search_area == 4) {
        $searchstr = (int)parse_imdb_id($searchstr);
    }

    $like_expression_array = [];
    switch ($search_mode) {
        case 0:    // AND, OR
        case 1:
        {
            $searchstr = str_replace(".", " ", $searchstr);
            $searchstr_exploded = explode(" ", $searchstr);
            $searchstr_exploded_count = 0;
            foreach ($searchstr_exploded as $searchstr_element) {
                $searchstr_element = trim($searchstr_element);    // furthur trim to ensure that multi space seperated words still work
                $searchstr_exploded_count++;
                if ($searchstr_exploded_count > 10) {    // maximum 10 keywords
                    break;
                }
                $like_expression_array[] = " LIKE '%" . $searchstr_element . "%'";
            }
            break;
        }
        case 2:    // exact
        {
            $like_expression_array[] = " LIKE '%" . $searchstr . "%'";
            break;
        }
        /*case 3 :	// parsed
        {
        $like_expression_array[] = $searchstr;
        break;
        }*/
    }
    $ANDOR = ($search_mode == 0 ? " AND " : " OR ");    // only affects mode 0 and mode 1

    switch ($search_area) {
        case 0:    // torrent name
        {
            foreach ($like_expression_array as &$like_expression_array_element) {
                $like_expression_array_element = "(torrents.name" . $like_expression_array_element . " OR torrents.small_descr" . $like_expression_array_element . ")";
            }
            $wherea[] = implode($ANDOR, $like_expression_array);
            break;
        }
        case 1:    // torrent description
        {
            foreach ($like_expression_array as &$like_expression_array_element) {
                $like_expression_array_element = "torrents.descr" . $like_expression_array_element;
            }
            $wherea[] = implode($ANDOR, $like_expression_array);
            break;
        }
        /*case 2	:	// torrent small description
        {
            foreach ($like_expression_array as &$like_expression_array_element)
            $like_expression_array_element =  "torrents.small_descr". $like_expression_array_element;
            $wherea[] =  implode($ANDOR, $like_expression_array);
            break;
        }*/
        case 3:    // torrent uploader
        {
            foreach ($like_expression_array as &$like_expression_array_element) {
                $like_expression_array_element = "users.username" . $like_expression_array_element;
            }

            if (!isset($CURUSER)) {    // not registered user, only show not anonymous torrents
                $wherea[] = implode($ANDOR, $like_expression_array) . " AND torrents.anonymous = 'no'";
            } else {
                if (get_user_class() > $torrentmanage_class) {    // moderator or above, show all
                    $wherea[] = implode($ANDOR, $like_expression_array);
                } else { // only show normal torrents and anonymous torrents from hiself
                    $wherea[] = "(" . implode($ANDOR, $like_expression_array) . " AND torrents.anonymous = 'no') OR (" . implode($ANDOR, $like_expression_array) . " AND torrents.anonymous = 'yes' AND users.id=" . $CURUSER["id"] . ") ";
                }
            }
            break;
        }
        case 4:  //imdb url
            foreach ($like_expression_array as &$like_expression_array_element) {
                $like_expression_array_element = "torrents.url" . $like_expression_array_element;
            }
            $wherea[] = implode($ANDOR, $like_expression_array);
            break;
        default:    // unkonwn
        {
            $search_area = 0;
            $wherea[] = "torrents.name LIKE '%" . $searchstr . "%'";
            write_log("User " . $CURUSER["username"] . "," . $CURUSER["ip"] . " is hacking search_area field in" . $_SERVER['SCRIPT_NAME'], 'mod');
            break;
        }
    }
    $addparam[] = "search_area=" . $search_area;
    $addparam[] = "search=" . rawurlencode($searchstr);
    $addparam[] = "search_mode=" . $search_mode;
}

$where = implode(" AND ", $wherea);

$allsec = 0 + $_GET["allsec"];
if ($allsec == 1) {		//show torrents from all sections
    $addparams[] = "allsec=1";
}

if ($allsec == 1 || $enablespecial != 'yes') {
    if ($where != "") {
        $where = "WHERE $where ";
    } else {
        $where = "";
    }
    $sql = "SELECT COUNT(*) FROM torrents " . ($search_area == 3 || $column == "owner" ? "LEFT JOIN users ON torrents.owner = users.id " : "") . $where;
} else {
    if ($where != "") {
        $where = "WHERE $where AND categories.mode = '$sectiontype'";
    } else {
        $where = "WHERE categories.mode = '$sectiontype'";
    }
    $sql = "SELECT COUNT(*), categories.mode FROM torrents LEFT JOIN categories ON category = categories.id " . ($search_area == 3 || $column == "owner" ? "LEFT JOIN users ON torrents.owner = users.id " : "") . $where . " GROUP BY categories.mode";
}

$res = \NexusPHP\Components\Database::query($sql) or die(\NexusPHP\Components\Database::error());
$count = 0;
while ($row = mysqli_fetch_array($res)) {
    $count += $row[0];
}

if ($count) {
    $torrentsperpage = $CURUSER["torrentsperpage"] ?? $torrentsperpage_main ?? 50;

    list($pagertop, $pagerbottom, $limit) = pager((int)$torrentsperpage, $count, "?" . implode("&", $addparams));
    if ($allsec == 1 || $enablespecial != 'yes') {
        $query = "SELECT torrents.id, torrents.sp_state, torrents.promotion_time_type, torrents.promotion_until, torrents.banned, torrents.picktype, torrents.pos_state, torrents.category, torrents.source, torrents.medium, torrents.codec, torrents.standard, torrents.processing, torrents.team, torrents.audiocodec, torrents.leechers, torrents.seeders, torrents.name, torrents.small_descr, torrents.times_completed, torrents.size, torrents.added, torrents.comments,torrents.anonymous,torrents.owner,torrents.url,torrents.cache_stamp FROM torrents " . ($search_area == 3 || $column == "owner" ? "LEFT JOIN users ON torrents.owner = users.id " : "") . " $where $orderby $limit";
    } else {
        $query = "SELECT torrents.id, torrents.sp_state, torrents.promotion_time_type, torrents.promotion_until, torrents.banned, torrents.picktype, torrents.pos_state, torrents.category, torrents.source, torrents.medium, torrents.codec, torrents.standard, torrents.processing, torrents.team, torrents.audiocodec, torrents.leechers, torrents.seeders, torrents.name, torrents.small_descr, torrents.times_completed, torrents.size, torrents.added, torrents.comments,torrents.anonymous,torrents.owner,torrents.url,torrents.cache_stamp FROM torrents " . ($search_area == 3 || $column == "owner" ? "LEFT JOIN users ON torrents.owner = users.id " : "") . " LEFT JOIN categories ON torrents.category=categories.id $where $orderby $limit";
    }

    $res = \NexusPHP\Components\Database::query($query) or die(\NexusPHP\Components\Database::error());
} else {
    unset($res);
}
if (isset($searchstr)) {
    stdhead($lang_torrents['head_search_results_for'].$searchstr_ori);
} elseif ($sectiontype == $browsecatmode) {
    stdhead($lang_torrents['head_torrents']);
} else {
    stdhead($lang_torrents['head_music']);
}
print("<table width=\"940\" class=\"main\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr><td class=\"embedded\">");
if ($allsec != 1 || $enablespecial != 'yes') { //do not print searchbox if showing bookmarked torrents from all sections;
    function printcat($cbname, $showimg = false)
    {
        global $catpadding, $catsperrow, $lang_torrents;
        global $all_classification, $classification_gets;

        $classification = $all_classification[$cbname];
        $name = $lang_torrents[$classification['lang'] ?? 'text_' . $cbname];
        $wherelistina = $classification_gets[$cbname] ?? [];
        $btname = $cbname . '_check';

        print("<tr><td class=\"embedded\" colspan=\"" . $catsperrow . "\" align=\"left\"><b>" . $name . "</b></td></tr><tr>");
        $i = 0;
        foreach ($classification['item'] as $list) {
            if ($i && $i % $catsperrow == 0) {
                print("</tr><tr>");
            }
            print("<td align=\"left\" class=\"bottom\" style=\"padding-bottom: 4px; padding-left: " . $catpadding . "px;\"><input type=\"checkbox\" id=\"" . $cbname . $list['id'] . "\" name=\"" . $cbname . "[]\"" . (in_array($list['id'], $wherelistina) ? " checked=\"checked\"" : "") . " value=\"" . $list['id'] . "\" />" . ($showimg ? return_category_image($list['id'], "?") : "<a title=\"" . $list['name'] . "\" href=\"?" . $cbname . "=" . $list['id'] . "\">" . $list['name'] . "</a>") . "</td>\n");
            $i++;
        }
        $checker = "<input name=\"" . $btname . "\" value='" . $lang_torrents['input_check_all'] . "' class=\"btn medium\" type=\"button\" onclick=\"javascript:SetChecked('" . $cbname . "','" . $btname . "','" . $lang_torrents['input_check_all'] . "','" . $lang_torrents['input_uncheck_all'] . "',-1,10)\" />";
        print("<td colspan=\"2\" class=\"bottom\" align=\"left\" style=\"padding-left: 15px\">" . $checker . "</td>\n");
        print("</tr>");
    }

    ?>
    <form method="get" name="searchbox" action="?">
    <table border="1" class="searchbox" cellspacing="0" cellpadding="5" width="100%">
    <tbody>
    <tr>
        <td class="colhead" align="center" colspan="2">
            <a href="javascript: klappe_news('searchboxmain')">
                <img class="minus" src="pic/trans.gif" id="picsearchboxmain" alt="Show/Hide"/>
                <?php echo $lang_torrents['text_search_box'] ?>
            </a>
        </td>
    </tr>
    </tbody>
    <tbody id="ksearchboxmain">
    <tr>
    <td class="rowfollow" align="left">
    <table>
    <?php
    foreach ($all_classification as $key => $value) {
        printcat($key);
    }

?>
				</table>
			</td>

			<td class="rowfollow" valign="middle">
				<table>
					<tr>
						<td class="bottom" style="padding: 1px;padding-left: 10px">
							<font class="medium"><?php echo $lang_torrents['text_show_dead_active'] ?></font>
						</td>
				 	</tr>
					<tr>
						<td class="bottom" style="padding: 1px;padding-left: 10px">
							<select class="med" name="incldead" style="width: 100px;">
								<option value="0"><?php echo $lang_torrents['select_including_dead'] ?></option>
								<option value="1"<?php print($include_dead == 1 ? " selected=\"selected\"" : ""); ?>><?php echo $lang_torrents['select_active'] ?> </option>
								<option value="2"<?php print($include_dead == 2 ? " selected=\"selected\"" : ""); ?>><?php echo $lang_torrents['select_dead'] ?></option>
							</select>
						</td>
				 	</tr>
				 	<tr>
						<td class="bottom" style="padding: 1px;padding-left: 10px">
							<br />
						</td>
				 	</tr>
					<tr>
						<td class="bottom" style="padding: 1px;padding-left: 10px">
							<font class="medium"><?php echo $lang_torrents['text_show_special_torrents'] ?></font>
						</td>
				 	</tr>
				 	<tr>
						<td class="bottom" style="padding: 1px;padding-left: 10px">
							<select class="med" name="spstate" style="width: 100px;">
								<option value="0"><?php echo $lang_torrents['select_all'] ?></option>
<?php echo promotion_selection($special_state, 0)?>
							</select>
						</td>
					</tr>
				 	<tr>
						<td class="bottom" style="padding: 1px;padding-left: 10px">
							<br />
						</td>
					</tr>
					<tr>
						<td class="bottom" style="padding: 1px;padding-left: 10px">
							<font class="medium"><?php echo $lang_torrents['text_show_bookmarked'] ?></font>
						</td>
				 	</tr>
				 	<tr>
						<td class="bottom" style="padding: 1px;padding-left: 10px">
							<select class="med" name="inclbookmarked" style="width: 100px;">
								<option value="0"><?php echo $lang_torrents['select_all'] ?></option>
								<option value="1"<?php print($inclbookmarked == 1 ? " selected=\"selected\"" : ""); ?>><?php echo $lang_torrents['select_bookmarked'] ?></option>
								<option value="2"<?php print($inclbookmarked == 2 ? " selected=\"selected\"" : ""); ?>><?php echo $lang_torrents['select_bookmarked_exclude'] ?></option>
							</select>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		</tbody>
		<tbody>
		<tr>
			<td class="rowfollow" align="center">
				<table>
					<tr>
						<td class="embedded">
							<?php echo $lang_torrents['text_search'] ?>&nbsp;&nbsp;
						</td>
						<td class="embedded">
							<table>
								<tr>
									<td class="embedded">
										<input id="searchinput" name="search" type="text" value="<?php echo  $searchstr_ori ?>" autocomplete="off" style="width: 200px" ondblclick="suggest(event.keyCode,this.value);" onkeyup="suggest(event.keyCode,this.value);" onkeypress="return noenter(event.keyCode);"/>
										<script src="suggest.js" type="text/javascript"></script>
										<div id="suggcontainer" style="text-align: left; width:100px;  display: none;">
											<div id="suggestions" style="width:204px; border: 1px solid rgb(119, 119, 119); cursor: default; position: absolute; color: rgb(0,0,0); background-color: rgb(255, 255, 255);"></div>
										</div>
									</td>
								</tr>
							</table>
						</td>
						<td class="embedded">
							<?php echo "&nbsp;" . $lang_torrents['text_in'] ?>

							<select name="search_area">
								<option value="0"><?php echo $lang_torrents['select_title'] ?></option>
								<option value="1"<?php print($_GET["search_area"] == 1 ? " selected=\"selected\"" : ""); ?>><?php echo $lang_torrents['select_description'] ?></option>
								<?php
                                /*if ($smalldescription_main == 'yes'){
                                ?>
                                <option value="2"<?php print($_GET["search_area"] == 2 ? " selected=\"selected\"" : ""); ?>><?php echo $lang_torrents['select_small_description'] ?></option>
                                <?php
                                }*/
                                ?>
								<option value="3"<?php print($_GET["search_area"] == 3 ? " selected=\"selected\"" : ""); ?>><?php echo $lang_torrents['select_uploader'] ?></option>
								<option value="4"<?php print($_GET["search_area"] == 4 ? " selected=\"selected\"" : ""); ?>><?php echo $lang_torrents['select_imdb_url'] ?></option>
							</select>

							<?php echo $lang_torrents['text_with'] ?>

							<select name="search_mode" style="width: 60px;">
								<option value="0"><?php echo $lang_torrents['select_and'] ?></option>
								<option value="1"<?php echo $_GET["search_mode"] == 1 ? " selected=\"selected\"" : "" ?>><?php echo $lang_torrents['select_or'] ?></option>
								<option value="2"<?php echo $_GET["search_mode"] == 2 ? " selected=\"selected\"" : "" ?>><?php echo $lang_torrents['select_exact'] ?></option>
							</select>

							<?php echo $lang_torrents['text_mode'] ?>
						</td>
					</tr>
<?php
$Cache->new_page('hot_search', 3670, true);
if (!$Cache->get_page()) {
    $secs = 3*24*60*60;
    $dt = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s", (TIMENOW - $secs)));
    $dt2 = \NexusPHP\Components\Database::escape(date("Y-m-d H:i:s", (TIMENOW - $secs*2)));
    \NexusPHP\Components\Database::query("DELETE FROM suggest WHERE adddate <" . $dt2) or sqlerr();
    $searchres = \NexusPHP\Components\Database::query("SELECT keywords, COUNT(DISTINCT userid) as count FROM suggest WHERE adddate >" . $dt . " GROUP BY keywords ORDER BY count DESC LIMIT 15") or sqlerr();
    $hotcount = 0;
    $hotsearch = "";
    while ($searchrow = mysqli_fetch_assoc($searchres)) {
        $hotsearch .= "<a href=\"".htmlspecialchars("?search=" . rawurlencode($searchrow["keywords"]) . "&notnewword=1")."\"><u>" . $searchrow["keywords"] . "</u></a>&nbsp;&nbsp;";
        $hotcount += mb_strlen($searchrow["keywords"], "UTF-8");
        if ($hotcount > 60) {
            break;
        }
    }
    $Cache->add_whole_row();
    if ($hotsearch) {
        print("<tr><td class=\"embedded\" colspan=\"3\">&nbsp;&nbsp;".$hotsearch."</td></tr>");
    }
    $Cache->end_whole_row();
    $Cache->cache_page();
}
echo $Cache->next_row();
?>
				</table>
			</td>
			<td class="rowfollow" align="center">
				<input type="submit" class="btn" value="<?php echo $lang_torrents['submit_go'] ?>" />
			</td>
		</tr>
		</tbody>
	</table>
	</form>
<?php
}
    if ($Advertisement->enable_ad()) {
        $belowsearchboxad = $Advertisement->get_ad('belowsearchbox');
        echo "<div align=\"center\" style=\"margin-top: 10px\" id=\"ad_belowsearchbox\">".$belowsearchboxad[0]."</div>";
    }
if ($inclbookmarked == 1) {
    print("<h1 align=\"center\">" . get_username($CURUSER['id']) . $lang_torrents['text_s_bookmarked_torrent'] . "</h1>");
} elseif ($inclbookmarked == 2) {
    print("<h1 align=\"center\">" . get_username($CURUSER['id']) . $lang_torrents['text_s_not_bookmarked_torrent'] . "</h1>");
}

if ($count) {
    print($pagertop);
    if ($sectiontype == $browsecatmode) {
        torrenttable($res, "torrents");
    } elseif ($sectiontype == $specialcatmode) {
        torrenttable($res, "music");
    } else {
        torrenttable($res, "bookmarks");
    }
    print($pagerbottom);
} else {
    if (isset($searchstr)) {
        print("<br />");
        stdmsg($lang_torrents['std_search_results_for'] . $searchstr_ori . "\"", $lang_torrents['std_try_again']);
    } else {
        stdmsg($lang_torrents['std_nothing_found'], $lang_torrents['std_no_active_torrents']);
    }
}
if ($CURUSER) {
    if ($sectiontype == $browsecatmode) {
        $USERUPDATESET[] = "last_browse = " . TIMENOW;
    } else {
        $USERUPDATESET[] = "last_music = " . TIMENOW;
    }
}
print("</td></tr></table>");
stdfoot();
