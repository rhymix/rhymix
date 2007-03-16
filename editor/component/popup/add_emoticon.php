<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>add Emoticon</title>
  <link rel='stylesheet' href='../editor.css' type='text/css' />
  <script type='text/javascript' src='../../../common/js/x.js'></script>
  <script type='text/javascript' src='../editor.js'></script>
  <script type='text/javascript'>
    function editorPrintEmoticon() {
      var html = '';
      for(var i=1;i<=40;i++) {
        var str = i;
        if(i<10) str = '0'+i;
        html += "<img src=\"../images/emoticon/msn0"+str+".gif\" onFocus=\"this.blur()\" style=\"margin:2px;width:19px;height:19px;cursor:pointer;border:1px solid;border-color:#CCCCCC;\" onMouseOver=\"this.style.borderColor='#ffffff'\" onMouseOut=\"this.style.borderColor='#CCCCCC'\" onClick=\"insertImage(this)\" />";
        if(i%8==0) html += "<br />";
      }
      document.write(html);
    }

    function insertImage(obj) {
      if(typeof(opener)=='undefined') return;

      opener.editorInsertEmoticon(obj);
      opener.editorFocus(opener.editorPrevSrl);
      self.close();
    }
  </script>
</head>
<body class="editor_pop_body">
<script type="text/javascript">
  editorPrintEmoticon();  
</script>
</body>
</html>
