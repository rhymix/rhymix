// ** I18N

// DyCalendar JA language
// Author: Mihai Bazon, <mihai_bazon@yahoo.com>
// Translation: ミニミ <@> for ZeroboardXE
// Encoding: UTF-8
// lang : jp
// Distributed under the same terms as the calendar itself.

// For translators: please use UTF-8 if possible.  We strongly believe that
// Unicode is the answer to a real internationalized world.  Also please
// include your contact information in the header, as can be seen above.

// full day names

DyCalendar._DN = new Array
("日曜日",
 "月曜日",
 "火曜日",
 "水曜日",
 "木曜日",
 "金曜日",
 "土曜日",
 "日曜日");

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
 "月",
 "火",
 "水",
 "木",
 "金",
 "土",
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
DyCalendar._TT["INFO"] = "カレンダーの紹介";

DyCalendar._TT["ABOUT"] =
"DHTML Date/Time Selector\n" +
"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + // don't translate this this ;-)
"\n"+
"最新バージョンは http://www.dynarch.com/projects/calendar/ にご訪問して下さい。\n" +
"\n"+
"GNU LGPL ライセンスで配布されます。 \n"+
"より詳しいライセンスの内容は http://gnu.org/licenses/lgpl.html をお読みください。" +
"\n\n" +
"日付選択:\n" +
"- 年度の選択には \xab, \xbb ボタンを使います。\n" +
"- 月の選択には " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " ボタンを使って下さい。\n" +
"- ずっと押していると上の値をよら楽に選択できます。";
DyCalendar._TT["ABOUT_TIME"] = "\n\n" +
"時間の選択:\n" +
"- マウスをクリックすると時間が増加します。\n" +
"- Shiftキーと同時にクリックすると減ります。\n" +
"- 押している状態で、マウスを動かすとより早く値が変化します。\n";

DyCalendar._TT["PREV_YEAR"] = "前年 (長く押すとリスト)";
DyCalendar._TT["PREV_MONTH"] = "前月 (長く押すとリスト)";
DyCalendar._TT["GO_TODAY"] = "今日";
DyCalendar._TT["NEXT_MONTH"] = "翌月 (長く押すとリスト)";
DyCalendar._TT["NEXT_YEAR"] = "翌年 (長く押すとリスト)";
DyCalendar._TT["SEL_DATE"] = "日付選択";
DyCalendar._TT["DRAG_TO_MOVE"] = "ウィンドウの移動";
DyCalendar._TT["PART_TODAY"] = " (今日)";

DyCalendar._TT["DAY_FIRST"] = "%s を先頭に";

DyCalendar._TT["WEEKEND"] = "0,6";

DyCalendar._TT["CLOSE"] = "閉じる";
DyCalendar._TT["TODAY"] = "今日";
DyCalendar._TT["TIME_PART"] = "(Shift-)クリック、もしくはドラッグして下さい。";

// date formats
DyCalendar._TT["DEF_DATE_FORMAT"] = "%Y-%m-%d";
DyCalendar._TT["TT_DATE_FORMAT"] = "%b/%e [%a]";

DyCalendar._TT["WK"] = "週";
DyCalendar._TT["TIME"] = "時:";
