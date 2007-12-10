Calendar.ControlAction=function(C){
var curYear = _CalendarInstances[arguments[1].parentNode.objID].date.getYear();
{switch(C)
{case"view":_CalendarInstances[arguments[1].parentNode.objID].changeView(arguments[2]);
break;
case"move":switch(arguments[2]){case"prev":_CalendarInstances[arguments[1].parentNode.objID].movePrev();
break;
case"next":_CalendarInstances[arguments[1].parentNode.objID].moveNext();
break;
case"today":_CalendarInstances[arguments[1].parentNode.objID].moveToday();
break}break;
case"plugin":
    if(_CalendarInstances[arguments[1].parentNode.objID].plugin[arguments[2]]&&_CalendarInstances[arguments[1].parentNode.objID].plugin[arguments[2]].controlAction)
    {
	var A=[];
	for(var B=3; B<arguments.length; B++)
	    {A.push(arguments[B])}_CalendarInstances[arguments[1].parentNode.objID].plugin[arguments[2]].controlAction(A);}break};
var newYear = _CalendarInstances[arguments[1].parentNode.objID].date.getYear();
if(curYear != newYear) { 
    var url = request_uri.setQuery('mid', current_mid).setQuery('year',_CalendarInstances[arguments[1].parentNode.objID].date.getFullYear()).setQuery('month',_CalendarInstances[arguments[1].parentNode.objID].date.getMonth()+1).setQuery('day',_CalendarInstances[arguments[1].parentNode.objID].date.getDate()); 
    location.href = url; 
    }
}};
