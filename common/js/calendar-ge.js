// ** I18N

// DyCalendar KO language
// Author: Mihai Bazon, <mihai_bazon@yahoo.com>
// Translation: bradly1, <bradly1@paran.com>
// Encoding: UTF-8
// lang : ge
// Distributed under the same terms as the calendar itself.

// For translators: please use UTF-8 if possible.  We strongly believe that
// Unicode is the answer to a real internationalized world.  Also please
// include your contact information in the header, as can be seen above.

// full day names
DyCalendar._DN = new Array
("Sonntag",
  "Montag",
  "Dienstag",
  "Mittwoch",
  "Donnerstag",
  "Freitag",
  "Samstag",
  "Sonntag");

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
("Sonntag",
  "Montag",
  "Dienstag",
  "Mittwoch",
  "Donnerstag",
  "Freitag",
  "Samstag",
  "Sonntag");

// First day of the week. "0" means display Sunday first, "1" means display
// Monday first, etc.
DyCalendar._FD = 0;

// full month names
DyCalendar._MN = new Array
("Januar",
  "Februar",
  "März",
  "April",
  "Mai",
  "Juni",
  "Juli",
  "August",
  "September",
  "Oktober",
  "November",
  "Dezember");

// short month names
DyCalendar._SMN = new Array
("Januar",
  "Februar",
  "März",
  "April",
  "Mai",
  "Juni",
  "Juli",
  "August",
  "September",
  "Oktober",
  "November",
  "Dezember");

// tooltips
DyCalendar._TT = {};
DyCalendar._TT["INFO"] = "Über den Kalender";

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

DyCalendar._TT["PREV_YEAR"] = "Prev. Jahr (halten für Menü)";
DyCalendar._TT["PREV_MONTH"] = "Prev. Monat (halten für Menü)";
DyCalendar._TT["GO_TODAY"] = "Gehe Heute";
DyCalendar._TT["NEXT_MONTH"] = "Nächsten Monat (halten für Menü)";
DyCalendar._TT["NEXT_YEAR"] = "Nächstes Jahr (halten für Menü)";
DyCalendar._TT["SEL_DATE"] = "Wählen Sie das Datum";
DyCalendar._TT["DRAG_TO_MOVE"] = "Per Drag & Drop verschieben";
DyCalendar._TT["PART_TODAY"] = " (heute)";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
DyCalendar._TT["DAY_FIRST"] = "Anzeige% s erste";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
DyCalendar._TT["WEEKEND"] = "0,6";

DyCalendar._TT["CLOSE"] = "Schließen";
DyCalendar._TT["TODAY"] = "Heute";
DyCalendar._TT["TIME_PART"] = "(Shift-)Klicken Sie oder ziehen Sie ändern Wert";

// date formats
DyCalendar._TT["DEF_DATE_FORMAT"] = "%Y-%m-%d";
DyCalendar._TT["TT_DATE_FORMAT"] = "%a, %b %e";

DyCalendar._TT["WK"] = "wk";
DyCalendar._TT["TIME"] = "Zeit:";
