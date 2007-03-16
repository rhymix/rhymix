<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>add Url</title>
  <link rel="stylesheet" href="../editor.css" type="text/css" />
  <script type="text/javascript" src="../../../common/js/x.js"></script>
  <script type="text/javascript" src="../editor.js"></script>
  <script type="text/javascript">
    function setText() {
      if(typeof(opener)=="undefined") return;
      var text = opener.editorGetSelectedHtml(opener.editorPrevSrl);
      var fo_obj = xGetElementById("fo");
      if(fo_obj.text.value) return;
      fo_obj.text.value = text;
      self.focus();
    }

    function insertUrl() {
      if(typeof(opener)=="undefined") return;

      var fo_obj = xGetElementById("fo");
      var text = fo_obj.text.value;
      var url = fo_obj.url.value;
      var link_type = fo_obj.link_type.options[fo_obj.link_type.selectedIndex].value;
      if(fo_obj.bold.checked) link_type = "bold "+link_type;
      if(text && url) opener.editorInsertUrl(text, url, link_type);

      opener.focus();
      self.close();
    }
    xAddEventListener(window, "load", setText);
  </script>
</head>
<body class="editor_pop_body">
  <form action="./" method="post" id="fo" onSubmit="return false" style="display:inline">
  <div id="zone_AddUrl" class="editor_window">
    <table width="380" border="0" cellspacing="1" cellpadding="0">
    <col width="50" />
    <col width="*" />
    <tr>
      <td class="editor_field">text</td>
      <td><textarea name="text" class="editor_small_textarea"><?=$_REQUEST["title"]?></textarea></td>
    </tr>
    <tr>
      <td class="editor_field">url</td>
      <td><input type="text" name="url" class="editor_input" value="<?=$_REQUEST["url"]?>"/></td>
    </tr>
    <tr>
      <td class="editor_field">bold</td>
      <td><input type="checkbox" name="bold" value="Y" /></td>
    </tr>
    <tr>
      <td class="editor_field">type</td>
      <td>
        <select name="link_type">
          <option value="">default</option>
          <option value="editor_blue_text">blue</option>
          <option value="editor_red_text">red</option>
          <option value="editor_green_text">green</option>
          <option value="editor_yellow_text">yellow</option>
        </select>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <input type="button" id="manual_url_submit" class="editor_submit" value="Insert" onClick="insertUrl()" />
      </td>
    </tr>
    </table>
  </div>
  </form>

</body>
</html>
