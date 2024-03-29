function postvalid(form){
	$('qr').disabled = true;
	return true;
}

function dropmenu(obj){
$(obj.id + 'list').style.display = $(obj.id + 'list').style.display == 'none' ? '' : 'none';
}

function confirm_delete(id, note, addon)
{
   if(confirm(note))
   {
      self.location.href='?action=del'+(addon ? '&'+addon : '')+'&id='+id;
   }
}

//viewfilelist.js
/**
 * 将字节转为易读的文件大小
 * https://github.com/xiaomlove/nexusphp/issues/27
 * https://ourbits.club/assets/js/common.js
 * @param bytes
 * @param fix
 * @param si
 * @returns {string}
 */
 function humanFileSize(bytes, fix, si) {
    var thresh = si ? 1000 : 1024;
    if (Math.abs(bytes) < thresh) {
        return bytes + ' B';
    }
    var units = si
        ? ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']
        : ['KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
    var u = -1;
    do {
        bytes /= thresh;
        ++u;
    } while (Math.abs(bytes) >= thresh && u < units.length - 1);
    return bytes.toFixed(fix ? fix : 2) + ' ' + units[u];
}

/**
 * 生成文件列表
 * https://github.com/xiaomlove/nexusphp/issues/27
 * https://ourbits.club/assets/js/common.js
 * @param tree
 * @param par
 * @returns {string}
 */
 function build_tree(tree, par = '') {
    let ret = '';
    for (let k in tree) {
        let v = tree[k];
        let dep = (par.match(/\//g) || []).length;
        if (typeof v == 'object') {
            ret += "<tr " + (par === "" ? "" : "style='display:none' data-par = \"" + par + "\" ") + "><td class='rowfollow' data-name='" + k + "'>" + "&ensp;".repeat(dep * 2) + "<a href='javascript: void(0);'><b>" + k + "</b></a></td>";
            ret += build_tree(v, par + "/" + k);
        } else {
            ret += "<tr " + (par === "" ? "" : "style='display:none' data-par = \"" + par + "\" ") + "><td class=rowfollow data-name='" + k + "'>" + "&ensp;".repeat(dep * 2) + k + "</td><td class=rowfollow align='right' data-size='" + v + "'>" + humanFileSize(v, 2, false) + "</td></tr>";
        }
    }
    return ret;
}


function viewfilelist(torrentid)
{
	$.get('viewfilelist.php', {"id": torrentid}, data => {
		document.getElementById("showfl").style.display = 'none';
		document.getElementById("hidefl").style.display = 'block';

		let file_list_html = "<table class=\"main\" border=\"1\" cellspacing=0 cellpadding=\"5\">";
		file_list_html += "<tr><td class=colhead>路径</td><td class=colhead align=center><img class=\"size\" src=\"/pic/trans.gif\" alt=\"size\" /></td></tr>";
		file_list_html += build_tree(data);
		file_list_html += "</table>";
		$('#filelist').html(file_list_html);
		$('#filelist a').click(function () {
			let that = $(this);
			let parent = that.parents('tr:eq(0)');
			let par = parent.attr('data-par');
			let expand = (par ? par: "") + "/" + that.text();

			$('#filelist tr[data-par^="'+ expand +'/"]').hide();  // 首先隐藏所有对应子项
			$('#filelist tr[data-par$="'+ expand +'"]').toggle();  // 然后对当前项可见性进行切换
		})
	});
}

function hidefilelist()
{
document.getElementById("hidefl").style.display = 'none';
document.getElementById("showfl").style.display = 'block';
document.getElementById("filelist").innerHTML="";
}

//viewpeerlist.js

function viewpeerlist(torrentid)
{
var list=ajax.gets('viewpeerlist.php?id='+torrentid);
document.getElementById("showpeer").style.display = 'none';
document.getElementById("hidepeer").style.display = 'block';
document.getElementById("peercount").style.display = 'none';
document.getElementById("peerlist").innerHTML=list;
}
function hidepeerlist()
{
document.getElementById("hidepeer").style.display = 'none';
document.getElementById("peerlist").innerHTML="";
document.getElementById("showpeer").style.display = 'block';
document.getElementById("peercount").style.display = 'block';
}

// smileit.js

function SmileIT(smile,form,text){
   document.forms[form].elements[text].value = document.forms[form].elements[text].value+" "+smile+" ";
   document.forms[form].elements[text].focus();
}

// saythanks.js

function saythanks(torrentid)
{
var list=ajax.post('thanks.php','','id='+torrentid);
document.getElementById("thanksbutton").innerHTML = document.getElementById("thanksadded").innerHTML;
document.getElementById("nothanks").innerHTML = "";
document.getElementById("addcuruser").innerHTML = document.getElementById("curuser").innerHTML;
}

// preview.js

function preview(obj) {
	var poststr = encodeURIComponent( document.getElementById("body").value );
	var result=ajax.posts('preview.php','body='+poststr);
	document.getElementById("previewouter").innerHTML=result;
	document.getElementById("previewouter").style.display = 'block';
	document.getElementById("editorouter").style.display = 'none';
	document.getElementById("unpreviewbutton").style.display = 'block';
	document.getElementById("previewbutton").style.display = 'none';
}

function unpreview(obj){
	document.getElementById("previewouter").style.display = 'none';
	document.getElementById("editorouter").style.display = 'block';
	document.getElementById("unpreviewbutton").style.display = 'none';
	document.getElementById("previewbutton").style.display = 'block';
}

// java_klappe.js

function klappe(id)
{
var klappText = document.getElementById('k' + id);
var klappBild = document.getElementById('pic' + id);

if (klappText.style.display == 'none') {
 klappText.style.display = 'block';
 // klappBild.src = 'pic/blank.gif';
}
else {
 klappText.style.display = 'none';
 // klappBild.src = 'pic/blank.gif';
}
}

function klappe_news(id)
{
var klappText = document.getElementById('k' + id);
var klappBild = document.getElementById('pic' + id);

if (klappText.style.display == 'none') {
 klappText.style.display = '';
 klappBild.className = 'minus';
}
else {
 klappText.style.display = 'none';
 klappBild.className = 'plus';
}
}
function klappe_ext(id)
{
var klappText = document.getElementById('k' + id);
var klappBild = document.getElementById('pic' + id);
var klappPoster = document.getElementById('poster' + id);
if (klappText.style.display == 'none') {
 klappText.style.display = 'block';
 klappPoster.style.display = 'block';
 klappBild.className = 'minus';
}
else {
 klappText.style.display = 'none';
 klappPoster.style.display = 'none';
 klappBild.className = 'plus';
}
}

// disableother.js

function disableother(select,target)
{
	if (document.getElementById(select).value == 0)
		document.getElementById(target).disabled = false;
	else {
	document.getElementById(target).disabled = true;
	document.getElementById(select).disabled = false;
	}
}

function disableother2(oricat,newcat)
{
	if (document.getElementById("movecheck").checked == true){
		document.getElementById(oricat).disabled = true;
		document.getElementById(newcat).disabled = false;
	}
	else {
		document.getElementById(oricat).disabled = false;
		document.getElementById(newcat).disabled = true;
	}
}

// ctrlenter.js
var submitted = false;
function ctrlenter(event,formname,submitname){
	if (submitted == false){
	var keynum;
	if (event.keyCode){
		keynum = event.keyCode;
	}
	else if (event.which){
		keynum = event.which;
	}
	if (event.ctrlKey && keynum == 13){
		submitted = true;
		document.getElementById(formname).submit();
		}
	}
}
function gotothepage(page){
var url=window.location.href;
var end=url.lastIndexOf("page");
url = url.replace(/#[0-9]+/g,"");
if (end == -1){
if (url.lastIndexOf("?") == -1)
window.location.href=url+"?page="+page;
else
window.location.href=url+"&page="+page;
}
else{
url = url.replace(/page=.+/g,"");
window.location.href=url+"page="+page;
}
}
function changepage(event){
var gotopage;
var keynum;
var altkey;
if (navigator.userAgent.toLowerCase().indexOf('presto') != -1)
altkey = event.shiftKey;
else altkey = event.altKey;
if (event.keyCode){
	keynum = event.keyCode;
}
else if (event.which){
	keynum = event.which;
}
if(altkey && keynum==33){
if(currentpage<=0) return;
gotopage=currentpage-1;
gotothepage(gotopage);
}
else if (altkey && keynum == 34){
if(currentpage>=maxpage) return;
gotopage=currentpage+1;
gotothepage(gotopage);
}
}
if(window.document.addEventListener){
window.addEventListener("keydown",changepage,false);
}
else{
window.attachEvent("onkeydown",changepage,false);
}

// bookmark.js
function bookmark(torrentid,counter)
{
var result=ajax.gets('bookmark.php?torrentid='+torrentid);
bmicon(result,counter);
}
function bmicon(status,counter)
{
	if (status=="added")
		document.getElementById("bookmark"+counter).innerHTML="<img class=\"bookmark\" src=\"pic/trans.gif\" alt=\"Bookmarked\" />";
	else if (status=="deleted")
		document.getElementById("bookmark"+counter).innerHTML="<img class=\"delbookmark\" src=\"pic/trans.gif\" src=\"pic/trans.gif\" alt=\"Unbookmarked\" />";
}

// check.js
var checkflag = "false";
function check(field,checkall_name,uncheckall_name) {
	if (checkflag == "false") {
		for (i = 0; i < field.length; i++) {
			field[i].checked = true;}
			checkflag = "true";
			return uncheckall_name; }
			else {
				for (i = 0; i < field.length; i++) {
					field[i].checked = false; }
					checkflag = "false";
					return checkall_name; }
}

// in torrents.php
var form='searchbox';
function SetChecked(chkName,ctrlName,checkall_name,uncheckall_name,start,count) {
	dml=document.forms[form];
	len = dml.elements.length;
	var begin;
	var end;
	if (start == -1){
	begin = 0;
	end = len;
	}
	else{
	begin = start;
	end = start + count;
	}
	var check_state;
	for( i=0 ; i<len ; i++) {
		if(dml.elements[i].name==ctrlName)
		{
			if(dml.elements[i].value == checkall_name)
			{
				dml.elements[i].value = uncheckall_name;
				check_state=1;
			}
			else
			{
				dml.elements[i].value = checkall_name;
				check_state=0;
			}
		}

	}
	for( i=begin ; i<end ; i++) {
		if (dml.elements[i].name.indexOf(chkName) != -1) {
			dml.elements[i].checked=check_state;
		}
	}
}

// funvote.js
function funvote(funid,yourvote)
{
var result=ajax.gets('fun.php?action=vote&id='+funid+"&yourvote="+yourvote);
voteaccept(yourvote);
}
function voteaccept(yourvote)
{
	if (yourvote=="fun" || yourvote=="dull"){
		document.getElementById("funvote").style.display = 'none';
		document.getElementById("voteaccept").style.display = 'block';
	}
}

// in upload.php
function getname()
{
var filename = document.getElementById("torrent").value;
var filename = filename.toString();
var lowcase = filename.toLowerCase();
var start = lowcase.lastIndexOf("\\"); //for Google Chrome on windows
if (start == -1){
start = lowcase.lastIndexOf("\/"); // for Google Chrome on linux
if (start == -1)
start == 0;
else start = start + 1;
}
else start = start + 1;
var end = lowcase.lastIndexOf("torrent");
var noext = filename.substring(start,end-1);
noext = noext.replace(/H\.264/ig,"H_264");
noext = noext.replace(/5\.1/g,"5_1");
noext = noext.replace(/2\.1/g,"2_1");
noext = noext.replace(/\./g," ");
noext = noext.replace(/H_264/g,"H.264");
noext = noext.replace(/5_1/g,"5.1");
noext = noext.replace(/2_1/g,"2.1");
document.getElementById("name").value=noext;
}

// in userdetails.php
function getusertorrentlistajax(userid, type, blockid)
{
if (document.getElementById(blockid).innerHTML==""){
var infoblock=ajax.gets('getusertorrentlistajax.php?userid='+userid+'&type='+type);
document.getElementById(blockid).innerHTML=infoblock;
}
return true;
}

// in functions.php
function get_ext_info_ajax(blockid,url,cache,type)
{
if (document.getElementById(blockid).innerHTML==""){
var infoblock=ajax.gets('getextinfoajax.php?url='+url+'&cache='+cache+'&type='+type);
document.getElementById(blockid).innerHTML=infoblock;
}
return true;
}

// in userdetails.php
function enabledel(msg){
document.deluser.submit.disabled=document.deluser.submit.checked;
alert (msg);
}

function disabledel(){
document.deluser.submit.disabled=!document.deluser.submit.checked;
}

// in mybonus.php
function customgift()
{
if (document.getElementById("giftselect").value == '0'){
document.getElementById("giftselect").disabled = true;
document.getElementById("giftcustom").disabled = false;
}
}
