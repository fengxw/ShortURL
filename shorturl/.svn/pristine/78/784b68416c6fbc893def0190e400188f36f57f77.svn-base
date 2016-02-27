/*-------------------------Code copiers----------------------------*/
function copy_code() {
  el = document.getElementById("aurl");
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