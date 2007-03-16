<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>add Image</title>
  <link rel='stylesheet' href='../css/editor.css' type='text/css' />
  <script type='text/javascript' src='../../common/js/x.js'></script>
  <script type='text/javascript' src='../../common/js/common.js'></script>
  <script type='text/javascript' src='../js/editor.js'></script>
  <script type='text/javascript'>
    function insertImage() {
      if(typeof(opener)=='undefined') return;

      var fo_obj = xGetElementById("fo");
      var url = fo_obj.url.value;
      var align = fo_obj.align.options[fo_obj.align.selectedIndex].value;
      if(url) {
        opener.editorInsertImage(url, align);
        opener.editorFocus(opener.editorPrevSrl);
      }
      self.close();
    }
    xAddEventListener(window, 'load', setFixedPopupSize); 
  </script>
</head>
<body class="editor_pop_body">
  <form action='./' method='post' id="fo" onSubmit="return false" style="display:inline">
  <div id='zone_AddUrl' class="editor_window">
    <table width="380" border="0" cellspacing="1" cellpadding="0">
    <col width="50" />
    <col width="*" />
    <tr>
      <td class="editor_field">url</td>
      <td><input type='text' name='url' class="editor_input" value='<?=$_GET['file_url']?>'/></td>
    </tr>
    <tr>
      <td class="editor_field">type</td>
      <td>
        <select name='align'>
          <option value=''>normal</option>
          <option value='left'>left</option>
          <option value='right'>right</option>
        </select>
      </td>
    </tr>
    <tr>
      <td colspan="2" height="40">
        <input type='button' class="editor_submit" value='Insert' onClick='insertImage()' />
      </td>
    </tr>
    </table>
  </div>
  </form>

</body>
</html>
