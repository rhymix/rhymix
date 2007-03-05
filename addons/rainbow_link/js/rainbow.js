/************************************************************************/
/* Rainbow Links Version 1.03 (2003.9.20)                               */
/* Script updated by Dynamicdrive.com for IE6                           */
/* Copyright (C) 1999-2001 TAKANASHI Mizuki                             */
/* takanasi@hamal.freemail.ne.jp                                        */
/*----------------------------------------------------------------------*/
/* Read it somehow even if my English text is a little wrong! ;-)       */
/*                                                                      */
/* Usage:                                                               */
/*  Insert '<script src="rainbow.js"></script>' into the BODY section,  */
/*  right after the BODY tag itself, before anything else.              */
/*  You don't need to add "onMouseover" and "onMouseout" attributes!!   */
/*                                                                      */
/*  If you'd like to add effect to other texts(not link texts), then    */
/*  add 'onmouseover="doRainbow(this);"' and                            */
/*  'onmouseout="stopRainbow();"' to the target tags.                   */
/*                                                                      */
/* This Script works with IE4,Netscape6,Mozilla browser and above only, */
/* but no error occurs on other browsers.                               */
/************************************************************************/


////////////////////////////////////////////////////////////////////
// Setting

var rate = 20;  // Increase amount(The degree of the transmutation)


////////////////////////////////////////////////////////////////////
// Main routine

if (document.getElementById)
window.onerror=new Function("return true")

var objActive;  // The object which event occured in
var act = 0;    // Flag during the action
var elmH = 0;   // Hue
var elmS = 128; // Saturation
var elmV = 255; // Value
var clrOrg;     // A color before the change
var TimerID;    // Timer ID


if(xIE4Up) {
    xAddEventListener(document, 'mouseover', doRainbowAnchor);
    xAddEventListener(document, 'mouseout', stopRainbowAnchor);
} else {
    xAddEventListener(document, 'mouseover', Mozilla_doRainbowAnchor);
    xAddEventListener(document, 'mouseout', Mozilla_stopRainbowAnchor);
}
/*
if (document.all) {
    document.onmouseover = doRainbowAnchor;
    document.onmouseout = stopRainbowAnchor;
}
else if (document.getElementById) {
    document.captureEvents(Event.MOUSEOVER | Event.MOUSEOUT);
    document.onmouseover = Mozilla_doRainbowAnchor;
    document.onmouseout = Mozilla_stopRainbowAnchor;
}
*/


//=============================================================================
// doRainbow
//  This function begins to change a color.
//=============================================================================
function doRainbow(obj)
{
    if (act == 0) {
        act = 1;
        if (obj)
            objActive = obj;
        else
            objActive = event.srcElement;
        clrOrg = objActive.style.color;
        TimerID = setInterval("ChangeColor()",100);
    }
}


//=============================================================================
// stopRainbow
//  This function stops to change a color.
//=============================================================================
function stopRainbow()
{
    if (act) {
        objActive.style.color = clrOrg;
        clearInterval(TimerID);
        act = 0;
    }
}


//=============================================================================
// doRainbowAnchor
//  This function begins to change a color. (of a anchor, automatically)
//=============================================================================
function doRainbowAnchor()
{
    if (act == 0) {
        var obj = event.srcElement;
        while (obj.tagName != 'A' && obj.tagName != 'BODY') {
            obj = obj.parentElement;
            if (obj.tagName == 'A' || obj.tagName == 'BODY')
                break;
        }

        if (obj.tagName == 'A' && obj.href != '') {
            objActive = obj;
            act = 1;
            clrOrg = objActive.style.color;
            TimerID = setInterval("ChangeColor()",100);
        }
    }
}


//=============================================================================
// stopRainbowAnchor
//  This function stops to change a color. (of a anchor, automatically)
//=============================================================================
function stopRainbowAnchor()
{
    if (act) {
        if (objActive.tagName == 'A') {
            objActive.style.color = clrOrg;
            clearInterval(TimerID);
            act = 0;
        }
    }
}


//=============================================================================
// Mozilla_doRainbowAnchor(for Netscape6 and Mozilla browser)
//  This function begins to change a color. (of a anchor, automatically)
//=============================================================================
function Mozilla_doRainbowAnchor(evt)
{
    var e = new xEvent(evt);
    if (act == 0) {
        obj = e.target;
        while (obj.nodeName != 'A' && obj.nodeName != 'BODY') {
            obj = obj.parentNode;
            if(typeof(obj)=='undefined'||!obj) return;
            if (obj.nodeName == 'A' || obj.nodeName == 'BODY') break;
        }

        if (obj.nodeName == 'A' && obj.href != '') {
            objActive = obj;
            act = 1;
            clrOrg = obj.style.color;
            TimerID = setInterval("ChangeColor()",100);
        }
    }
}


//=============================================================================
// Mozilla_stopRainbowAnchor(for Netscape6 and Mozilla browser)
//  This function stops to change a color. (of a anchor, automatically)
//=============================================================================
function Mozilla_stopRainbowAnchor(e)
{
    if (act) {
        if (objActive.nodeName == 'A') {
            objActive.style.color = clrOrg;
            clearInterval(TimerID);
            act = 0;
        }
    }
}


//=============================================================================
// Change Color
//  This function changes a color actually.
//=============================================================================
function ChangeColor()
{
    objActive.style.color = makeColor();
}


//=============================================================================
// makeColor
//  This function makes rainbow colors.
//=============================================================================
function makeColor()
{
    // Don't you think Color Gamut to look like Rainbow?

    // HSVtoRGB
    if (elmS == 0) {
        elmR = elmV;    elmG = elmV;    elmB = elmV;
    }
    else {
        t1 = elmV;
        t2 = (255 - elmS) * elmV / 255;
        t3 = elmH % 60;
        t3 = (t1 - t2) * t3 / 60;

        if (elmH < 60) {
            elmR = t1;  elmB = t2;  elmG = t2 + t3;
        }
        else if (elmH < 120) {
            elmG = t1;  elmB = t2;  elmR = t1 - t3;
        }
        else if (elmH < 180) {
            elmG = t1;  elmR = t2;  elmB = t2 + t3;
        }
        else if (elmH < 240) {
            elmB = t1;  elmR = t2;  elmG = t1 - t3;
        }
        else if (elmH < 300) {
            elmB = t1;  elmG = t2;  elmR = t2 + t3;
        }
        else if (elmH < 360) {
            elmR = t1;  elmG = t2;  elmB = t1 - t3;
        }
        else {
            elmR = 0;   elmG = 0;   elmB = 0;
        }
    }

    elmR = Math.floor(elmR).toString(16);
    elmG = Math.floor(elmG).toString(16);
    elmB = Math.floor(elmB).toString(16);
    if (elmR.length == 1)    elmR = "0" + elmR;
    if (elmG.length == 1)    elmG = "0" + elmG;
    if (elmB.length == 1)    elmB = "0" + elmB;

    elmH = elmH + rate;
    if (elmH >= 360)
        elmH = 0;

    return '#' + elmR + elmG + elmB;
}
