if(!xq) xq = {};
if(!xq.ui_templates) xq.ui_templates = {};

xq.ui_templates.basicColorPickerDialog='<form action="#" class="xqFormDialog xqBasicColorPickerDialog">\n		<div>\n			<label>\n				<input type="radio" class="initialFocus" name="color" value="black" checked="checked" />\n				<span style="color: black;">Black</span>\n			</label>\n			<label>\n				<input type="radio" name="color" value="red" />\n				<span style="color: red;">Red</span>\n			</label>\n				<input type="radio" name="color" value="yellow" />\n				<span style="color: yellow;">Yellow</span>\n			</label>\n			</label>\n				<input type="radio" name="color" value="pink" />\n				<span style="color: pink;">Pink</span>\n			</label>\n			<label>\n				<input type="radio" name="color" value="blue" />\n				<span style="color: blue;">Blue</span>\n			</label>\n			<label>\n				<input type="radio" name="color" value="green" />\n				<span style="color: green;">Green</span>\n			</label>\n			\n			<input type="submit" value="Ok" />\n			<input type="button" class="cancel" value="Cancel" />\n		</div>\n	</form>';
if(!xq) xq = {};
if(!xq.ui_templates) xq.ui_templates = {};

xq.ui_templates.basicIFrameDialog='<form action="#" class="xqFormDialog xqBasicIFrameDialog">\n		<table>\n			<tr>\n				<td>IFrame src:</td>\n				<td><input type="text" class="initialFocus" name="p_src" size="36" value="http://" /></td>\n			</tr>\n			<tr>\n				<td>Width:</td>\n				<td><input type="text" name="p_width" size="6" value="320" /></td>\n			</tr>\n			<tr>\n				<td>Height:</td>\n				<td><input type="text" name="p_height" size="6" value="200" /></td>\n			</tr>\n			<tr>\n				<td>Frame border:</td>\n				<td><select name="p_frameborder">\n					<option value="0" selected="selected">No</option>\n					<option value="1">Yes</option>\n				</select></td>\n			</tr>\n			<tr>\n				<td>Scrolling:</td>\n				<td><select name="p_scrolling">\n					<option value="0">No</option>\n					<option value="1" selected="selected">Yes</option>\n				</select></td>\n			</tr>\n			<tr>\n				<td>ID(optional):</td>\n				<td><input type="text" name="p_id" size="24" value="" /></td>\n			</tr>\n			<tr>\n				<td>Class(optional):</td>\n				<td><input type="text" name="p_class" size="24" value="" /></td>\n			</tr>\n		</table>\n		<p>\n			<input type="submit" value="Ok" />\n			<input type="button" class="cancel" value="Cancel" />\n		</p>\n	</form>';
if(!xq) xq = {};
if(!xq.ui_templates) xq.ui_templates = {};

xq.ui_templates.basicLinkDialog='<form action="#" class="xqFormDialog xqBasicLinkDialog">\n		<h3>Link</h3>\n		<div>\n			<input type="text" class="initialFocus" name="text" value="" />\n			<input type="text" name="url" value="http://" />\n			\n			<input type="submit" value="Ok" />\n			<input type="button" class="cancel" value="Cancel" />\n		</div>\n	</form>';
if(!xq) xq = {};
if(!xq.ui_templates) xq.ui_templates = {};

xq.ui_templates.basicMovieDialog='<form action="#" class="xqFormDialog xqBasicMovieDialog">\n		<table>\n			<tr>\n				<td>Movie OBJECT tag:</td>\n				<td><input type="text" class="initialFocus" name="html" size="36" value="" /></td>\n			</tr>\n		</table>\n		<p>\n			<input type="submit" value="Ok" />\n			<input type="button" class="cancel" value="Cancel" />\n		</p>\n	</form>';
if(!xq) xq = {};
if(!xq.ui_templates) xq.ui_templates = {};

xq.ui_templates.basicScriptDialog='<form action="#" class="xqFormDialog xqBasicScriptDialog">\n		<table>\n			<tr>\n				<td>Script URL:</td>\n				<td><input type="text" class="initialFocus" name="url" size="36" value="http://" /></td>\n			</tr>\n		</table>\n		<p>\n			<input type="submit" value="Ok" />\n			<input type="button" class="cancel" value="Cancel" />\n		</p>\n	</form>';
