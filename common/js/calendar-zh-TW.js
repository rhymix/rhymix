// ** I18N

// DyCalendar zh-TW language
// Author: Mihai Bazon, <mihai_bazon@yahoo.com>
// Encoding: UTF-8
// Distributed under the same terms as the calendar itself.

// For translators: please use UTF-8 if possible.  We strongly believe that
// Unicode is the answer to a real internationalized world.  Also please
// include your contact information in the header, as can be seen above.

// full day names

DyCalendar._DN = new Array
("星期日",
 "星期一",
 "星期二",
 "星期三",
 "星期四",
 "星期五",
 "星期六",
 "星期日");

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
("日",
 "一",
 "二",
 "三",
 "四",
 "五",
 "六",
 "日");

// full month names
DyCalendar._MN = new Array
("1月",
 "2月",
 "3月",
 "4月",
 "5月",
 "6月",
 "7月",
 "8月",
 "9月",
 "10月",
 "11月",
 "12月");

// short month names
DyCalendar._SMN = new Array
("1",
 "2",
 "3",
 "4",
 "5",
 "6",
 "7",
 "8",
 "9",
 "10",
 "11",
 "12");

// tooltips
DyCalendar._TT = {};
DyCalendar._TT["INFO"] = "日曆簡介";

DyCalendar._TT["ABOUT"] =
"DHTML Date/Time Selector\n" +
"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + // don't translate this this ;-)
"\n"+
"請到 http://www.dynarch.com/projects/calendar/ 下載最新版本\n" +
"\n"+
"遵照 GNU LGPL版權。 \n"+
"實際的版權内容請參考考 http://gnu.org/licenses/lgpl.html " +
"\n\n" +
"選擇日期:\n" +
"- 選擇年份請使用 \xab, \xbb 按鈕\n" +
"- 選擇月份請使用 " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " 按鈕\n" +
"- 按住按鈕可快速的選擇所要的年份或月份";
DyCalendar._TT["ABOUT_TIME"] = "\n\n" +
"選擇時間:\n" +
"- 以滑鼠點擊可增加時間\n" +
"- 配合Shift按鍵，可減少時間 \n" +
"- 在按住滑鼠的狀態下，往右是增加、往左是減少，可快速調整時間。\n";

DyCalendar._TT["PREV_YEAR"] = "去年 (按住顯示目錄)";
DyCalendar._TT["PREV_MONTH"] = "上個月 (按住顯示目錄)";
DyCalendar._TT["GO_TODAY"] = "設為今天";
DyCalendar._TT["NEXT_MONTH"] = "下個月 (按住顯示目錄)";
DyCalendar._TT["NEXT_YEAR"] = "明年 (按住顯示目錄)";
DyCalendar._TT["SEL_DATE"] = "請選擇日期";
DyCalendar._TT["DRAG_TO_MOVE"] = "可用滑鼠拖曳";
DyCalendar._TT["PART_TODAY"] = " (今日)";

DyCalendar._TT["DAY_FIRST"] = "%s排到第一列";

DyCalendar._TT["WEEKEND"] = "0,6";

DyCalendar._TT["CLOSE"] = "關閉";
DyCalendar._TT["TODAY"] = "今日";
DyCalendar._TT["TIME_PART"] = "(Shift-)點擊或拖曳";

// date formats
DyCalendar._TT["DEF_DATE_FORMAT"] = "%Y-%m-%d";
DyCalendar._TT["TT_DATE_FORMAT"] = "%b/%e [%a]";

DyCalendar._TT["WK"] = "周";
DyCalendar._TT["TIME"] = "時:";
