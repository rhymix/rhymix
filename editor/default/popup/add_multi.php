<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>add Multimedia</title>
  <link rel='stylesheet' href='../editor.css' type='text/css' />
  <script type='text/javascript' src='../../../common/js/x.js'></script>
  <script type='text/javascript' src='../editor.js'></script>
  <script type='text/javascript'>
    function insertMultimedia() {
      if(typeof(opener)=='undefined') return;

      var fo_obj = xGetElementById("fo_multimedia");
      var url = fo_obj.url.value;
      var width = fo_obj.width.value;
      var height = fo_obj.height.value;
      if(!width) width = 540;
      if(!height) height = 400;
      if(url) opener.emoticonInsertMultimedia(url, width, height);
      opener.focus();
      self.close();
    }
  </script>
</head>
<body class="editor_pop_body">
  <form id="fo_multimedia" action='./' method='post' onSubmit="return false" style="display:inline">
  <div id='zone_AddUrl' class="editor_window">
    <table width="400" border="0" cellspacing="1" cellpadding="0">
    <col width="50" />
    <col width="*" />
    <tr>
      <td class="editor_field">url</td>
      <td><input type='text' name='url' class="editor_input" value=''/></td>
    </tr>
    <tr>
      <td class="editor_field">width</td>
      <td><input type='text' name='width' style="width:100px" class="editor_input" value='540'/></td>
    </tr>
    <tr>
      <td class="editor_field">height</td>
      <td><input type='text' name='height' style="width:100px" class="editor_input" value='400'/></td>
    </tr>
    <tr>
      <td colspan="2"><input type='button' class="editor_submit" value='Insert' onClick='insertMultimedia()' /></td>
    </tr>
    </table>
  </div>
  </form>

</body>
</html>
