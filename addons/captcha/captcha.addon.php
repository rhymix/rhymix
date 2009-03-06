<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file captcha.addon.php
     * @author zero (zero@nzeo.com)
     * @brief 특정 action을 실행할때 captcah를 띄우도록 함
     **/

    // before_module_proc 일 경우 && act_type != everytime 이면 세션 초기화
    if($called_position == "before_module_proc" && $addon_info->act_type == 'everytime' && $_SESSION['captcha_authed']) {
        unset($_SESSION['captcha_authed']);

    // before_module_init 일때에 captcha 동작
    } else if($called_position == 'before_module_init') {

        $logged_info = Context::get('logged_info');
        if($logged_info->is_admin == 'Y' || $logged_info->is_site_admin) return;
        if($addon_info->target != 'all' && Context::get('is_logged')) return;

        // 캡챠 인증이 되지 않은 세션이면 실행 시작
        if(!$_SESSION['captcha_authed']) {

            // 언어파일 로드
            Context::loadLang(_XE_PATH_.'addons/captcha/lang');

            // 캡챠 세션 세팅
            if(Context::get('act')=='setCaptchaSession') {
                $f = FileHandler::readDir('./addons/captcha/icon');
                shuffle($f);
                $key = rand(0,count($f)-1);
                $keyword = str_replace('.gif','',$f[$key]);
                $_SESSION['captcha_keyword'] = $keyword;
                $target = Context::getLang('target_captcha');
                header("Content-Type: text/xml; charset=UTF-8");
                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                header("Cache-Control: no-store, no-cache, must-revalidate");
                header("Cache-Control: post-check=0, pre-check=0", false);
                header("Pragma: no-cache");
                printf("<response>\r\n<error>0</error>\r\n<message>success</message>\r\n<about><![CDATA[%s]]></about>\r\n<keyword><![CDATA[%s]]></keyword>\r\n</response>",Context::getLang('about_captcha'),$target[$keyword]);
                Context::close();
                exit();

            // 캡챠 이미지 출력
            } else if(Context::get('act')=='captchaImage') {
                $f = FileHandler::readDir('./addons/captcha/icon');
                shuffle($f);
                $keyword = $_SESSION['captcha_keyword'];
                for($key=0,$c=count($f);$key<$c;$key++) {
                    if($keyword.".gif" == $f[$key]) break;
                }

                // 이미지 만들기
                $thumb = imagecreatetruecolor(250,100);
                for($i=0,$c=count($f);$i<$c;$i++) {
                    $x = ($i%5)*50;
                    $y = $i>4?0:50;
                    imagedestroy($dummy);
                    $dummy = imagecreatefromgif('./addons/captcha/icon/'.$f[$i]);
                    imagecopyresampled($thumb, $dummy, $x, $y, 0, 0, 50, 50, 50, 50);

                    if($i==$key) {
                        $_SESSION['captcha_x'] = $x;
                        $_SESSION['captcha_y'] = $y;
                    }
                }
                imagedestroy($dummy);
                header("Cache-Control: ");
                header("Pragma: ");
                header("Content-Type: image/png");
                imagepng($thumb, null,9);
                imagedestroy($thumb);
                Context::close();
                exit();

            // 캡챠 이미지 점검
            } else if(Context::get('act')=='captchaCompare') {
                $x = Context::get('mx');
                $y = Context::get('my');
                $sx = $_SESSION['captcha_x'];
                $sy = $_SESSION['captcha_y'];

                if($x>=$sx && $x<=$sx+50 && $y>=$sy && $y<=$sy+50) $_SESSION['captcha_authed'] = true;
                else $_SESSION['captcha_authed'] = false;

                header("Content-Type: text/xml; charset=UTF-8");
                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                header("Cache-Control: no-store, no-cache, must-revalidate");
                header("Cache-Control: post-check=0, pre-check=0", false);
                header("Pragma: no-cache");
                print("<response>\r\n<error>0</error>\r\n<message>success</message>\r\n</response>");

                Context::close();

                exit();
            }

            Context::addJsFile('./addons/captcha/captcha.js',false);

            // 게시판/ 이슈트래커의 글쓰기/댓글쓰기 액션 호출시 세션 비교
            if(in_array(Context::get('act'), array('procBoardInsertDocument','procBoardInsertComment','procIssuetrackerInsertIssue','procIssuetrackerInsertHistory'))) {
                $this->error = "msg_not_permitted";
            }
        }
    }
?>
