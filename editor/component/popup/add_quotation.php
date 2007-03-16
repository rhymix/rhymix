<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>add Quotation</title>
  <link rel='stylesheet' href='../editor.css' type='text/css' />
  <script type='text/javascript' src='../../../common/js/x.js'></script>
  <script type='text/javascript' src='../editor.js'></script>
  <script type='text/javascript'>
    var color_list = new Array('000000','993300','333300','003300','003366','000080','333399','333333','800000','FF6600','808000','008000','008080','0000FF','666699','808080','FF0000','FF9900','99CC00','339966','33CCCC','3366FF','800080','969696','FF00FF','FFCC00','FFFF00','00FF00','00FFFF','00CCFF','993366','c0c0c0','FF99CC','FFCC99','FFFF99','CCFFCC','CCFFFF','99CCFF','CC99FF','FFFFFF');

    function setText() {
      if(typeof(opener)=='undefined') return;
      var text = opener.editorGetSelectedHtml(opener.editorPrevSrl);
      var fo_obj = xGetElementById('fo');
      fo_obj.text.value = text;
      self.focus();
    }

    function change_type(obj) {
      xGetElementById('quotation').style.display = 'none';
      xGetElementById('htmlcode').style.display = 'none';
      xGetElementById('phpcode').style.display = 'none';
      xGetElementById('fold').style.display = 'none';

      var editor_obj = xGetElementById('editor');

      var val = obj.value;
      xGetElementById(val).style.display = 'block';
      switch(val) {
        case 'quotation' :
            xHeight(editor_obj,240);
          break;
        case 'htmlcode' :
            xHeight(editor_obj,240);
          break;
        case 'phpcode' :
            xHeight(editor_obj,240);
          break;
        case 'fold' :
            xHeight(editor_obj,150);
          break;
      }
    }

    function change_color(obj) {
      var code = obj.options[obj.selectedIndex].value;
      obj.style.backgroundColor = "#"+code;
    }

    function write_color_list(field_name) {
      var idx = '';
      switch(field_name) {
        case 'bgcolor' :
            idx = color_list.length-1;
          break;
        case 'bordercolor' :
            idx = 35;
          break;
      }
      var html = "<select name='"+field_name+"' id='"+field_name+"' onChange='change_color(this);' style='width:40px;'>";
      for(var i=0;i<color_list.length;i++) {
        html += "<option value='"+color_list[i]+"' ";
        if(i == idx) html += " selected ";
        html += "style='background-color:#"+color_list[i]+"'></option>";
      } 
      html += "</select>";
      document.write(html);
    }

    function getSelectValue(obj) {
      return obj.options[obj.selectedIndex].value;
    }

    function insertQuotation() {
      if(typeof(opener)=='undefined') return;

      var fo_obj = xGetElementById('fo');

      var bgcolor = getSelectValue(fo_obj.bgcolor);
      var bordercolor = getSelectValue(fo_obj.bordercolor);
      var bordertype = 'background-color:#'+bgcolor+';';
      switch(getSelectValue(fo_obj.bordertype)) {
        case 'solid' :
            bordertype += "border:1px solid #"+bordercolor+";";
          break;
        case 'dotted' :
            bordertype += "border:1px dotted #"+bordercolor+";";
          break;
        case 'none' :
            bordertype += "border:0px;";
          break;
      }

      var bordercolor = getSelectValue(fo_obj.bordercolor);

      var text = fo_obj.text.value;
      var html = '';

      if(!text) return;

      if(xGetElementById('type_fold').checked) {
        var opentext = fo_obj.opentext.value;
        if(!opentext) opentext = 'more...';
        var closetext = fo_obj.closetext.value;
        if(!closetext) closetext = 'close';

        var link_type = fo_obj.link_type.options[fo_obj.link_type.selectedIndex].value;
        if(fo_obj.bold.checked) link_type = 'bold '+link_type;

        var id = Math.round(Math.random()*1000000);

        html = '<div id="_folder_open_'+id+'" class="folder_opener"><a href="#" onClick="svc_folder_open(\''+id+'\');return false;" class="'+link_type+'" style="text-decoration:none;">'+opentext+'</a></div>';
        html += '<div id="_folder_close_'+id+'" class="folder_closer"><a href="#" onClick="svc_folder_close(\''+id+'\');return false;" class="'+link_type+'" style="text-decoration:none;">'+closetext+'</a></div>';
        html += '<div id="_folder_'+id+'" class="folder_area" style="padding:10px; '+bordertype+'">'+text+'</div>';
        html = "<div>"+html+"</div>";
      } else {
        html = '<div style="padding:10px; '+bordertype+'">'+text+'</div>';
      }

      opener.editorInsertQuotation(html);

      opener.focus();
      self.close();
    }
    xAddEventListener(window, 'load', setText);
    </script>
</head>
<body class="editor_pop_body">
  <form action='./' method='post' id='fo' onSubmit="return false" style="display:inline">
  <div id='zone_Quotation' class="editor_window">
    <div class="quotation_box">
      <input type="radio" id="type_quotation" name="quotation_type" value="quotation" checked onClick="change_type(this)" /> quotation 
      <input type="radio" id="type_fold" name="quotation_type" value="fold" onClick="change_type(this)" /> fold
    </div>

    <div class="quotation_type">
      <table border="0" width="100%">
      <col width="100" />
      <col width="*" />
      <tr>
        <td class="editor_field">bg color</td>
        <td class="editor_area"><script type="text/javascript">write_color_list("bgcolor");</script></td>
      </tr>
      <tr>
        <td class="editor_field">border type</td>
        <td class="editor_area">
          <select name="bordertype">
            <option value="solid">solid</option>
            <option value="dotted">dotted</option>
            <option value="none">none</option>
          </select>
        </td>
      </tr>
      <tr>
        <td class="editor_field">border color</td>
        <td class="editor_area"><script type="text/javascript">write_color_list("bordercolor");</script></td>
      </tr>
      </table>
    </div>

    <div class="quotation_type" id="quotation">
    </div>

    <div class="quotation_type" id="htmlcode" style="display:none"></div>

    <div class="quotation_type" id="phpcode" style="display:none"></div>

    <div class="quotation_type" id="fold" style="display:none">
      <table width="100%" border="0" cellspacing="1" cellpadding="0">
      <col width="100" />
      <col width="*" />
      <tr>
        <td class="editor_field">open text</td>
        <td><input type='text' name='opentext' class="editor_input" style="width:120px;" value='more...'/></td>
      </tr>
      <tr>
        <td class="editor_field">close text</td>
        <td><input type='text' name='closetext' class="editor_input" style="width:120px;" value='close' /></td>
      </tr>
      <tr>
        <td class="editor_field">bold</td>
        <td><input type='checkbox' name='bold' value='Y' /></td>
      </tr>
      <tr>
        <td class="editor_field">type</td>
        <td class="editor_area">
          <select name='link_type'>
            <option value=''>default</option>
            <option value='editor_blue_text'>blue</option>
            <option value='editor_red_text'>red</option>
            <option value='editor_green_text'>green</option>
            <option value='editor_yellow_text'>yellow</option>
          </select>
        </td>
      </tr>
      </table>
    </div>
    
    <div>
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td><textarea name="text" id='editor' class="editor_textarea" style="width:100%;height:240px;"><?=$_REQUEST['title']?></textarea></td>
      </tr>
      <tr>
        <td>
          <input type='button' id='manual_url_submit' class="editor_submit" value='Insert' onClick='insertQuotation()' />
        </td>
      </tr>
      </table>
    </div>
  </div>
  </form>

</body>
</html>
