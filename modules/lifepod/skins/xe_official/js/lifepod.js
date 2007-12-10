var _calendar;
var entries = new Array();
var _year;
var _month;
var _day;

function startLifepod()
{
    //초기화
    //Calendar.Language = 'en'; //영어버전
    //객체를 생성, 인자는 캘린더로 변할 DIV의 ID
    _calendar=new Calendar('div_calendar');
    //콘트롤 추가(보기 방식, 기간 이동)
    _calendar.addControl(new Calendar.Control.View);
    _calendar.addControl(new Calendar.Control.Move);
    //이벤트 추가
    _calendar.attachEvent('drawFinish', entryDraw); //캘린더가 그려지면 호출할 함수, 주로 엔트리 추가시 사용된다
    _calendar.attachEvent('entryMove', entryMove); // 엔트리를 이동할 때 호출할 함수
    //그리기
    _calendar.draw();
}

function calAdd(id, start, end, title, description, type, category, color)
{
    entries.push(new Calendar.Entry(id, category, Calendar.str2date(start), Calendar.str2date(end), type, title, description, color)); 
}

function setDate(year, month, day)
{
    _year = year;
    _month = month;
    _day = day;
}

function entryDraw() {
 //생성한 엔트리를 추가
 for(entry in entries)
 {
    _calendar.addEntry(entries[entry]);
 }

 if(_year != undefined)
 {
    _calendar.date.setFullYear(_year);
    _calendar.date.setMonth(_month-1);
    _calendar.date.setDate(_day);
    _year = undefined;
    _calendar.draw();
 }
}

function entryMove() {
 //true를 리턴하면 엔트리가 이동이 됨, false를 리턴하면 엔트리 이동이 취소됨
 return true;
}
