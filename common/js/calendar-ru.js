// ** I18N

// DyCalendar RU language
// Author: Mihai Bazon, <mihai_bazon@yahoo.com>
// Translation: bradly1, <bradly1@paran.com>
// Encoding: UTF-8
// lang : RU
// Distributed under the same terms as the calendar itself.

// For translators: please use UTF-8 if possible.  We strongly believe that
// Unicode is the answer to a real internationalized world.  Also please
// include your contact information in the header, as can be seen above.

// full day names
DyCalendar._DN = new Array
("Воскресенье", 
 "Понедельник", 
 "Вторник", 
 "Среда", 
 "Четверг", 
 "Пятница", 
 "Суббота", 
 "Воскресенье");

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
 "ПН", 
 "ВТ", 
 "Wed", 
 "Thu", 
 "Пят", 
 "СБ", 
 "Солнце");

// First day of the week. "0" means display Sunday first, "1" means display
// Monday first, etc.
DyCalendar._FD = 0;

// full month names
DyCalendar._MN = new Array
("Январь", 
 "Февраль", 
 "Марта", 
 "Апрель", 
 "Мая", 
 "Июнь", 
 "Июль", 
 "Август", 
 "Сентябрь", 
 "Октябрь", 
 "Ноябрь", 
 "Декабрь");

// short month names
DyCalendar._SMN = new Array
("Январь", 
 "Февраль", 
 "Марта", 
 "Апрель", 
 "Мая", 
 "Июнь", 
 "Июль", 
 "Август", 
 "Сентябрь", 
 "Октябрь", 
 "Ноябрь", 
 "Декабрь");

// tooltips
DyCalendar._TT = {};
DyCalendar._TT["INFO"] = "О календаре";

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

DyCalendar._TT [ "PREV_YEAR"] = "Пред. Год (удерживать для меню)"; 
DyCalendar._TT [ "PREV_MONTH"] = "Пред. Месяце (удерживать для меню)"; 
DyCalendar._TT [ "GO_TODAY"] = "Перейти Сегодня"; 
DyCalendar._TT [ "NEXT_MONTH"] = "В следующем месяце (удерживать для меню)"; 
DyCalendar._TT [ "NEXT_YEAR"] = "В следующем году (удерживать для меню)"; 
DyCalendar._TT [ "SEL_DATE"] = "Выберите дату"; 
DyCalendar._TT [ "DRAG_TO_MOVE"] = "Перетаскивайте двигаться"; 
DyCalendar._TT [ "PART_TODAY"] = "(сегодня)";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
DyCalendar._TT [ "DAY_FIRST"] = "display% с первой";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
DyCalendar._TT [ "WEEKEND"] = "0,6"; 

DyCalendar._TT [ "CLOSE"] = "Закрыть"; 
DyCalendar._TT [ "Today"] = "Сегодня"; 
DyCalendar._TT [ "TIME_PART"] = "(Shift-) клик или перетащите для изменения стоимости";

// date formats
DyCalendar._TT [ "DEF_DATE_FORMAT"] = "% Y-% м-% г"; 
DyCalendar._TT [ "TT_DATE_FORMAT"] = "%,%% е б"; 

DyCalendar._TT [ "WK"] = "Нед"; 
DyCalendar._TT [ "TIME"] = "Время:";
