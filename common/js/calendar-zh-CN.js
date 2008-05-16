// ** I18N

// DyCalendar zh-CN language
// Author: Mihai Bazon, <mihai_bazon@yahoo.com>
// Translation: Yourim Yi <yyi@yourim.net>
// Encoding: UTF-8
// lang : ko
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
DyCalendar._TT["INFO"] = "calendar 简介";

DyCalendar._TT["ABOUT"] =
"DHTML Date/Time Selector\n" +
"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + // don't translate this this ;-)
"\n"+
"下载最新版本请访问 http://www.dynarch.com/projects/calendar/ \n" +
"\n"+
"遵循GNU LGPL许可协议。 \n"+
"具体许可内容请参考 http://gnu.org/licenses/lgpl.html " +
"\n\n" +
"日期选择:\n" +
"- 选择年份请使用 \xab, \xbb 按钮\n" +
"- 选择月份请使用 " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " 按钮\n" +
"- 按住按钮可快速的选择所需的年份或月份";
DyCalendar._TT["ABOUT_TIME"] = "\n\n" +
"时间选择:\n" +
"- 鼠标点击可增加时间\n" +
"- 配合Shift键，时间减少 \n" +
"- 鼠标按住状态下向右为增加向左为减少，这样时间调整会快一些。\n";

DyCalendar._TT["PREV_YEAR"] = "去年 (按住显示目录)";
DyCalendar._TT["PREV_MONTH"] = "上月 (按住显示目录)";
DyCalendar._TT["GO_TODAY"] = "设为今日";
DyCalendar._TT["NEXT_MONTH"] = "下月 (按住显示目录)";
DyCalendar._TT["NEXT_YEAR"] = "来年 (按住显示目录)";
DyCalendar._TT["SEL_DATE"] = "请选择日期";
DyCalendar._TT["DRAG_TO_MOVE"] = "可以用鼠标拖动";
DyCalendar._TT["PART_TODAY"] = " (今日)";

DyCalendar._TT["DAY_FIRST"] = "%s排到第一列";

DyCalendar._TT["WEEKEND"] = "0,6";

DyCalendar._TT["CLOSE"] = "关闭";
DyCalendar._TT["TODAY"] = "今日";
DyCalendar._TT["TIME_PART"] = "(Shift-)点击或拖动";

// date formats
DyCalendar._TT["DEF_DATE_FORMAT"] = "%Y-%m-%d";
DyCalendar._TT["TT_DATE_FORMAT"] = "%b/%e [%a]";

DyCalendar._TT["WK"] = "周";
DyCalendar._TT["TIME"] = "时:";
