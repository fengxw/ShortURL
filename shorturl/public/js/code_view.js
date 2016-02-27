/*-------------------------Emotion---------------------------------*/
function add_emotion(event) {
  insertAtCursor(document.getElementById('comment'), " " + this.alt + " ");
  $("#comment").focus();
}

function load_emotion() {
  var obj = document.getElementById('comment');
  if (obj) {
    $('#emotion').children('img').bind("click", add_emotion);
  }
}

/*-------------------------Rate and Reply--------------------------*/
function reply_at(username) {
  var obj = document.getElementById('comment');
  if (obj) {
    insertAtCursor(obj, "@" + username + ": ");
    $(obj).focus();
  } else {
    alert("发表评论需要登录本站");
  }
}

function hot_reload_cmt_rating(cmt_id)
{
  var myurl = "/code/comment/rates/" + cmt_id + "/";
  $.ajax({
    type: "GET",
    dataType: 'text',
    url: myurl,
    success: function(msg) {
        eval(msg);
        $("#" + cmt_id + "_up").text(rate_up);
        $("#" + cmt_id + "_dn").text(rate_down);
        rate_total = rate_up + rate_down;
        $("#" + cmt_id + "_pup").width(100 * rate_up / rate_total + "%");
        $("#" + cmt_id + "_pdn").width(100 * rate_down / rate_total + "%");
    },
    error: function(msg){ /*alert('操作失败:' + msg);*/ }
  });
}

function rate_cmt(rateupdown, cmt_id) {
  var myurl = "/code/comment/" + rateupdown + "/" + cmt_id + "/";
  $.ajax({
    type: "POST",
    dataType: 'text',
    url: myurl,
    success: function(msg) {
        hot_reload_cmt_rating(cmt_id);
    },
    error: function(msg){ /*alert('操作失败:' + msg);*/ }
  });
}

/*-------------------------Favorites-------------------------------*/
function add_to_fav(id) {
  var myurl = "/code/fav/add/" + id + "/";
  $.ajax({
    type: "POST",
    dataType: 'text',
    url: myurl,
    success: function(msg) {
        hot_reload_cmt_rating(id);
        $("#fav_oper").html(
          '<a href="#" onclick="del_from_fav(' + id + ');return false">取消收藏</a>');
        $("#fav_count").text(parseInt($("#fav_count").text(), 10) + 1);
    },
    error: function(msg){ /*alert('操作失败:' + msg);*/ }
  });
}

function del_from_fav(id) {
  var myurl = "/code/fav/del/" + id + "/";

  $.ajax({
    type: "POST",
    dataType: 'text',
    url: myurl,
    success: function(msg) {
        hot_reload_cmt_rating(id);
        $("#fav_oper").html('<a href="#" onclick="add_to_fav(' + id + ');return false">收藏这篇文章</a>');
        $("#fav_count").text(parseInt($("#fav_count").text(), 10) - 1);
    },
    error: function(msg){ /*alert('操作失败:' + msg);*/ }
  });
}

/*-------------------------Line number toggler---------------------*/
function zero_fill(num, digits) {
  var s = "" + num;
  while (s.length < digits)
    s = "0" + s;
  return s;
}

//remove span during on empty span
//
function toggle_linenum() {
  var source = document.getElementById("codee_html");
  var brs = null;
  var k = 0;
  if (linenum_is_on) {
    //remove "span" before each "br"
    //[1/2]remove the first
    brs = source.getElementsByTagName("br");
    var br_parent = brs[0].parentNode;
    br_parent.removeChild(br_parent.firstChild);
    //[2/2]remove the rest
    for (k=0; k < brs.length - 1; k++) {
      br_parent.removeChild(brs[k].nextSibling);
    }
    linenum_is_on = false;
  } else {
    fn_lineno = CodeeStyles["lineno"];
    fn_special = CodeeStyles["special"];
    brs = source.getElementsByTagName("br");
    var width = Math.floor(Math.LOG10E * Math.log(brs.length) + 1);
    //[1/2]insert the first lineno span
    var spanholder = document.createElement('span');
    fn_lineno(spanholder);
    spanholder.innerHTML = zero_fill(1, width) + ' ';
    brs[0].parentNode.insertBefore(spanholder, brs[0].parentNode.firstChild);
    //[2/2]insert the rest lineno span
    for (k=0; k < brs.length - 1; k++) {
      var ele = brs[k];
      spanholder = document.createElement('span');
      spanholder.innerHTML = zero_fill(k+2, width) + ' ';
      if ((k+2) % 5 === 0)
        fn_special(spanholder);
      else
        fn_lineno(spanholder);
      ele.parentNode.insertBefore(spanholder, ele.nextSibling);
    }
    linenum_is_on = true;
  }

  var d = new Date();
  d.setDate(d.getDate() + 3000);
  set_cookie('codee_linenum', linenum_is_on ? "on" : "off", d, '/');
}

/*-------------------------Code copiers----------------------------*/
function copy_code() {
  el = document.getElementById("codee_html");
  var spanholder = null;
  if (el && window.getSelection) { // FF, Safari, Opera
    var sel = window.getSelection();
    range = document.createRange();
    range.selectNodeContents(el);
    sel.removeAllRanges();
    sel.addRange(range);
  } else if (el) { // IE
    document.selection.empty();
    range = document.body.createTextRange();
    range.moveToElementText(el);
    range.select();
    range.execCommand("Copy");
    alert("高亮代码已经复制到剪贴板，在可视化编辑器里面粘贴即可");
  }
}

function copy_text(str) {
  if (window.clipboardData) {
    window.clipboardData.setData('text', str);
  } else {
    var flashcopier = 'flashcopier';
    if(!document.getElementById(flashcopier)) {
      var divholder = document.createElement('div');
      divholder.id = flashcopier;
      document.body.appendChild(divholder);
    }
    document.getElementById(flashcopier).innerHTML = '';
    var divinfo = '<embed src="/static/flash/clipboard.swf" FlashVars="clipboard='+encodeURIComponent(str)+'" width="0" height="0" type="application/x-shockwave-flash"></embed>';
    document.getElementById(flashcopier).innerHTML = divinfo;
  }
}

function get_code_html() {
  var str = document.getElementById("codee_html").innerHTML;
  str = str.replace(/>([^<]*?)'(?=[^<]*<)/g, ">$1&#39;");
  str = str.replace(/>([^<]*?)'(?=[^<]*<)/g, ">$1&#39;");
  str = str.replace(/>([^<]*?)"(?=[^<]*<)/g, ">$1&quot;");
  str = str.replace(/>([^<]*?)"(?=[^<]*<)/g, ">$1&quot;");
  str = str.replace(/\s$/g, "");
  str = str.replace(/^\s/g, "");
  //去掉开头结尾的空格
  return str;
}

function show_in_textbox(str) {
  if (!document.getElementById('codetextbox')) {
    $('<div id="codetextbox" style="margin-top:10px;margin-bottom:10px;width:965px;background:#eee;border:1px solid #00ff66;color:#000;"><p onclick="$(\'#codetextbox\').hide();return false;">已经复制到剪贴板，以下为剪贴板内容，如有需要可手动复制(<a href="#" onclick="$(\'#codetextbox\').hide();return false;">点击此处隐藏本框</a>) --HTML</p><textarea onclick="select()" type="text" style="width:950px;height:100px;margin:5px;overflow-x:visible;overflow-y:visible;"></textarea></div>').insertBefore('#codee_html');
  }
  $('#codetextbox').show();
  $('#codetextbox > textarea').val(str);
}
function copy_html() {
  var result = get_code_html();
  copy_text(result);
  show_in_textbox(result);
}

function copy_raw(id) {
  $.ajax({
    type: "GET",
    dataType: 'text',
    url: "/code/view/" + id + "/raw/",
    success: function(msg) {
      copy_text(msg);
      show_in_textbox(msg);
    },
    error: function(msg){ alert('操作失败:' + msg); }
  });
}

function zero_fill_hex(num, digits) {
  var s = num.toString(16);
  while (s.length < digits)
    s = "0" + s;
  return s;
}
function rgb2hex(rgb) {
  //nnd, Firefox / IE not the same, fxck
  if (rgb.charAt(0) == '#')
    return rgb;
  var n = Number(rgb);
  var ds = rgb.split(/\D+/);
  var decimal = Number(ds[1]) * 65536 + Number(ds[2]) * 256 + Number(ds[3]);
  return "#" + zero_fill_hex(decimal, 6);
}

function copy_qqcode() {
  //[B]aaa[/B]  [I]aaa[/I]  [ftc=#000000]aaa[/ft]  [ftf=Arial]a[/ft]
  //[url=http://example.com]aaaa[/url]
  copy_xxcode("ftc", "ft", "ftf", "ft");
}

function copy_bbcode(color_pre, color_post, font_pre, font_post) {
  copy_xxcode("color", "color", "font", "font");
}

function copy_xxcode(color_pre, color_post, font_pre, font_post) {
  var codee = $("#codee_html");
  var bgColor = codee.css('background-color');
  /*title: hack for first child*/
  var title = $("#codee_html div:first-child").html();
  title = title.replace(/<a href="/, "[url=").replace(/">/, "]").replace(/<\/a>/, "[/url]");
  title = title.replace(/</g, "[").replace(/>/g, "]");
  /*code: hack for last child*/
  var code = "";
  childs = $("#codee_html div:last-child")[0].childNodes;
  for (var i=0; i<childs.length; i++) {
    var t = $(childs[i]);
    var txt = t.text();
    if (t.is('br')) {
      code += "\n";
    } else if (t.is("span")) {
      if (txt !== "" && !(/^(&nbsp;| )+$/.test(txt))) {
        if (t.css('color') != undefined) {
          txt = "[" + color_pre + "=" + rgb2hex(t.css('color')) + "]" + txt + "[/" + color_post + "]";
        }
        if (t.css('text-decoration') == "underline") {
          txt = "[u]" + txt + "[/u]";
        }
        if (t.css('font-weight') == "bold") {
          txt = "[b]" + txt + "[/b]";
        }
        if (t.css('font-style') == "italic") {
          txt = "[i]" + txt + "[/i]";
        }
      }
      code += txt;
    } else {
      code += childs[i].data;
    }
  }
  var font = $("#code_font").val();
  var result = "[" + font_pre + "=" + font + "]" + title + "\n" + code + "[/" + font_post + "]";
  copy_text(result);
  show_in_textbox(result);
}

/*-------------------------Code Themes-----------------------------*/
/* below functions(start with "_") stand for style changes */
function _c(ele, p) {
  ele.style.color = p;
}
function _fs(ele, p) {
  ele.style.fontStyle = p;
}
function _bk(ele, p) {
  ele.style.backgroundColor = p;
}
function _fw(ele, p) {
  ele.style.fontWeight = p;
}
function _td(ele, p) {
  ele.style.textDecoration = p;
}
function _bd(ele, p) {
  ele.style.borderColor = p;
  ele.style.borderWidth = "1px";
  ele.style.borderStyle = "solid";
}

//clean up extra tags
//remove 'class' attribute from span
//and remove span tag if it doesn't have style attribute
function clean_up_extra_tags() {
  //remove codee html classes
  var source = document.getElementById("codee_html");
  var spans = source.getElementsByTagName("span");
  for (var k=0,ele; ele=spans[k]; k++) {
    /*if (ele.className.charAt(0) == '_') {*/
    //lineno class name ALSO been deleted
      ele.removeAttribute("class", 0);
      ele.removeAttribute("className", 0);
    /*}*/
  }

  var str = source.innerHTML;
  //remove "span" with empty style
  str = str.replace(/<span>([^<]*)<\/span>/gi, "$1");
  //remove "span" with space/tab as text
  str = str.replace(/<span[^>]*>([(&nbsp;|\s)]*)<\/span>/gi, "$1");
  source.innerHTML = str;

  //@merge the same style spans?
  //and, any more things to do? see the result html for more information
}

/*actually make the style changes upon <span> elements*/
function do_style_change(set_bk) {
  /*NOTICE: we assume all span tags belong to codee tags*/
  /*not using jQuery here because:
   * it add jQueryxxxxx tag to any target object been changed*/
  var obj_html = document.getElementById("codee_html");
  var source = $(obj_html).children(".source");
  var f = CodeeStyles['_'];
  if (f && source.length > 0)
    f(source[0]);
  var spans = obj_html.getElementsByTagName("span");
  for (var k=0,ele; ele=spans[k]; k++) {
    ele.removeAttribute("style", 0);
    f = CodeeStyles[ele.className];
    if (!f)
      f = CodeeStyles['_'];
    if (f)
      f(ele);
  }

  //set background if needed
  if (set_bk) {
    var color = "#F9F7ED";
    if (CodeeStyles['_back'])
      color = CodeeStyles['_back'];
    do_bk_change(color);
  }
}

function change_style(style) {
  //load codee html cache
  var source = $("#codee_html").children(".source");
  source.html(originalSource);

  //do style change as usual
  change_style_by_json(style);
}

function change_style_by_json(style) {
  if (!document.getElementById("theme")) return;

  var d = new Date();
  d.setDate(d.getDate() + 3000);
  var set_bk = true;
  if (!style) {
    set_bk = false;
    style = get_cookie('codee_style');
    if (!style) {
      style = "default";
    } else {
      /*set by cookie, change theme option*/
      $("#theme").val(style);
    }
  } else
    set_cookie('codee_style', style, d, '/');

  var myurl = '/static/styles/pyg/' + style + '.json';

  $.ajax({
    type: "GET",
    async: false,
    dataType: 'text',
    url: myurl,
    success: function(msg) {
      eval(msg); //here we got the CodeeStyles
      CodeeStyles['lineno special'] = CodeeStyles['special'];//fix the lineno issue
      do_style_change(set_bk);
      toggle_linenum_by_cookie();
      clean_up_extra_tags();
    },
    error: function(msg){ /*alert('操作失败:' + msg);*/ }
  });
}

/*get an element's given style name value*/
function get_style(ele, css) {
  var val = "";
  if (document.defaultView && document.defaultView.getComputedStyle) {
    val = document.defaultView.getComputedStyle(ele, "").getPropertyValue(css);
  } else if(ele.currentStyle) {
    css = css.replace(/\-(\w)/g, function(strMatch, p1) {
        return p1.toUpperCase();
        });
    val = ele.currentStyle[css];
  }
  return val;
}

/*-------------------------Background------------------------------*/
function do_bk_change(color) {
  $("#codee_html").css("background-color", color);
  $("#codee_html").children(".source").css("background-color", color);

  var d = new Date();
  d.setDate(d.getDate() + 3000);
  set_cookie("codee_bk", color, d, '/');
}

function set_bk(ele) {
  if(!$("#codee_html").length) return;

  color = "";
  if (ele)
    color = get_style(ele, 'background-color');
  else {
    color = get_cookie("codee_bk");
    if (!color)
      color = CodeeStyles["_back"];
  }
  if (!color) return;
  do_bk_change(color);
}

function change_code_font(font) {
  if (!document.getElementById("codee_html")) return;

  var d = new Date();
  d.setDate(d.getDate() + 3000);
  if (!font) {
    font = get_cookie('codee_font');
    if (!font)
      font = $("#code_font")[0].options[0];
    else
      $("#code_font").val(font);
  } else
    set_cookie('codee_font', font, d, '/');

  var work_fonts = get_fonts();
  font_family = '"' + font + '"';
  for (var i=0; i<Math.min(3, work_fonts.length / 2); i++) {
    other_font = work_fonts[i * 2 + 1];
    if (other_font != font) {
      font_family += ',"' + other_font + '"';
    }
  }

//  $("#codee_html").css('font-family', font_family);
  $("#codee_html").children(".source").css('font-family', font_family);
}

/*-------------------------Font operations-------------------------*/
function get_fonts() {
  var fonts = [
    'Consolas', 'Consolas',
    'Lucida', 'Lucida Console',
    'Courier New', 'Courier New',
    'Bitstream', 'Bitstream Vera Sans Mono',
    'monospace', 'monospace',
    'Fixedsys', 'Fixedsys',
    'Monaco', 'Monaco',
    'Verdana', 'Verdana',
    'Comic', 'Comic Sans MS',
    '微软雅黑', 'Microsoft Yahei',
    'Tahoma', 'Tahoma'];
  var d = new Detector();
  var work_fonts = [];
  for (var i=0; i<fonts.length / 2; i++) {
    var font = fonts[i * 2 + 1];
    var font_str = fonts[i * 2];
    if (d.test(font)[3]) {
      work_fonts.push(font_str);
      work_fonts.push(font);
    }
  }
  return work_fonts;
}

function load_fonts() {
  var obj = document.getElementById('code_font');
  if (!obj) return;
  work_fonts = get_fonts();
  options = obj.options;
  for (var i=0; i<work_fonts.length / 2; i++) {
    var font = work_fonts[i * 2 + 1];
    var font_str = work_fonts[i * 2];
    options[i] = new Option(font_str, font);
  }
}

/*-------------------------Other operations-------------------------*/
function cache_codee_html() {
  var source = $("#codee_html").children(".source");
  originalSource = source.html();
}

function toggle_linenum_by_cookie() {
  turnoff = get_cookie('codee_linenum') != "on";
  linenum_is_on = true; //Global variable
  if (turnoff) //initial state is ON
    toggle_linenum();
}

var originalSource = null;

/* Do initialize on document when it's been loaded */
$(document).ready(function() {
  load_fonts();
  change_code_font();
  cache_codee_html();
  change_style();
  set_bk();
});

var Detector = function(){
	var h = document.getElementsByTagName("BODY")[0];
	var d = document.createElement("DIV");
	var s = document.createElement("SPAN");
	d.appendChild(s);
	d.style.fontFamily = "sans-serif";
	s.style.fontFamily = "sans-serif";
	s.style.fontSize = "72px";
	s.innerHTML = "mmmmmmmmmml";
	h.appendChild(d);
	var defaultWidth   = s.offsetWidth;
	var defaultHeight  = s.offsetHeight;
	h.removeChild(d);
	function test(font) {
		h.appendChild(d);
		var f = [];
		f[0] = s.style.fontFamily = font;	// Name of the font
		f[1] = s.offsetWidth;				// Width
		f[2] = s.offsetHeight;				// Height
		h.removeChild(d);
		font = font.toLowerCase();
		if (font == "arial" || font == "sans-serif")
			f[3] = true;	// to set arial and sans-serif true
		else
			f[3] = (f[1] != defaultWidth || f[2] != defaultHeight);	// Detected?
		return f;
	}
	this.test = test;
};

