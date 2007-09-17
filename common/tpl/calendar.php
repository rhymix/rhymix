<?php
    ####################
    ## 연도 범위 설정 ##
    // 최대
    $max_year = 2900;//년 (우주가 사라지는 날 까지)
    // 최소
    $min_year = 1900;//년 (0년 부터)
    ####################

    $year = $_REQUEST['year'];
    $month = $_REQUEST['month'];

    $method = $_REQUEST['method'];
    $fo_id = $_REQUEST['fo_id'];
    $callback_func = $_REQUEST['callback_func'];

    $day_str = $_REQUEST['day_str']; // 날짜 (ex. 20080101)
    if($day_str && strlen($day_str)) {
        $year = substr($day_str, 0, 4);
        $month = substr($day_str, 4, 2);
    }

    if($year < $min_year || $year > $max_year) $year = date("Y");
    if(!(int)$month || $month < 1 || $month > 12) $month = date("n");

    switch($method) {
        case 'prev_year' :
                $year --;
            break;
        case 'prev_month' :
                $month --;
                if($month < 1) {
                    $month = 12;
                    $year --;
                }
            break;
        case 'next_month' :
                $month ++;
                if($month > 12) {
                    $month = 1;
                    $year ++;
                }
            break;
        case 'next_year' :
                $year ++;
            break;
    }

// 긴 이름
$monthLongName = array(1 => "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

// 짧은 이름
$monthShortName = array(1 => "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");

// 요일 이름
$dayName = array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");

/*
 * @brief 윤년 검사
 */
function isLeapYear($year) { 
    if ($year % 4 == 0 && $year % 100 != 0 || $year % 400 == 0) return true; 
    else return false;
} 

/*
 * @brief 날짜 수 계산
 */
function convertDatetoDay($year, $month, $day = 0) { 
    $numOfLeapYear = 0; // 윤년의 수 

    // 전년도까지의 윤년의 수를 구한다. 
    for($i = 0; $i < $year; $i++) { 
        if(isLeapYear($i)) $numOfLeapYear++; 
    } 

    // 전년도까지의 일 수를 구한다. 
    $toLastYearDaySum = ($year-1) * 365 + $numOfLeapYear; 

    // 올해의 현재 월까지의 일수 계산 
    $thisYearDaySum = 0; 
    //                        1,  2,  3,  4,  5,  6,  7,  8,  9, 10, 11, 12 
    $endOfMonth = array(1 => 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31); 

    for($i = 1; $i < $month; $i++) { 
        $thisYearDaySum += $endOfMonth[$i]; 
    } 

    // 윤년이고, 2월이 포함되어 있으면 1일을 증가시킨다. 
    if ($month > 2 && isLeapYear($year)) $thisYearDaySum++; 

    if(isLeapYear($year)) $endOfMonth[2] = 29;

    if($day) {
        $thisYearDaySum += $day; 
        return $toLastYearDaySum + $thisYearDaySum - 1; 
    } else {
        return $endOfMonth[$month];
    }
} 

/*
 * @brief 요일 구하기
 */
function getDayOfWeek($year, $month, $day) { 
    // 0~6의 값을 반환한다. 결과가 0이면 일요일이다. 
    return convertDatetoDay($year, $month, $day) % 7; 
} 

$start_week = getDayOfWeek($year, $month, 1);
$month_day = convertDatetoDay($year, $month);
$before_month_month_day = convertDatetoDay( $month == 1 ? $year - 1 : $year, $month == 1 ? 12 : $month - 1);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="ko" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="imagetoolbar" content="no" />
    <title>Calendar</title>
    <script type="text/javascript" src="../js/x.js"></script>
    <script type="text/javascript" src="../js/common.js"></script>
    <script type="text/javascript" src="../js/xml_handler.js"></script>

    <link rel="stylesheet" href="../css/default.css" type="text/css" />
    <link rel="stylesheet" href="../css/button.css" type="text/css" />
    <link rel="stylesheet" href="../../modules/admin/tpl/css/admin.css" type="text/css" />
    <link rel="stylesheet" href="./css/calendar.css" type="text/css" />

    <script type="text/javascript">
        function selectDate(date_str, date_val, callback_func) {
            if(!opener) {
                window.close();
                return;
            }

            var date_obj = opener.xGetElementById("date_<?php echo $fo_id?>");
            var str_obj = opener.xGetElementById("str_<?php echo $fo_id?>");

            if(date_obj) date_obj.value = date_val;

            if(str_obj) xInnerHtml(str_obj, date_str);

            if(callback_func) eval('opener.'+callback_func+'('+date_val+')');

            window.close();

        }
    </script>
</head>
<body>
<div id="popup_content" >
    <div id="popHeadder">
        <h1>Calendar</h1>
    </div>

    <form action="./calendar.php" method="get">
    <input type="hidden" name="fo_id" value="<?php echo $fo_id?>"/>
    <input type="hidden" name="callback_func" value="<?php echo $callback_func?>"/>

        <div id="popBody">

            <div class="calendar">
                <div class="yymm">
                    <div class="yy">
                        <a href="./calendar.php?fo_id=<?php echo $fo_id?>&amp;year=<?php echo $year?>&amp;month=<?php echo $month?>&amp;method=prev_year&amp;callback_func=<?php echo $callback_func?>" class="left"><img src="./images/buttonLeft2.gif" alt="<?php echo $year - 1?>" width="11" height="11" /></a><?php echo $year?><a href="./calendar.php?fo_id=<?php echo $fo_id?>&amp;year=<?php echo $year?>&amp;month=<?php echo $month?>&amp;method=next_year&amp;callback_func=<?php echo $callback_func?>" class="right"><img src="./images/buttonRight2.gif" alt="<?php echo $year + 1?>" width="11" height="11" /></a>
                    </div>
                    <div class="mm">
                        <p><?php echo $monthLongName[$month]?></p>
                            <a href="./calendar.php?fo_id=<?php echo $fo_id?>&amp;year=<?php echo $year?>&amp;month=<?php echo $month?>&amp;method=prev_month&amp;callback_func=<?php echo $callback_func?>" class="left"><img src="./images/buttonLeft2.gif" alt="prev" width="11" height="11" /></a><span><?php echo $month?></span><a href="./calendar.php?fo_id=<?php echo $fo_id?>&amp;year=<?php echo $year?>&amp;month=<?php echo $month?>&amp;method=next_month&amp;callback_func=<?php echo $callback_func?>" class="right"><img src="./images/buttonRight2.gif" alt="next" width="11" height="11" /></a>

                    </div>

                    <div class="go">
                        <select name="year" class="selectTypeY" onchange="submit()">
                            <? for($i = $max_year; $i >= $min_year; $i--):?>
                            <option value="<?php echo $i?>" <?php echo $year == $i? "selected":""?> class="<?php echo $i%10?($i%2?"select_color1":"select_color2"):"select_color10"?>"><?php echo $i?></option>
                            <?endfor?>
                        </select>
                        <select name="month" class="selectTypeM" onchange="submit()">
                            <? for($i = 1; $i <= 12; $i++):?>
                            <option value="<?php echo $i?>" <?php echo $month == $i? "selected":""?> class="<?php echo $i%2?"select_color1":"select_color2"?>"><?php echo sprintf("%02d",$i)?></option>
                            <?endfor?>
                        </select>
                    </div>
                    <br /><br />
                    <center><a href="./calendar.php" class="button"><span>Go Today</span></a></center>
                </div>

                <table cellspacing="0" class="dd">
                <tr>
                    <?for($y = 0; $y < 7; $y++) {?>
                    <td class="<?php echo $y==0?"sun":($y==6?"sat":"")?>"><?php echo $dayName[$y]?></td>
                    <?}?>
                </tr>
                <?php
                    //1주~6주
                    for($i = 0; $i < 6; $i++) {
                ?>
                <tr class="<?if($i == 0){?>first<?}elseif($i == 5){?>last<?}?>">
                <?php
                        //요일
                        for($j = 0; $j < 7; $j++) {
                            $m = $month;
                            $y = $year;

                            $cell_no = $i * 7 + $j;

                            if($cell_no < $start_week) {
                                $day = $before_month_month_day + $cell_no - $start_week + 1;
                                $m = $month - 1;
                                if($m < 1) {
                                    $m = 12;
                                    $y = $year - 1;
                                }
                            } else {

                                $day = $cell_no - $start_week + 1;
                                $m = $month;

                                if($day > $month_day) {
                                    $day = $day - $month_day;
                                    $m = $month + 1;
                                    if($m >12 ) {
                                        $m = 1;
                                        $y = $year - 1;
                                    }
                                }
                            }

                            if($j == 0) $class_name = "sun";
                            elseif($j == 6) $class_name = "sat";
                            else $class_name= "";

                            $date = $y.". ".sprintf("%02d", $m).". ".sprintf("%02d", $day);
                            $date_str = $y.sprintf("%02d", $m).sprintf("%02d", $day);

                ?>
                    <td class="<?php echo $class_name?>" <?if($day){?> onclick="selectDate('<?php echo $date?>','<?php echo $date_str?>','<?php echo $callback_func?>')"<?}?>>
                        <?if($m == $month){?><?if(date("Ymd")==$date_str){?><strong><?}?>
                            <?if($day){?><?php echo $day?><?}else{?>&nbsp;<?}?>
                            <?if(date("Ymd")==$date_str){?></strong><?}?>
                        <?}else{?>
                            <span class="disable"><?if($day){?><?php echo $day?><?}else{?>&nbsp;<?}?></span>
                        <?}?>
                    </td>
                <?php
                        }
                ?>
                </tr>
                <?php
                    }
                ?>
                </table>

            </div>
        </div>
    </form>
    <div id="popFooter" class="tCenter">
        <a href="#" onclick="window.close();" class="button"><span>close</span></a>
    </div>
</div>

<script type="text/javascript">
    xAddEventListener(window,'load', setFixedPopupSize);
    var _isPoped = true;
</script>
</body>
</html>
