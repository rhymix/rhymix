// ** I18N

// DyCalendar EN language
// Author: Mihai Bazon, <mihai_bazon@yahoo.com>
// Encoding: any
// Distributed under the same terms as the calendar itself.

// For translators: please use UTF-8 if possible.  We strongly believe that
// Unicode is the answer to a real internationalized world.  Also please
// include your contact information in the header, as can be seen above.

// full day names
DyCalendar._DN = new Array
("¬£¬à¬ã¬Ü¬â¬Ö¬ã¬Ö¬ß¬î¬Ö", 
 "¬±¬à¬ß¬Ö¬Õ¬Ö¬Ý¬î¬ß¬Ú¬Ü", 
 "¬£¬ä¬à¬â¬ß¬Ú¬Ü", 
 "¬³¬â¬Ö¬Õ¬Ñ", 
 "¬¹¬Ö¬ä¬Ó¬Ö¬â¬Ô", 
 "¬±¬ñ¬ä¬ß¬Ú¬è¬Ñ", 
 "¬³¬å¬Ò¬Ò¬à¬ä¬Ñ", 
 "¬£¬à¬ã¬Ü¬â¬Ö¬ã¬Ö¬ß¬î¬Ö");

// Please note that the following array of short day names (and the same goes
// for short month names, _SMN) isn't absolutely necessary.  We give it here
// for exemplification on how one can customize the short day names, but if
// they are simply the first N letters of the full name you can simply say:
//
//   DyCalendar._SDN_len = N; // short day name length
//   DyCalendar._SMN_len = N; // short month name length
//
// If N = 3 then this is not needed either since we assume a value of 3 if not
// present, to be compatible with translation files that were written before
// this feature.

// short day names
DyCalendar._SDN = new Array
("Sun", 
 "¬±¬¯", 
 "¬£¬´", 
 "Wed", 
 "Thu", 
 "¬±¬ñ¬ä", 
 "¬³¬¢", 
 "¬³¬à¬Ý¬ß¬è¬Ö");

// First day of the week. "0" means display Sunday first, "1" means display
// Monday first, etc.
DyCalendar._FD = 0;

// full month names
DyCalendar._MN = new Array
("¬Á¬ß¬Ó¬Ñ¬â¬î", 
 "¬¶¬Ö¬Ó¬â¬Ñ¬Ý¬î", 
 "¬®¬Ñ¬â¬ä¬Ñ", 
 "¬¡¬á¬â¬Ö¬Ý¬î", 
 "¬®¬Ñ¬ñ", 
 "¬ª¬ð¬ß¬î", 
 "¬ª¬ð¬Ý¬î", 
 "¬¡¬Ó¬Ô¬å¬ã¬ä", 
 "¬³¬Ö¬ß¬ä¬ñ¬Ò¬â¬î", 
 "¬°¬Ü¬ä¬ñ¬Ò¬â¬î", 
 "¬¯¬à¬ñ¬Ò¬â¬î", 
 "¬¥¬Ö¬Ü¬Ñ¬Ò¬â¬î");

// short month names
DyCalendar._SMN = new Array
("¬Á¬ß¬Ó¬Ñ¬â¬î", 
 "¬¶¬Ö¬Ó¬â¬Ñ¬Ý¬î", 
 "¬®¬Ñ¬â¬ä¬Ñ", 
 "¬¡¬á¬â¬Ö¬Ý¬î", 
 "¬®¬Ñ¬ñ", 
 "¬ª¬ð¬ß¬î", 
 "¬ª¬ð¬Ý¬î", 
 "¬¡¬Ó¬Ô¬å¬ã¬ä", 
 "¬³¬Ö¬ß¬ä¬ñ¬Ò¬â¬î", 
 "¬°¬Ü¬ä¬ñ¬Ò¬â¬î", 
 "¬¯¬à¬ñ¬Ò¬â¬î", 
 "¬¥¬Ö¬Ü¬Ñ¬Ò¬â¬î");

// tooltips
DyCalendar._TT = {};
DyCalendar._TT["INFO"] = "¬° ¬Ü¬Ñ¬Ý¬Ö¬ß¬Õ¬Ñ¬â¬Ö";

DyCalendar._TT["ABOUT"] =
"DHTML Date/Time Selector\n" +
"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + // don't translate this this ;-)
"For latest version visit: http://www.dynarch.com/projects/calendar/\n" +
"Distributed under GNU LGPL.  See http://gnu.org/licenses/lgpl.html for details." +
"\n\n" +
"Date selection:\n" +
"- Use the \xab, \xbb buttons to select year\n" +
"- Use the " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " buttons to select month\n" +
"- Hold mouse button on any of the above buttons for faster selection.";
DyCalendar._TT["ABOUT_TIME"] = "\n\n" +
"Time selection:\n" +
"- Click on any of the time parts to increase it\n" +
"- or Shift-click to decrease it\n" +
"- or click and drag for faster selection.";

DyCalendar._TT [ "PREV_YEAR"] = "¬±¬â¬Ö¬Õ. ¬¤¬à¬Õ (¬å¬Õ¬Ö¬â¬Ø¬Ú¬Ó¬Ñ¬ä¬î ¬Õ¬Ý¬ñ ¬Þ¬Ö¬ß¬ð)"; 
DyCalendar._TT [ "PREV_MONTH"] = "¬±¬â¬Ö¬Õ. ¬®¬Ö¬ã¬ñ¬è¬Ö (¬å¬Õ¬Ö¬â¬Ø¬Ú¬Ó¬Ñ¬ä¬î ¬Õ¬Ý¬ñ ¬Þ¬Ö¬ß¬ð)"; 
DyCalendar._TT [ "GO_TODAY"] = "¬±¬Ö¬â¬Ö¬Û¬ä¬Ú ¬³¬Ö¬Ô¬à¬Õ¬ß¬ñ"; 
DyCalendar._TT [ "NEXT_MONTH"] = "¬£ ¬ã¬Ý¬Ö¬Õ¬å¬ð¬ë¬Ö¬Þ ¬Þ¬Ö¬ã¬ñ¬è¬Ö (¬å¬Õ¬Ö¬â¬Ø¬Ú¬Ó¬Ñ¬ä¬î ¬Õ¬Ý¬ñ ¬Þ¬Ö¬ß¬ð)"; 
DyCalendar._TT [ "NEXT_YEAR"] = "¬£ ¬ã¬Ý¬Ö¬Õ¬å¬ð¬ë¬Ö¬Þ ¬Ô¬à¬Õ¬å (¬å¬Õ¬Ö¬â¬Ø¬Ú¬Ó¬Ñ¬ä¬î ¬Õ¬Ý¬ñ ¬Þ¬Ö¬ß¬ð)"; 
DyCalendar._TT [ "SEL_DATE"] = "¬£¬í¬Ò¬Ö¬â¬Ú¬ä¬Ö ¬Õ¬Ñ¬ä¬å"; 
DyCalendar._TT [ "DRAG_TO_MOVE"] = "¬±¬Ö¬â¬Ö¬ä¬Ñ¬ã¬Ü¬Ú¬Ó¬Ñ¬Û¬ä¬Ö ¬Õ¬Ó¬Ú¬Ô¬Ñ¬ä¬î¬ã¬ñ"; 
DyCalendar._TT [ "PART_TODAY"] = "(¬ã¬Ö¬Ô¬à¬Õ¬ß¬ñ)";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
DyCalendar._TT [ "DAY_FIRST"] = "display% ¬ã ¬á¬Ö¬â¬Ó¬à¬Û";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
DyCalendar._TT [ "WEEKEND"] = "0,6"; 

DyCalendar._TT [ "CLOSE"] = "¬©¬Ñ¬Ü¬â¬í¬ä¬î"; 
DyCalendar._TT [ "Today"] = "¬³¬Ö¬Ô¬à¬Õ¬ß¬ñ"; 
DyCalendar._TT [ "TIME_PART"] = "(Shift-) ¬Ü¬Ý¬Ú¬Ü ¬Ú¬Ý¬Ú ¬á¬Ö¬â¬Ö¬ä¬Ñ¬ë¬Ú¬ä¬Ö ¬Õ¬Ý¬ñ ¬Ú¬Ù¬Þ¬Ö¬ß¬Ö¬ß¬Ú¬ñ ¬ã¬ä¬à¬Ú¬Þ¬à¬ã¬ä¬Ú";

// date formats
DyCalendar._TT [ "DEF_DATE_FORMAT"] = "% Y-% ¬Þ-% ¬Ô"; 
DyCalendar._TT [ "TT_DATE_FORMAT"] = "%,%% ¬Ö ¬Ò"; 

DyCalendar._TT [ "WK"] = "¬¯¬Ö¬Õ"; 
DyCalendar._TT [ "TIME"] = "¬£¬â¬Ö¬Þ¬ñ:";
