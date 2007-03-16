<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>change Color</title>
  <link rel='stylesheet' href='../editor.css' type='text/css' />
  <script type='text/javascript' src='../../../common/js/x.js'></script>
  <script type='text/javascript' src='../editor.js'></script>
  <script type='text/javascript'>
    var color_list = new Array('000000','993300','333300','003300','003366','000080','333399','333333','800000','FF6600','808000','008000','008080','0000FF','666699','808080','FF0000','FF9900','99CC00','339966','33CCCC','3366FF','800080','969696','FF00FF','FFCC00','FFFF00','00FF00','00FFFF','00CCFF','993366','c0c0c0','FF99CC','FFCC99','FFFF99','CCFFCC','CCFFFF','99CCFF','CC99FF','FFFFFF');

    function printColorBox() {
      var html = '';
      for(var i=0;i<color_list.length;i++) {
        html += "<div style=\"float:left;background-color:#"+color_list[i]+"\"><img src=\"..\/images\/blank.gif\" class=\"color_icon\" onmouseover=\"this.className='color_icon_over'\" onmouseout=\"this.className='color_icon'\" onclick=\"setColor('"+color_list[i]+"')\" \/><\/div>";
      }
      document.write(html);
    }

    function setColor(color) {
      var mode = "<?=$_REQUEST['mode']?>";
      opener.editorFocus(opener.editorPrevSrl);
      if(mode == "ForeColor") {
        opener.editorSetForeColor("#"+color);
      } else {
        opener.editorSetBackColor("#"+color);
      }
      opener.editorFocus(opener.editorPrevSrl);
      self.close();
    }

  </script>
</head>
<body class="editor_pop_body">
  <script type="text/javascript">
    printColorBox();  
  </script>
</body>
</html>
