<?php
require "include/bittorrent.php";
dbconn();
require_once(get_langfile_path());
loggedinorreturn();

if (get_user_class() < UC_ADMINISTRATOR) {
    permissiondenied();
}

function return_category_db_table_name($type)
{
    switch ($type) {
        case 'category':
            $dbtablename = 'categories';
            break;
        case 'source':
            $dbtablename = 'sources';
            break;
        case 'medium':
            $dbtablename = 'media';
            break;
        case 'codec':
            $dbtablename = 'codecs';
            break;
        case 'standard':
            $dbtablename = 'standards';
            break;
        case 'processing':
            $dbtablename = 'processings';
            break;
        case 'team':
            $dbtablename = 'teams';
            break;
        case 'audiocodec':
            $dbtablename = 'audiocodecs';
            break;
        case 'searchbox':
            $dbtablename = 'searchbox';
            break;
        case 'secondicon':
            $dbtablename = 'secondicons';
            break;
        case 'caticon':
            $dbtablename = 'caticons';
            break;
        default:
            return false;
    }
    return $dbtablename;
}
function return_category_mode_selection($selname, $selectionid)
{
    $res = \NexusPHP\Components\Database::query("SELECT * FROM searchbox ORDER BY id ASC");
    $selection = "<select name=\"".$selname."\">";
    while ($row = mysqli_fetch_array($res)) {
        $selection .= "<option value=\"" . $row["id"] . "\"". ($row["id"]==$selectedid ? " selected=\"selected\"" : "").">" . htmlspecialchars($row["name"]) . "</option>\n";
    }
    $selection .= "</select>";
    return $selection;
}
function return_type_name($type)
{
    global $lang_catmanage;
    switch ($type) {
        case 'searchbox':
            $name = $lang_catmanage['text_searchbox'];
            break;
        case 'caticon':
            $name = $lang_catmanage['text_category_icons'];
            break;
        case 'secondicon':
            $name = $lang_catmanage['text_second_icons'];
            break;
        case 'category':
            $name = $lang_catmanage['text_categories'];
            break;
        case 'source':
            $name = $lang_catmanage['text_sources'];
            break;
        case 'medium':
            $name = $lang_catmanage['text_media'];
            break;
        case 'codec':
            $name = $lang_catmanage['text_codecs'];
            break;
        case 'standard':
            $name = $lang_catmanage['text_standards'];
            break;
        case 'processing':
            $name = $lang_catmanage['text_processings'];
            break;
        case 'team':
            $name = $lang_catmanage['text_teams'];
            break;
        case 'audiocodec':
            $name = $lang_catmanage['text_audio_codecs'];
            break;
        default:
            return false;
    }
    return $name;
}

function print_type_list($type)
{
    global $lang_catmanage;
    $typename=return_type_name($type);
    stdhead($lang_catmanage['head_category_management']." - ".$typename);
    begin_main_frame(); ?>
<h1 align="center"><?php echo $lang_catmanage['text_category_management']?> - <?php echo $typename?></h1>
<div>
<span id="item" onclick="dropmenu(this);"><span style="cursor: pointer;" class="big"><b><?php echo $lang_catmanage['text_manage']?></b></span>
<div id="itemlist" class="dropmenu" style="display: none"><ul>
<li><a href="?action=view&amp;type=searchbox"><?php echo $lang_catmanage['text_searchbox']?></a></li>
<li><a href="?action=view&amp;type=caticon"><?php echo $lang_catmanage['text_category_icons']?></a></li>
<li><a href="?action=view&amp;type=secondicon"><?php echo $lang_catmanage['text_second_icons']?></a></li>
<li><a href="?action=view&amp;type=category"><?php echo $lang_catmanage['text_categories']?></a></li>
<li><a href="?action=view&amp;type=source"><?php echo $lang_catmanage['text_sources']?></a></li>
<li><a href="?action=view&amp;type=medium"><?php echo $lang_catmanage['text_media']?></a></li>
<li><a href="?action=view&amp;type=codec"><?php echo $lang_catmanage['text_codecs']?></a></li>
<li><a href="?action=view&amp;type=standard"><?php echo $lang_catmanage['text_standards']?></a></li>
<li><a href="?action=view&amp;type=processing"><?php echo $lang_catmanage['text_processings']?></a></li>
<li><a href="?action=view&amp;type=team"><?php echo $lang_catmanage['text_teams']?></a></li>
<li><a href="?action=view&amp;type=audiocodec"><?php echo $lang_catmanage['text_audio_codecs']?></a></li>
</ul>
</div>
</span>
&nbsp;&nbsp;&nbsp;&nbsp;
<span id="add">
<a href="?action=add&amp;type=<?php echo $type?>" class="big"><b><?php echo $lang_catmanage['text_add']?></b></a>
</span>
</div>
<?php
}
function check_valid_type($type)
{
    global $lang_catmanage;
    $validtype=array('searchbox', 'caticon', 'secondicon', 'category', 'source', 'medium', 'codec', 'standard', 'processing', 'team', 'audiocodec');
    if (!in_array($type, $validtype)) {
        stderr($lang_catmanage['std_error'], $lang_catmanage['std_invalid_type']);
    }
}
function print_sub_category_list($type)
{
    global $lang_catmanage;
    $dbtablename = return_category_db_table_name($type);
    $perpage = 50;
    $num = \NexusPHP\Components\Database::count($dbtablename);
    if (!$num) {
        print("<p align=\"center\">".$lang_catmanage['text_no_record_yet']."</p>");
    } else {
        list($pagertop, $pagerbottom, $limit) = pager($perpage, $num, "?");
        $res = \NexusPHP\Components\Database::query("SELECT * FROM ".$dbtablename." ORDER BY id DESC ".$limit) or sqlerr(__FILE__, __LINE__); ?>
<table border="1" cellspacing="0" cellpadding="5" width="940">
<tr>
<td class="colhead"><?php echo $lang_catmanage['col_id']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_name']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_order']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_action']?></td>
</tr>
<?php
        while ($row = mysqli_fetch_array($res)) {
            ?>
<tr>
<td class="colfollow"><?php echo $row['id']?></td>
<td class="colfollow"><?php echo htmlspecialchars($row['name'])?></td>
<td class="colfollow"><?php echo $row['sort_index']?></td>
<td class="colfollow"><a href="javascript:confirm_delete('<?php echo $row['id']?>', '<?php echo $lang_catmanage['js_sure_to_delete_this']?>', 'type=<?php echo $type?>');"><?php echo $lang_catmanage['text_delete']?></a> | <a href="?action=edit&amp;type=<?php echo $type?>&amp;id=<?php echo $row['id']?>"><?php echo $lang_catmanage['text_edit']?></a></td>
</tr>
<?php
        } ?>
</table>
<?php
print($pagerbottom);
    }
}
function print_category_editor($type, $row='')
{
    global $lang_catmanage;
    global $validsubcattype;
    if (in_array($type, $validsubcattype)) {
        print_sub_category_editor($type, $row);
    } else {
        $typename=return_type_name($type); ?>
<div style="width: 940px">
<h1 align="center"><a class="faqlink" href="?action=view&amp;type=<?php echo $type?>"><?php echo $typename?></a></h1>
<div>
<table border="1" cellspacing="0" cellpadding="10" width="100%">
<?php
        if ($type=='searchbox') {
            if ($row) {
                $name = $row['name'];
                $showsource = $row['showsource'];
                $showmedium = $row['showmedium'];
                $showcodec = $row['showcodec'];
                $showstandard = $row['showstandard'];
                $showprocessing = $row['showprocessing'];
                $showteam = $row['showteam'];
                $showaudiocodec = $row['showaudiocodec'];
                $catsperrow = $row['catsperrow'];
                $catpadding = $row['catpadding'];
            } else {
                $name = '';
                $showsource = 0;
                $showmedium = 0;
                $showcodec = 0;
                $showstandard = 0;
                $showprocessing = 0;
                $showteam = 0;
                $showaudiocodec = 0;
                $catsperrow = 8;
                $catpadding = 3;
            }
            tr($lang_catmanage['row_searchbox_name']."<font color=\"red\">*</font>", "<input type=\"text\" name=\"name\" value=\"".htmlspecialchars($name)."\" style=\"width: 300px\" /> " . $lang_catmanage['text_searchbox_name_note'], 1);
            tr($lang_catmanage['row_show_sub_category'], "<input type=\"checkbox\" name=\"showsource\" value=\"1\"".($showsource ? " checked=\"checked\"" : "")." /> " . $lang_catmanage['text_sources'] . "<input type=\"checkbox\" name=\"showmedium\" value=\"1\"".($showmedium ? " checked=\"checked\"" : "")." /> " . $lang_catmanage['text_media'] . "<input type=\"checkbox\" name=\"showcodec\" value=\"1\"".($showcodec ? " checked=\"checked\"" : "")." /> " . $lang_catmanage['text_codecs'] . "<input type=\"checkbox\" name=\"showstandard\" value=\"1\"".($showstandard ? " checked=\"checked\"" : "")." /> " . $lang_catmanage['text_standards'] . "<input type=\"checkbox\" name=\"showprocessing\" value=\"1\"".($showprocessing ? " checked=\"checked\"" : "")." /> " . $lang_catmanage['text_processings'] . "<input type=\"checkbox\" name=\"showteam\" value=\"1\"".($showteam ? " checked=\"checked\"" : "")." /> " . $lang_catmanage['text_teams'] . "<input type=\"checkbox\" name=\"showaudiocodec\" value=\"1\"".($showaudiocodec ? " checked=\"checked\"" : "")." /> " . $lang_catmanage['text_audio_codecs']."<br />".$lang_catmanage['text_show_sub_category_note'], 1);
            tr($lang_catmanage['row_items_per_row']."<font color=\"red\">*</font>", "<input type=\"text\" name=\"catsperrow\" value=\"".$catsperrow."\" style=\"width: 100px\" /> " . $lang_catmanage['text_items_per_row_note'], 1);
            tr($lang_catmanage['row_padding_between_items']."<font color=\"red\">*</font>", "<input type=\"text\" name=\"catpadding\" value=\"".$catpadding."\" style=\"width: 100px\" /> " . $lang_catmanage['text_padding_between_items_note'], 1);
        } elseif ($type=='caticon') {
            if ($row) {
                $name = $row['name'];
                $folder = $row['folder'];
                $multilang = $row['multilang'];
                $secondicon = $row['secondicon'];
                $cssfile = $row['cssfile'];
                $designer = $row['designer'];
                $comment = $row['comment'];
            } else {
                $name = '';
                $folder = '';
                $multilang = 'no';
                $secondicon = 'no';
                $cssfile = '';
                $designer = '';
                $comment = '';
            } ?>
<tr><td colspan="2"><?php echo $lang_catmanage['text_icon_directory_note']?></td></tr>
<?php
            tr($lang_catmanage['col_name']."<font color=\"red\">*</font>", "<input type=\"text\" name=\"name\" value=\"".htmlspecialchars($name)."\" style=\"width: 300px\" /> " . $lang_catmanage['text_category_icon_name_note'], 1);
            tr($lang_catmanage['col_folder']."<font color=\"red\">*</font>", "<input type=\"text\" name=\"folder\" value=\"".htmlspecialchars($folder)."\" style=\"width: 300px\" /><br />" . $lang_catmanage['text_folder_note'], 1);
            tr($lang_catmanage['text_multi_language'], "<input type=\"checkbox\" name=\"multilang\" value=\"yes\"".($multilang == 'yes' ? " checked=\"checked\"" : "")." />".$lang_catmanage['text_yes'] ."<br />". $lang_catmanage['text_multi_language_note'], 1);
            tr($lang_catmanage['text_second_icon'], "<input type=\"checkbox\" name=\"secondicon\" value=\"yes\"".($secondicon == 'yes' ? " checked=\"checked\"" : "")." />".$lang_catmanage['text_yes'] ."<br />". $lang_catmanage['text_second_icon_note'], 1);
            tr($lang_catmanage['text_css_file'], "<input type=\"text\" name=\"cssfile\" value=\"".htmlspecialchars($cssfile)."\" style=\"width: 300px\" /> ". $lang_catmanage['text_css_file_note'], 1);
            tr($lang_catmanage['text_designer'], "<input type=\"text\" name=\"designer\" value=\"".htmlspecialchars($designer)."\" style=\"width: 300px\" /> ". $lang_catmanage['text_designer_note'], 1);
            tr($lang_catmanage['text_comment'], "<input type=\"text\" name=\"comment\" value=\"".htmlspecialchars($comment)."\" style=\"width: 300px\" /> ". $lang_catmanage['text_comment_note'], 1);
        } elseif ($type=='secondicon') {
            if ($row) {
                $name = $row['name'];
                $image = $row['image'];
                $class_name = $row['class_name'];
                $source = $row['source'];
                $medium = $row['medium'];
                $codec = $row['codec'];
                $standard = $row['standard'];
                $processing = $row['processing'];
                $team = $row['team'];
                $audiocodec = $row['audiocodec'];
            } else {
                $name = '';
                $image = '';
                $class_name = '';
                $source = 0;
                $medium = 0;
                $codec = 0;
                $standard = 0;
                $processing = 0;
                $team = 0;
                $audiocodec = 0;
            }
            tr($lang_catmanage['col_name']."<font color=\"red\">*</font>", "<input type=\"text\" name=\"name\" value=\"".htmlspecialchars($name)."\" style=\"width: 300px\" /> " . $lang_catmanage['text_second_icon_name_note'], 1);
            tr($lang_catmanage['col_image']."<font color=\"red\">*</font>", "<input type=\"text\" name=\"image\" value=\"".htmlspecialchars($image)."\" style=\"width: 300px\" /><br />" . $lang_catmanage['text_image_note'], 1);
            tr($lang_catmanage['text_class_name'], "<input type=\"text\" name=\"class_name\" value=\"".htmlspecialchars($class_name)."\" style=\"width: 300px\" /><br />" . $lang_catmanage['text_class_name_note'], 1);
            tr($lang_catmanage['row_selections']."<font color=\"red\">*</font>", torrent_selection(return_type_name('source'), 'source', return_category_db_table_name('source'), $source) . torrent_selection(return_type_name('source'), 'source', return_category_db_table_name('source'), $source) . torrent_selection(return_type_name('medium'), 'medium', return_category_db_table_name('medium'), $medium) . torrent_selection(return_type_name('codec'), 'codec', return_category_db_table_name('codec'), $codec) . torrent_selection(return_type_name('standard'), 'standard', return_category_db_table_name('standard'), $standard) . torrent_selection(return_type_name('processing'), 'processing', return_category_db_table_name('processing'), $processing) . torrent_selection(return_type_name('team'), 'team', return_category_db_table_name('team'), $team) . torrent_selection(return_type_name('audiocodec'), 'audiocodec', return_category_db_table_name('audiocodec'), $audiocodec)."<br />".$lang_catmanage['text_selections_note'], 1);
        } elseif ($type=='category') {
            if ($row) {
                $name = $row['name'];
                $mode = $row['mode'];
                $image = $row['image'];
                $class_name = $row['class_name'];
                $sort_index = $row['sort_index'];
            } else {
                $name = '';
                $mode = 1;
                $image = '';
                $class_name = '';
                $sort_index = 0;
            }
            tr($lang_catmanage['row_category_name']."<font color=\"red\">*</font>", "<input type=\"text\" name=\"name\" value=\"".htmlspecialchars($name)."\" style=\"width: 300px\" /> " . $lang_catmanage['text_category_name_note'], 1);
            tr($lang_catmanage['col_image']."<font color=\"red\">*</font>", "<input type=\"text\" name=\"image\" value=\"".htmlspecialchars($image)."\" style=\"width: 300px\" /><br />" . $lang_catmanage['text_image_note'], 1);
            tr($lang_catmanage['text_class_name'], "<input type=\"text\" name=\"class_name\" value=\"".htmlspecialchars($class_name)."\" style=\"width: 300px\" /><br />" . $lang_catmanage['text_class_name_note'], 1);
            tr($lang_catmanage['row_mode']."<font color=\"red\">*</font>", return_category_mode_selection('mode', $mode), 1);
            tr($lang_catmanage['col_order'], "<input type=\"text\" name=\"sort_index\" value=\"".$sort_index."\" style=\"width: 100px\" /> " . $lang_catmanage['text_order_note'], 1);
        } ?>
</table>
</div>
<div style="text-align: center; margin-top: 10px;">
<input type="submit" value="<?php echo $lang_catmanage['submit_submit']?>" />
</div>
</div>
<?php
    }
}
function print_sub_category_editor($type, $row='')
{
    global $lang_catmanage;
    $typename=return_type_name($type);
    if ($row) {
        $name = $row['name'];
        $sort_index = $row['sort_index'];
    } else {
        $name = '';
        $sort_index = 0;
    } ?>
<div style="width: 940px">
<h1 align="center"><a class="faqlink" href="?action=view&amp;type=<?php echo $type?>"><?php echo $typename?></a></h1>
<table border="1" cellspacing="0" cellpadding="10" width="100%">
<?php
tr($lang_catmanage['col_name']."<font color=\"red\">*</font>", "<input type=\"text\" name=\"name\" value=\"".htmlspecialchars($name)."\" style=\"width: 300px\" /> " . $lang_catmanage['text_subcategory_name_note'], 1);
    tr($lang_catmanage['col_order'], "<input type=\"text\" name=\"sort_index\" value=\"".$sort_index."\" style=\"width: 100px\" /> " . $lang_catmanage['text_order_note'], 1); ?>
</table>
<div style="text-align: center; margin-top: 10px;">
<input type="submit" value="<?php echo $lang_catmanage['submit_submit']?>" />
</div>
</div>
<?php
}

$validsubcattype=array('source', 'medium', 'codec', 'standard', 'processing', 'team', 'audiocodec');
$type = $_GET['type'];
if ($type == '') {
    $type = 'searchbox';
} else {
    check_valid_type($type);
}
$action = $_GET['action'];
if ($action == '') {
    $action = 'view';
}
if ($action == 'view') {
    print_type_list($type); ?>
<div style="margin-top: 8px">
<?php
    if (in_array($type, $validsubcattype)) {
        print_sub_category_list($type);
    } elseif ($type=='searchbox') {
        $perpage = 50;
        $dbtablename=return_category_db_table_name($type);
        $num = \NexusPHP\Components\Database::count($dbtablename);
        if (!$num) {
            print("<p align=\"center\">".$lang_catmanage['text_no_record_yet']."</p>");
        } else {
            list($pagertop, $pagerbottom, $limit) = pager($perpage, $num, "?");
            $res = \NexusPHP\Components\Database::query("SELECT * FROM ".$dbtablename." ORDER BY id ASC ".$limit) or sqlerr(__FILE__, __LINE__); ?>
<table border="1" cellspacing="0" cellpadding="5" width="940">
<tr>
<td class="colhead"><?php echo $lang_catmanage['col_id']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_name']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_sub_category']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_sources']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_media']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_codecs']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_standards']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_processings']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_teams']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_audio_codecs']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_per_row']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_padding']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_action']?></td>
</tr>
<?php
        while ($row = mysqli_fetch_array($res)) {
            ?>
<tr>
<td class="colfollow"><?php echo $row['id']?></td>
<td class="colfollow"><?php echo htmlspecialchars($row['name'])?></td>
<td class="colfollow"><?php echo $row['showsubcat'] ? "<font color=\"green\">".$lang_catmanage['text_enabled']."</font>" : "<font color=\"red\">".$lang_catmanage['text_disabled']."</font>"?></td>
<td class="colfollow"><?php echo $row['showsource'] ? "<font color=\"green\">".$lang_catmanage['text_enabled']."</font>" : "<font color=\"red\">".$lang_catmanage['text_disabled']."</font>"?></td>
<td class="colfollow"><?php echo $row['showmedium'] ? "<font color=\"green\">".$lang_catmanage['text_enabled']."</font>" : "<font color=\"red\">".$lang_catmanage['text_disabled']."</font>"?></td>
<td class="colfollow"><?php echo $row['showcodec'] ? "<font color=\"green\">".$lang_catmanage['text_enabled']."</font>" : "<font color=\"red\">".$lang_catmanage['text_disabled']."</font>"?></td>
<td class="colfollow"><?php echo $row['showstandard'] ? "<font color=\"green\">".$lang_catmanage['text_enabled']."</font>" : "<font color=\"red\">".$lang_catmanage['text_disabled']."</font>"?></td>
<td class="colfollow"><?php echo $row['showprocessing'] ? "<font color=\"green\">".$lang_catmanage['text_enabled']."</font>" : "<font color=\"red\">".$lang_catmanage['text_disabled']."</font>"?></td>
<td class="colfollow"><?php echo $row['showteam'] ? "<font color=\"green\">".$lang_catmanage['text_enabled']."</font>" : "<font color=\"red\">".$lang_catmanage['text_disabled']."</font>"?></td>
<td class="colfollow"><?php echo $row['showaudiocodec'] ? "<font color=\"green\">".$lang_catmanage['text_enabled']."</font>" : "<font color=\"red\">".$lang_catmanage['text_disabled']."</font>"?></td>
<td class="colfollow"><?php echo $row['catsperrow']?></td>
<td class="colfollow"><?php echo $row['catpadding']?></td>
<td class="colfollow"><a href="javascript:confirm_delete('<?php echo $row['id']?>', '<?php echo $lang_catmanage['js_sure_to_delete_this']?>', 'type=<?php echo $type?>');"><?php echo $lang_catmanage['text_delete']?></a> | <a href="?action=edit&amp;type=<?php echo $type?>&amp;id=<?php echo $row['id']?>"><?php echo $lang_catmanage['text_edit']?></a></td>
</tr>
<?php
        } ?>
</table>
<?php
print($pagerbottom);
        }
    } elseif ($type=='caticon') {
        $perpage = 50;
        $dbtablename=return_category_db_table_name($type);
        $num = \NexusPHP\Components\Database::count($dbtablename);
        if (!$num) {
            print("<p align=\"center\">".$lang_catmanage['text_no_record_yet']."</p>");
        } else {
            list($pagertop, $pagerbottom, $limit) = pager($perpage, $num, "?");
            $res = \NexusPHP\Components\Database::query("SELECT * FROM ".$dbtablename." ORDER BY id ASC ".$limit) or sqlerr(__FILE__, __LINE__); ?>
<table border="1" cellspacing="0" cellpadding="5" width="940">
<tr>
<td class="colhead"><?php echo $lang_catmanage['col_id']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_name']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_folder']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_multi_language']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_second_icon']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_css_file']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_designer']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_comment']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_action']?></td>
</tr>
<?php
        while ($row = mysqli_fetch_array($res)) {
            ?>
<tr>
<td class="colfollow"><?php echo $row['id']?></td>
<td class="colfollow"><?php echo htmlspecialchars($row['name'])?></td>
<td class="colfollow"><?php echo htmlspecialchars($row['folder'])?></td>
<td class="colfollow"><?php echo $row['multilang']=='yes' ? "<font color=\"green\">".$lang_catmanage['text_yes']."</font>" : "<font color=\"red\">".$lang_catmanage['text_no']."</font>"?></td>
<td class="colfollow"><?php echo $row['secondicon']=='yes' ? "<font color=\"green\">".$lang_catmanage['text_yes']."</font>" : "<font color=\"red\">".$lang_catmanage['text_no']."</font>"?></td>
<td class="colfollow"><?php echo $row['cssfile'] ? htmlspecialchars($row['cssfile']) : $lang_catmanage['text_none']?></td>
<td class="colfollow"><?php echo htmlspecialchars($row['designer'])?></td>
<td class="colfollow"><?php echo htmlspecialchars($row['comment'])?></td>
<td class="colfollow"><a href="javascript:confirm_delete('<?php echo $row['id']?>', '<?php echo $lang_catmanage['js_sure_to_delete_this']?>', 'type=<?php echo $type?>');"><?php echo $lang_catmanage['text_delete']?></a> | <a href="?action=edit&amp;type=<?php echo $type?>&amp;id=<?php echo $row['id']?>"><?php echo $lang_catmanage['text_edit']?></a></td>
</tr>
<?php
        } ?>
</table>
<?php
print($pagerbottom);
        }
    } elseif ($type=='secondicon') {
        $perpage = 50;
        $dbtablename=return_category_db_table_name($type);
        $num = \NexusPHP\Components\Database::count($dbtablename);
        if (!$num) {
            print("<p align=\"center\">".$lang_catmanage['text_no_record_yet']."</p>");
        } else {
            list($pagertop, $pagerbottom, $limit) = pager($perpage, $num, "?");
            $res = \NexusPHP\Components\Database::query("SELECT * FROM ".$dbtablename." ORDER BY id ASC ".$limit) or sqlerr(__FILE__, __LINE__); ?>
<table border="1" cellspacing="0" cellpadding="5" width="940">
<tr>
<td class="colhead"><?php echo $lang_catmanage['col_id']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_name']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_image']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_class_name']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_sources']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_media']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_codecs']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_standards']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_processings']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_teams']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_audio_codecs']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_action']?></td>
</tr>
<?php
        while ($row = mysqli_fetch_array($res)) {
            ?>
<tr>
<td class="colfollow"><?php echo $row['id']?></td>
<td class="colfollow"><?php echo htmlspecialchars($row['name'])?></td>
<td class="colfollow"><?php echo htmlspecialchars($row['image'])?></td>
<td class="colfollow"><?php echo $row['class_name'] ? htmlspecialchars($row['class_name']) : $lang_catmanage['text_none']?></td>
<td class="colfollow"><?php echo $row['source']?></td>
<td class="colfollow"><?php echo $row['medium']?></td>
<td class="colfollow"><?php echo $row['codec']?></td>
<td class="colfollow"><?php echo $row['standard']?></td>
<td class="colfollow"><?php echo $row['processing']?></td>
<td class="colfollow"><?php echo $row['team']?></td>
<td class="colfollow"><?php echo $row['audiocodec']?></td>
<td class="colfollow"><a href="javascript:confirm_delete('<?php echo $row['id']?>', '<?php echo $lang_catmanage['js_sure_to_delete_this']?>', 'type=<?php echo $type?>');"><?php echo $lang_catmanage['text_delete']?></a> | <a href="?action=edit&amp;type=<?php echo $type?>&amp;id=<?php echo $row['id']?>"><?php echo $lang_catmanage['text_edit']?></a></td>
</tr>
<?php
        } ?>
</table>
<?php
print($pagerbottom);
        }
    } elseif ($type=='category') {
        $perpage = 50;
        $dbtablename=return_category_db_table_name($type);
        $num = \NexusPHP\Components\Database::count($dbtablename);
        if (!$num) {
            print("<p align=\"center\">".$lang_catmanage['text_no_record_yet']."</p>");
        } else {
            list($pagertop, $pagerbottom, $limit) = pager($perpage, $num, "?");
            $res = \NexusPHP\Components\Database::query("SELECT ".$dbtablename.".*, searchbox.name AS catmodename FROM ".$dbtablename." LEFT JOIN searchbox ON ".$dbtablename.".mode=searchbox.id ORDER BY ".$dbtablename.".mode ASC, ".$dbtablename.".id ASC ".$limit) or sqlerr(__FILE__, __LINE__); ?>
<table border="1" cellspacing="0" cellpadding="5" width="940">
<tr>
<td class="colhead"><?php echo $lang_catmanage['col_id']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_mode']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_name']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_image']?></td>
<td class="colhead"><?php echo $lang_catmanage['text_class_name']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_order']?></td>
<td class="colhead"><?php echo $lang_catmanage['col_action']?></td>
</tr>
<?php
        while ($row = mysqli_fetch_array($res)) {
            ?>
<tr>
<td class="colfollow"><?php echo $row['id']?></td>
<td class="colfollow"><?php echo htmlspecialchars($row['catmodename'])?></td>
<td class="colfollow"><?php echo htmlspecialchars($row['name'])?></td>
<td class="colfollow"><?php echo htmlspecialchars($row['image'])?></td>
<td class="colfollow"><?php echo $row['class_name'] ? htmlspecialchars($row['class_name']) : $lang_catmanage['text_none']?></td>
<td class="colfollow"><?php echo $row['sort_index']?></td>
<td class="colfollow"><a href="javascript:confirm_delete('<?php echo $row['id']?>', '<?php echo $lang_catmanage['js_sure_to_delete_this']?>', 'type=<?php echo $type?>');"><?php echo $lang_catmanage['text_delete']?></a> | <a href="?action=edit&amp;type=<?php echo $type?>&amp;id=<?php echo $row['id']?>"><?php echo $lang_catmanage['text_edit']?></a></td>
</tr>
<?php
        } ?>
</table>
<?php
print($pagerbottom);
        }
    } ?>
</div>
<?php
    end_main_frame();
    stdfoot();
} elseif ($action == 'del') {
    $id = 0 + $_GET['id'];
    if (!$id) {
        stderr($lang_catmanage['std_error'], $lang_catmanage['std_invalid_id']);
    }
    $dbtablename=return_category_db_table_name($type);
    $res = \NexusPHP\Components\Database::query("SELECT * FROM ".$dbtablename." WHERE id = ".\NexusPHP\Components\Database::escape($id)." LIMIT 1");
    if ($row = mysqli_fetch_array($res)) {
        \NexusPHP\Components\Database::query("DELETE FROM ".$dbtablename." WHERE id = ".\NexusPHP\Components\Database::escape($row['id'])) or sqlerr(__FILE__, __LINE__);
        if (in_array($type, $validsubcattype)) {
            $Cache->delete_value($dbtablename.'_list');
        } elseif ($type=='searchbox') {
            $Cache->delete_value('searchbox_content');
        } elseif ($type=='caticon') {
            $Cache->delete_value('category_icon_content');
        } elseif ($type=='secondicon') {
            $Cache->delete_value('secondicon_'.$row['source'].'_'.$row['medium'].'_'.$row['codec'].'_'.$row['standard'].'_'.$row['processing'].'_'.$row['team'].'_'.$row['audiocodec'].'_content');
        } elseif ($type=='category') {
            $Cache->delete_value('category_content');
            $Cache->delete_value('category_list_mode_'.$row['mode']);
        }
    }
    header("Location: ".get_protocol_prefix() . $BASEURL."/catmanage.php?action=view&type=".$type);
    die();
} elseif ($action == 'edit') {
    $id = 0 + $_GET['id'];
    if (!$id) {
        stderr($lang_catmanage['std_error'], $lang_catmanage['std_invalid_id']);
    } else {
        $dbtablename=return_category_db_table_name($type);
        $res = \NexusPHP\Components\Database::query("SELECT * FROM ".$dbtablename." WHERE id = ".\NexusPHP\Components\Database::escape($id)." LIMIT 1");
        if (!$row = mysqli_fetch_array($res)) {
            stderr($lang_catmanage['std_error'], $lang_catmanage['std_invalid_id']);
        } else {
            $typename=return_type_name($type);
            stdhead($lang_catmanage['head_edit']." - ".$typename);
            print("<form method=\"post\" action=\"?action=submit&amp;type=".$type."\">");
            print("<input type=\"hidden\" name=\"isedit\" value=\"1\" />");
            print("<input type=\"hidden\" name=\"id\" value=\"".$id."\" />");
            print_category_editor($type, $row);
            print("</form>");
            stdfoot();
        }
    }
} elseif ($action == 'add') {
    $typename=return_type_name($type);
    stdhead($lang_catmanage['head_add']." - ".$typename);
    print("<form method=\"post\" action=\"?action=submit&amp;type=".$type."\">");
    print("<input type=\"hidden\" name=\"isedit\" value=\"0\" />");
    print_category_editor($type);
    print("</form>");
    stdfoot();
} elseif ($action == 'submit') {
    $dbtablename=return_category_db_table_name($type);
    if ($_POST['isedit']) {
        $id = 0 + $_POST['id'];
        if (!$id) {
            stderr($lang_catmanage['std_error'], $lang_catmanage['std_invalid_id']);
        } else {
            $res = \NexusPHP\Components\Database::query("SELECT * FROM ".$dbtablename." WHERE id = ".\NexusPHP\Components\Database::escape($id)." LIMIT 1");
            if (!$row = mysqli_fetch_array($res)) {
                stderr($lang_catmanage['std_error'], $lang_catmanage['std_invalid_id']);
            }
        }
    }
    $updateset = array();
    if (in_array($type, $validsubcattype)) {
        $name = $_POST['name'];
        if (!$name) {
            stderr($lang_catmanage['std_error'], $lang_catmanage['std_missing_form_data']);
        }
        $updateset[] = "name=".\NexusPHP\Components\Database::escape($name);
        $sort_index = 0+$_POST['sort_index'];
        $updateset[] = "sort_index=".\NexusPHP\Components\Database::escape($sort_index);
        $Cache->delete_value($dbtablename.'_list');
    } elseif ($type=='searchbox') {
        $name = $_POST['name'];
        $catsperrow = 0+$_POST['catsperrow'];
        $catpadding = 0+$_POST['catpadding'];
        if (!$name || !$catsperrow || !$catpadding) {
            stderr($lang_catmanage['std_error'], $lang_catmanage['std_missing_form_data']);
        }
        $showsource = 0+$_POST['showsource'];
        $showmedium = 0+$_POST['showmedium'];
        $showcodec = 0+$_POST['showcodec'];
        $showstandard = 0+$_POST['showstandard'];
        $showprocessing = 0+$_POST['showprocessing'];
        $showteam = 0+$_POST['showteam'];
        $showaudiocodec = 0+$_POST['showaudiocodec'];
        $updateset[] = "catsperrow=".\NexusPHP\Components\Database::escape($catsperrow);
        $updateset[] = "catpadding=".\NexusPHP\Components\Database::escape($catpadding);
        $updateset[] = "name=".\NexusPHP\Components\Database::escape($name);
        $updateset[] = "showsource=".\NexusPHP\Components\Database::escape($showsource);
        $updateset[] = "showmedium=".\NexusPHP\Components\Database::escape($showmedium);
        $updateset[] = "showcodec=".\NexusPHP\Components\Database::escape($showcodec);
        $updateset[] = "showstandard=".\NexusPHP\Components\Database::escape($showstandard);
        $updateset[] = "showprocessing=".\NexusPHP\Components\Database::escape($showprocessing);
        $updateset[] = "showteam=".\NexusPHP\Components\Database::escape($showteam);
        $updateset[] = "showaudiocodec=".\NexusPHP\Components\Database::escape($showaudiocodec);
        if ($showsource || $showmedium || $showcodec || $showstandard || $showprocessing || $showteam || $showaudiocodec) {
            $updateset[] = "showsubcat=1";
        } else {
            $updateset[] = "showsubcat=0";
        }
        if ($_POST['isedit']) {
            $Cache->delete_value('searchbox_content');
        }
    } elseif ($type=='caticon') {
        $name = $_POST['name'];
        $folder = trim($_POST['folder']);
        $cssfile = trim($_POST['cssfile']);
        $multilang = ($_POST['multilang'] == 'yes' ? 'yes' : 'no');
        $secondicon = ($_POST['secondicon'] == 'yes' ? 'yes' : 'no');
        $designer = $_POST['designer'];
        $comment = $_POST['comment'];
        if (!$name || !$folder) {
            stderr($lang_catmanage['std_error'], $lang_catmanage['std_missing_form_data']);
        }
        if (!valid_file_name($folder)) {
            stderr($lang_catmanage['std_error'], $lang_catmanage['std_invalid_character_in_filename'].htmlspecialchars($folder));
        }
        if ($cssfile && !valid_file_name($cssfile)) {
            stderr($lang_catmanage['std_error'], $lang_catmanage['std_invalid_character_in_filename'].htmlspecialchars($cssfile));
        }
        $updateset[] = "name=".\NexusPHP\Components\Database::escape($name);
        $updateset[] = "folder=".\NexusPHP\Components\Database::escape($folder);
        $updateset[] = "multilang=".\NexusPHP\Components\Database::escape($multilang);
        $updateset[] = "secondicon=".\NexusPHP\Components\Database::escape($secondicon);
        $updateset[] = "cssfile=".\NexusPHP\Components\Database::escape($cssfile);
        $updateset[] = "designer=".\NexusPHP\Components\Database::escape($designer);
        $updateset[] = "comment=".\NexusPHP\Components\Database::escape($comment);
        if ($_POST['isedit']) {
            $Cache->delete_value('category_icon_content');
        }
    } elseif ($type=='secondicon') {
        $name = $_POST['name'];
        $image = trim($_POST['image']);
        $class_name = trim($_POST['class_name']);
        $source = 0+$_POST['source'];
        $medium = 0+$_POST['medium'];
        $codec = 0+$_POST['codec'];
        $standard = 0+$_POST['standard'];
        $processing = 0+$_POST['processing'];
        $team = 0+$_POST['team'];
        $audiocodec = 0+$_POST['audiocodec'];
        if (!$name || !$image) {
            stderr($lang_catmanage['std_error'], $lang_catmanage['std_missing_form_data']);
        }
        if (!valid_file_name($image)) {
            stderr($lang_catmanage['std_error'], $lang_catmanage['std_invalid_character_in_filename'].htmlspecialchars($image));
        }
        if ($class_name && !valid_class_name($class_name)) {
            stderr($lang_catmanage['std_error'], $lang_catmanage['std_invalid_character_in_filename'].htmlspecialchars($class_name));
        }
        if (!$source && !$medium && !$codec && !$standard && !$processing && !$team && !$audiocodec) {
            stderr($lang_catmanage['std_error'], $lang_catmanage['std_must_define_one_selection']);
        }
        $updateset[] = "name=".\NexusPHP\Components\Database::escape($name);
        $updateset[] = "image=".\NexusPHP\Components\Database::escape($image);
        $updateset[] = "class_name=".\NexusPHP\Components\Database::escape($class_name);
        $updateset[] = "medium=".\NexusPHP\Components\Database::escape($medium);
        $updateset[] = "codec=".\NexusPHP\Components\Database::escape($codec);
        $updateset[] = "standard=".\NexusPHP\Components\Database::escape($standard);
        $updateset[] = "processing=".\NexusPHP\Components\Database::escape($processing);
        $updateset[] = "team=".\NexusPHP\Components\Database::escape($team);
        $updateset[] = "audiocodec=".\NexusPHP\Components\Database::escape($audiocodec);
        if ($_POST['isedit']) {
            $res2=\NexusPHP\Components\Database::query("SELECT * FROM secondicons WHERE id=".\NexusPHP\Components\Database::escape($id)." LIMIT 1");
            if ($row2=mysqli_fetch_array($res)) {
                $Cache->delete_value('secondicon_'.$row2['source'].'_'.$row2['medium'].'_'.$row2['codec'].'_'.$row2['standard'].'_'.$row2['processing'].'_'.$row2['team'].'_'.$row2['audiocodec'].'_content');
            }
        }
        $Cache->delete_value('secondicon_'.$source.'_'.$medium.'_'.$codec.'_'.$standard.'_'.$processing.'_'.$team.'_'.$audiocodec.'_content');
    } elseif ($type=='category') {
        $name = $_POST['name'];
        $image = trim($_POST['image']);
        $mode = 0+$_POST['mode'];
        $class_name = trim($_POST['class_name']);
        $sort_index = 0+$_POST['sort_index'];
        if (!$name || !$image) {
            stderr($lang_catmanage['std_error'], $lang_catmanage['std_missing_form_data']);
        }
        if (!valid_file_name($image)) {
            stderr($lang_catmanage['std_error'], $lang_catmanage['std_invalid_character_in_filename'].htmlspecialchars($image));
        }
        if ($class_name && !valid_class_name($class_name)) {
            stderr($lang_catmanage['std_error'], $lang_catmanage['std_invalid_character_in_filename'].htmlspecialchars($class_name));
        }
        if (!$mode) {
            stderr($lang_catmanage['std_error'], $lang_catmanage['std_invalid_mode_id']);
        }
        $updateset[] = "name=".\NexusPHP\Components\Database::escape($name);
        $updateset[] = "image=".\NexusPHP\Components\Database::escape($image);
        $updateset[] = "mode=".\NexusPHP\Components\Database::escape($mode);
        $updateset[] = "class_name=".\NexusPHP\Components\Database::escape($class_name);
        $updateset[] = "sort_index=".\NexusPHP\Components\Database::escape($sort_index);
        if ($_POST['isedit']) {
            $Cache->delete_value('category_content');
        }
        $Cache->delete_value('category_list_mode_'.$mode);
    }
    if ($_POST['isedit']) {
        \NexusPHP\Components\Database::query("UPDATE ".$dbtablename." SET " . join(",", $updateset) . " WHERE id = ".\NexusPHP\Components\Database::escape($id)) or sqlerr(__FILE__, __LINE__);
    } else {
        \NexusPHP\Components\Database::query("INSERT INTO ".$dbtablename." SET " . join(",", $updateset)) or sqlerr(__FILE__, __LINE__);
    }
    header("Location: ".get_protocol_prefix() . $BASEURL."/catmanage.php?action=view&type=".$type);
}
?>
