<?php
    /**
     * @class image_counter
     * @author zero (zero@nzeo.com)
     * @version 0.1
     * @brief counter 모듈의 데이터를 이용하여 counter 현황을 출력
     **/

    class image_counter extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         * ./widgets/위젯/conf/info.xml에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            // 인자 값 정리
            $graph_width = (int)$args->graph_width?$args->graph_width:150;
            $graph_height = (int)$args->graph_height?$args->graph_height:100;
            $day_range = (int)$args->day_range?$args->day_range:7;
            if($day_range < 7) $day_range = 7;

            $bg_color = hexrgb($args->bg_color?$args->bg_color:'#FFFFFF');
            $check_bg_color = hexrgb($args->check_bg_color?$args->check_bg_color:'#F9F9F9');
            $grid_color = hexrgb($args->grid_color?$args->grid_color:'#dbdbdb');
            $unique_line_color = hexrgb($args->unique_line_color?$args->unique_line_color:'#BBBBBB');
            $unique_text_color = hexrgb($args->unique_text_color?$args->unique_text_color:'#666666');
            $point_color = hexrgb($args->point_color?$args->point_color:'#ed3027');

            // 시작일 부터 오늘까지 일단 배열 만들어 놓기
            $start_time = ztime(date("YmdHis"))-$day_range*60*60*24;
            $end_time = time();
            $day_check_falg = 0;
            for($i=$start_time;$i<$end_time;$i+= 60*60*24) {
                $data[date("Ymd", $i+60*60*24)] = 0;
                $day_check_falg++;
                if($day_check_falg>$day_range) break;
            }
            unset($obj);

            // 현재부터 지난 $day_range동안의 카운터 로그를 가져옴
            $obj->e_regdate = date("Ymd"); 
            $obj->s_regdate = date("Ymd", ztime(date("YmdHis"))-$day_range*60*60*24+1); 
            $output = executeQuery('widgets.image_counter.getCounterStatus', $obj);

            // 결과가 있다면 loop를 돌면서 최고/최저값을 구하고 그래프를 그릴 준비
            $max_unique_visitor = 0;
            $min_unique_visitor = 99999999999;
            if(count($output->data)) {
                foreach($output->data as $key => $val) {
                    if($max_unique_visitor < $val->unique_visitor) $max_unique_visitor = $val->unique_visitor;
                    if($min_unique_visitor > $val->unique_visitor) $min_unique_visitor = $val->unique_visitor;
                    $data[$val->regdate] = $val;
                }
            }

            // 이미지를 그림 (이미지 위치는 ./files/cache/widget_cache/couter_graph.gif로 고정)
            $image_src = "files/cache/widget_cache/couter_graph.gif";

            // 이미지 생성
            $image = imagecreate($graph_width, $graph_height);

            // 각 종류의 색상을 지정
            $gridLine = imagecolorallocate($image, $grid_color['red'], $grid_color['green'], $grid_color['blue']);
            $fillBack = imagecolorallocate($image, $bg_color['red'], $bg_color['green'], $bg_color['blue']);
            $checkFillBack = imagecolorallocate($image, $check_bg_color['red'], $check_bg_color['green'], $check_bg_color['blue']);
            $visitorLine = imagecolorallocate($image, $unique_line_color['red'], $unique_line_color['green'], $unique_line_color['blue']);
            $visitorText = imagecolorallocate($image, $unique_text_color['red'], $unique_text_color['green'], $unique_text_color['blue']);
            $pointColor = imagecolorallocate($image, $point_color['red'], $point_color['green'], $point_color['blue']);

            // 배경선 채우기 
            imagefilledrectangle($image, 0, 0, $graph_width-1, $graph_height-1, $fillBack);

            // 가로선 그리기
            $y_gap = ($graph_height - 32) /3;
            for($i=0;$i<4;$i++) {
                imageline($image, 5, 5+($i*$y_gap), $graph_width-5, 5+($i*$y_gap), $gridLine);
            }

            // 세로선 그리기
            $x_gap = ($graph_width - 30) / ($day_range-1);
            for($i=0;$i<$day_range;$i++) {
                imageline($image, 15+($i*$x_gap), 5, 15+($i*$x_gap), $graph_height - 27, $gridLine);
            }

            // 체크 무늬 배경 칠하기
            for($j=0;$j<$day_range-1;$j++) {
                for($i=0;$i<3;$i++) {
                    if( ($j+$i)%2==1) continue;
                    imagefilledrectangle($image, 15+($j*$x_gap)+1, 5+($i*$y_gap)+1, 15+($j*$x_gap)+$x_gap-1, 5+($i*$y_gap)+$y_gap-1, $checkFillBack);
                }
            }

            // 그래프 그리기
            $prev_x = 0;
            $prev_y = $graph_height-45;
            $step = 0;

            // 선 그림
            foreach($data as $date => $val) {
                // 그래프를 그리기 위한 좌표 구함
                $unique_visitor = $val->unique_visitor;
                if($max_unique_visitor == 0) $per = 0;
                else $per = $val->unique_visitor / $max_unique_visitor;

                // x,y 좌표 구함
                $cur_x = (int)($step * $x_gap);
                $cur_y = (int)( ($graph_height-45) - ($graph_height-45)*$per);

                imageline($image, $prev_x+15, $prev_y+15, $cur_x+15, $cur_y+15, $visitorLine);

                $prev_x = $cur_x;
                $prev_y = $cur_y;

                $step ++;
            }

            // 포인트 + 숫자 표시
            $prev_x = 0;
            $prev_y = $graph_height-45;
            $step = 0;
            foreach($data as $date => $val) {
                // 그래프를 그리기 위한 좌표 구함
                $unique_visitor = $val->unique_visitor;
                if($max_unique_visitor == 0) $per = 0;
                else $per = $val->unique_visitor / $max_unique_visitor;

                // x,y 좌표 구함
                $cur_x = (int)($step * $x_gap);
                $cur_y = (int)( ($graph_height-45) - ($graph_height-45)*$per);

                imagefilledrectangle($image, $cur_x+15-1, $cur_y+15-1, $cur_x+15+1, $cur_y+15+1, $pointColor);

                for($j=0;$j<strlen($unique_visitor);$j++) {
                    imageString($image, 1, $cur_x+6+$j*4, $cur_y+5 + ($step%2-1)*13+13, substr($unique_visitor,$j,1), $visitorText);
                }

                $prev_x = $cur_x;
                $prev_y = $cur_y;

                imageString($image, 1, $cur_x+15-1, $graph_height - 25, substr($date,6,2), $gridLine);

                $step ++;
            }
            
            imageString($image, 1, $graph_width - 100, $graph_height - 15, date("Y-m-d H:i:s"), $gridLine);

            // 이미지 저장
            @imagegif($image, $image_src, 100);
            @chmod($image_src, 0644);

            // graph의 img 태그 값을 return
            return sprintf('<img src="%s%s" border="0" alt="counter" />',  Context::getRequestUri(), $image_src);
        }
    }
?>
