<?php
    $year = sprintf("%04d",$_REQUEST['year']);
    $month = sprintf("%02d",$_REQUEST['month']);

    $method = $_REQUEST['method'];
    $fo_id = $_REQUEST['fo_id'];
    $callback_func = $_REQUEST['callback_func'];

    $day_str = $_REQUEST['day_str'];
    if($day_str && strlen($day_str)) {
        $year = substr($day_str,0,4);
        $month = substr($day_str,4,2);
    }

    if(!(int)$year) $year = date("Y");
    if(!(int)$month) $month = date("m");

    switch($method) {
        case 'prev_year' :
                $year = date("Y", mktime(0,0,0,1,1,$year)-60*60*24);
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
                $year = date("Y", mktime(0,0,0,12,31,$year)+60*60*24);
            break;
    }

    $start_week = date("w", mktime(0,0,0,$month,1,$year));
    $month_day = date("t", mktime(0,0,0,$month,1,$year));
    $before_month_month_day = date("t", mktime(0,0,0,$month,1,$year)-60*60*24);

    $next_year = date("m", mktime(0,0,0,12,31, $year)+60*60*24);
    $next_month = date("m", mktime(0,0,0,$month,$month_day, $year)+60*60*24);
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
    <link rel="stylesheet" href="./css/popup.css" type="text/css" />
    <link rel="stylesheet" href="./css/calendar.css" type="text/css" />

    <script type="text/javascript">
        function selectDate(date_str, date_val, callback_func) {
            if(!opener) {
                window.close();
                return;
            }

            var date_obj = opener.xGetElementById("date_<?=$fo_id?>");
            var str_obj = opener.xGetElementById("str_<?=$fo_id?>");

            if(date_obj) date_obj.value = date_val;

            if(str_obj) xInnerHtml(str_obj, date_str);

            if(callback_func) eval('opener.'+callback_func+'('+date_val+')');

            window.close();

        }
    </script>
</head>
<body>

    <div id="popup_content" class="calendar_box">
        <div class="calendar_title">
            <form action="./calendar.php" method="get">
                <input type="hidden" name="fo_id" value="<?=$fo_id?>"/>
                <input type="hidden" name="callback_func" value="<?=$callback_func?>"/>

                <table width="100%" border="0" cellspacing="0" cellpadding="2" align="center">
                <tr>
                    <td width="40">
                        <a href="#" onclick="location.href='./calendar.php?fo_id=<?=$fo_id?>&amp;year=<?=$year?>&amp;month=<?=$month?>&amp;method=prev_year&amp;callback_func=<?=$callback_func?>';return false;"><img src="./images/icon_pprev.gif" border="0" alt="prev year" /></a>
                        <a href="#" onclick="location.href='./calendar.php?fo_id=<?=$fo_id?>&amp;year=<?=$year?>&amp;month=<?=$month?>&amp;method=prev_month&amp;callback_func=<?=$callback_func?>';return false;"><img src="./images/icon_prev.gif" border="0" alt="prev month" /></a>
                    </td>
                    <td align="center">
                        <table border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td><input type="text" name="year" value="<?=$year?>" class="calendar_input_year" maxlength="4" />-<input type="text" name="month" value="<?=$month?>" maxlength="2" class="calendar_input_month" /></td>
                            <td><input type="image" src="./images/calendar.gif" class="calendar_btn" /></td>
                        </tr>
                        </table>
                    </td>
                    <td width="40">
                        <a href="#" onclick="location.href='./calendar.php?fo_id=<?=$fo_id?>&amp;year=<?=$year?>&amp;month=<?=$month?>&amp;method=next_month&amp;callback_func=<?=$callback_func?>';return false;"><img src="./images/icon_nnext.gif" border="0" alt="next month" /></a>
                        <a href="#" onclick="location.href='./calendar.php?fo_id=<?=$fo_id?>&amp;year=<?=$year?>&amp;month=<?=$month?>&amp;method=next_year&amp;callback_func=<?=$callback_func?>';return false;"><img src="./images/icon_next.gif" border="0" alt="next year" /></a>
                    </td>
                </tr>
                </table>

            </form>
        </div>

        <div class="calendar_list">
            <?php
                for($i=0;$i<5;$i++) {
                    for($j=0;$j<7;$j++) {
                        $m = $month;
                        $y = $year;

                        $cell_no = $i*7 + $j;

                        if($cell_no < $start_week) {
                            $day = $before_month_month_day + $cell_no - $start_week + 1;
                            $m = $month - 1;
                            if($m<1) {
                                $m = 12;
                                $y = $year - 1;
                            }
                        } else {

                            $day = $cell_no - $start_week +1;
                            $m = $month;

                            if($day > $month_day) {
                                $day = $day - $month_day;
                                $m = $month + 1;
                                if($m>12) {
                                    $m = 1;
                                    $y = $year-1;
                                }
                            }
                        }

                        if($j==0) $class_name = "sunday";
                        elseif($j==6) $class_name = "saturday";
                        else $class_name= "";

                        $date = date("Y. m. d", mktime(0,0,0,$m, $day, $y));
                        $date_str = date("Ymd", mktime(0,0,0,$m, $day, $y));


                        if(date("Ymd")==$date_str) $sel_class = "today";
                        else $sel_class = "";
            ?>
            <div class="calendar_cell <?=$sel_class?> <?=$class_name?>" onclick="selectDate('<?=$date?>','<?=$date_str?>','<?=$callback_func?>');return false;"><div><?=$day?></div></div>
            <?
                    }
                }
            ?>
        </div>
    </div>

    <script type="text/javascript">
        xAddEventListener(window,'load', setFixedPopupSize);
    </script>
</body>
</html>
