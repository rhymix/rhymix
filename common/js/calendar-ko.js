// ** I18N

// DyCalendar KO language
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
("일요일",
 "월요일",
 "화요일",
 "수요일",
 "목요일",
 "금요일",
 "토요일",
 "일요일");

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
("일",
 "월",
 "화",
 "수",
 "목",
 "금",
 "토",
 "일");

// full month names
DyCalendar._MN = new Array
("1월",
 "2월",
 "3월",
 "4월",
 "5월",
 "6월",
 "7월",
 "8월",
 "9월",
 "10월",
 "11월",
 "12월");

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
DyCalendar._TT["INFO"] = "calendar 소개";

DyCalendar._TT["ABOUT"] =
"DHTML Date/Time Selector\n" +
"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + // don't translate this this ;-)
"\n"+
"최신 버전을 받으시려면 http://www.dynarch.com/projects/calendar/ 에 방문하세요\n" +
"\n"+
"GNU LGPL 라이센스로 배포됩니다. \n"+
"라이센스에 대한 자세한 내용은 http://gnu.org/licenses/lgpl.html 을 읽으세요." +
"\n\n" +
"날짜 선택:\n" +
"- 연도를 선택하려면 \xab, \xbb 버튼을 사용합니다\n" +
"- 달을 선택하려면 " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " 버튼을 누르세요\n" +
"- 계속 누르고 있으면 위 값들을 빠르게 선택하실 수 있습니다.";
DyCalendar._TT["ABOUT_TIME"] = "\n\n" +
"시간 선택:\n" +
"- 마우스로 누르면 시간이 증가합니다\n" +
"- Shift 키와 함께 누르면 감소합니다\n" +
"- 누른 상태에서 마우스를 움직이면 좀 더 빠르게 값이 변합니다.\n";

DyCalendar._TT["PREV_YEAR"] = "지난 해 (길게 누르면 목록)";
DyCalendar._TT["PREV_MONTH"] = "지난 달 (길게 누르면 목록)";
DyCalendar._TT["GO_TODAY"] = "오늘 날짜로";
DyCalendar._TT["NEXT_MONTH"] = "다음 달 (길게 누르면 목록)";
DyCalendar._TT["NEXT_YEAR"] = "다음 해 (길게 누르면 목록)";
DyCalendar._TT["SEL_DATE"] = "날짜를 선택하세요";
DyCalendar._TT["DRAG_TO_MOVE"] = "마우스 드래그로 이동 하세요";
DyCalendar._TT["PART_TODAY"] = " (오늘)";

DyCalendar._TT["DAY_FIRST"] = "%s 먼저 표시";

DyCalendar._TT["WEEKEND"] = "0,6";

DyCalendar._TT["CLOSE"] = "닫기";
DyCalendar._TT["TODAY"] = "오늘";
DyCalendar._TT["TIME_PART"] = "(Shift-)클릭 또는 드래그 하세요";

// date formats
DyCalendar._TT["DEF_DATE_FORMAT"] = "%Y-%m-%d";
DyCalendar._TT["TT_DATE_FORMAT"] = "%b/%e [%a]";

DyCalendar._TT["WK"] = "주";
DyCalendar._TT["TIME"] = "시:";
