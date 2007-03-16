<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>Edit Html</title>
  <link rel='stylesheet' href='../css/editor.css' type='text/css' />
  <script type='text/javascript' src='../../common/js/x.js'></script>
  <script type='text/javascript' src='../../common/js/common.js'></script>
  <script type='text/javascript' src='../js/editor.js'></script>
  <script type='text/javascript'>
    function setText() {
      if(typeof(opener)=='undefined') return;
      var text = opener.editorGetSelectedHtml(opener.editorPrevSrl);
      var fo_obj = xGetElementById('fo');
      fo_obj.text.value = text;
      self.focus();
    }

    function insertHtml() {
      if(typeof(opener)=='undefined') return;
      var fo_obj = xGetElementById('fo');
      var text = fo_obj.text.value;
      if(!text) return;
      opener.editorInsertHTML(text);
      opener.focus();
      self.close();
    }
    xAddEventListener(window, 'load', setText);
    xAddEventListener(window, 'load', setFixedPopupSize); 
  </script>
</head>
<body class="editor_pop_body">
  <form action='./' method='post' id='fo' onSubmit="return false" style="display:inline">
  <div class="editor_window">
    <div><textarea name="text" id='editor' class="editor_textarea"></textarea></div>
    <div><input type='button' id='manual_url_submit' class="editor_submit" value='insert' onClick='insertHtml()' /></div>
  </div>
  </form>

</body>
</html>
