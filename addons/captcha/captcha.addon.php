<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file captcha.addon.php
     * @author NHN (developers@xpressengine.com)
     * @brief 특정 action을 실행할때 captcha를 띄우도록 함
	 *        영어 알파벳을 입력, 음성기능 추가
     **/

	if(!class_exists('AddonCaptcha'))
	{
		class AddonCaptcha
		{
			var $addon_info;

			function setInfo(&$addon_info)
			{
				$this->addon_info = $addon_info;
			}

			function before_module_proc()
			{
			    if($this->addon_info->act_type == 'everytime' && $_SESSION['captcha_authed']) {
			        unset($_SESSION['captcha_authed']);
				}
			}

			function before_module_init(&$ModuleHandler)
			{
				$logged_info = Context::get('logged_info');
				if($logged_info->is_admin == 'Y' || $logged_info->is_site_admin) return false;
				if($this->addon_info->target != 'all' && Context::get('is_logged')) return false;

				$target_acts = array('procBoardInsertDocument','procBoardInsertComment','procIssuetrackerInsertIssue','procIssuetrackerInsertHistory','procTextyleInsertComment');
				if($this->addon_info->apply_find_account=='apply') $target_acts[] = 'procMemberFindAccount';
				if($this->addon_info->apply_resend_auth_mail=='apply') $target_acts[] = 'procMemberResendAuthMail';
				if($this->addon_info->apply_signup=='apply') $target_acts[] = 'procMemberInsert';

				if(Context::getRequestMethod()!='XMLRPC' && Context::getRequestMethod()!=='JSON')
				{
					Context::addHtmlHeader('<script type="text/javascript"> var captchaTargetAct = new Array("'.implode('","',$target_acts).'"); </script>');
					Context::addJsFile('./addons/captcha/captcha.js',false, '', null, 'body');
				}

				// 게시판/ 이슈트래커의 글쓰기/댓글쓰기 액션 호출시 세션 비교
				if(!$_SESSION['captcha_authed'] && in_array(Context::get('act'), $target_acts)) {
					Context::loadLang('./addons/captcha/lang');
					$ModuleHandler->error = "captcha_denied";
				}

				return true;
			}

			function before_module_init_setCaptchaSession()
			{
				if($_SESSION['captcha_authed']) return false;

				// 언어파일 로드
				Context::loadLang(_XE_PATH_.'addons/captcha/lang');

				// 키워드 생성
				$arr = range('A','Y');
				shuffle($arr);
				$arr = array_slice($arr,0,6);
                $_SESSION['captcha_keyword'] = join('', $arr);

                $target = Context::getLang('target_captcha');
                header("Content-Type: text/xml; charset=UTF-8");
                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                header("Cache-Control: no-store, no-cache, must-revalidate");
                header("Cache-Control: post-check=0, pre-check=0", false);
                header("Pragma: no-cache");
                printf("<response>\r\n <error>0</error>\r\n <message>success</message>\r\n <about_captcha><![CDATA[%s]]></about_captcha>\r\n <captcha_reload><![CDATA[%s]]></captcha_reload>\r\n <captcha_play><![CDATA[%s]]></captcha_play>\r\n <cmd_input><![CDATA[%s]]></cmd_input>\r\n <cmd_cancel><![CDATA[%s]]></cmd_cancel>\r\n </response>"
						,Context::getLang('about_captcha')
						,Context::getLang('captcha_reload')
						,Context::getLang('captcha_play')
						,Context::getLang('cmd_input')
						,Context::getLang('cmd_cancel')
						);
                Context::close();
                exit();
			}

			function before_module_init_captchaImage()
			{
				if($_SESSION['captcha_authed']) return false;

			    $keyword = $_SESSION['captcha_keyword'];
				$im = $this->createCaptchaImage($keyword);

                header("Cache-Control: ");
                header("Pragma: ");
                header("Content-Type: image/png");

				imagepng($im);
				imagedestroy($im);

				Context::close();
                exit();
			}

			function createCaptchaImage($string)
			{
				$arr = array();
				for($i=0,$c=strlen($string);$i<$c;$i++) $arr[] = $string{$i};

				// 글자 하나 사이즈
				$w = 18;
				$h = 25;

				// 글자 수
				$c = count($arr);

				// 글자 이미지
				$im = array();

				// 총사이즈로 바탕 이미지 생성
				$im[] = imagecreate(($w+2)*count($arr), $h);

				$deg = range(-30,30);
				shuffle($deg);

				// 글자별 이미지 생성
				foreach($arr as $i => $str)
				{
					$im[$i+1] = @imagecreate($w, $h);
					$background_color = imagecolorallocate($im[$i+1], 255, 255, 255);
					$text_color = imagecolorallocate($im[$i+1], 0, 0, 0);

					// 글자폰트(사이즈) 조절
					$ran = range(1,20);
					shuffle($ran);

					if(function_exists('imagerotate'))
					{
						imagestring($im[$i+1], (array_pop($ran)%3)+3, 2, (array_pop($ran)%8),  $str, $text_color);
						$im[$i+1] = imagerotate($im[$i+1], array_pop($deg), 0);

						$background_color = imagecolorallocate($im[$i+1], 255, 255, 255);
						imagecolortransparent($im[$i+1], $background_color);
					}
					else
					{
						imagestring($im[$i+1], (array_pop($ran)%3)+3, 2, (array_pop($ran)%4), $str, $text_color);
					}
				}
				
				// 각글자 이미지를 합침
				for($i=1;$i<count($im);$i++)
				{
					imagecopy($im[0],$im[$i],(($w+2)*($i-1)),0,0,0,$w,$h);
					imagedestroy($im[$i]);
				}

				// 이미지 확대
				$big_count = 2;
				$big = imagecreatetruecolor(($w+2)*$big_count*$c, $h*$big_count);
				imagecopyresized($big, $im[0], 0, 0, 0, 0, ($w+2)*$big_count*$c, $h*$big_count, ($w+2)*$c, $h);
				imagedestroy($im[0]);

				if(function_exists('imageantialias')) imageantialias($big,true);

				// 배경 라인 및 점찍기
				$line_color = imagecolorallocate($big, 0, 0, 0);

				$w = ($w+2)*$big_count*$c;
				$h = $h*$big_count;
				$d = array_pop($deg);

				for($i=-abs($d);$i<$h+abs($d);$i=$i+7) imageline($big,0,$i+$d,$w,$i,$line_color);

				$x = range(0,($w-10));
				shuffle($x);

				for($i=0;$i<200;$i++) imagesetpixel($big,$x[$i]%$w,$x[$i+1]%$h,$line_color);

				return $big;
			}

			function before_module_init_captchaAudio()
			{
				if($_SESSION['captcha_authed']) return false;
				
				$keyword = strtoupper($_SESSION['captcha_keyword']);
				$data = $this->createCaptchaAudio($keyword);

				header('Content-type: audio/mpeg'); 
				header("Content-Disposition: attachment; filename=\"captcha_audio.mp3\"");
				header('Cache-Control: no-store, no-cache, must-revalidate');
				header('Expires: Sun, 1 Jan 2000 12:00:00 GMT');
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
				header('Content-Length: ' . strlen($data));

				echo $data;
                Context::close();
				exit();
			}

			function createCaptchaAudio($string)
			{
				$data = '';
				$_audio = './addons/captcha/audio/F_%s.mp3';
				for($i=0,$c=strlen($string);$i<$c;$i++)
				{
					$_data = FileHandler::readFile(sprintf($_audio, $string{$i}));

					$start = rand(5, 68); // 해더 4바이트, 데이터 영역 64바이트 정도 랜덤하게 시작
					$datalen = strlen($_data) - $start - 256; // 마지막 unchanged 256 바이트 

					for($j=$start;$j<$datalen;$j+=64)
					{
						$ch = ord($_data{$j});
						if($ch<9 || $ch>119) continue;
						$_data{$j} = chr($ch+rand(-8,8));
					}

					$data .= $_data;
				}

				return $data;
			}

			function before_module_init_captchaCompare()
			{
				if($_SESSION['captcha_authed']) return false;

                if(strtoupper($_SESSION['captcha_keyword']) == strtoupper(Context::get('secret_text'))) $_SESSION['captcha_authed'] = true;
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
		}

		$GLOBALS['__AddonCaptcha__'] = new AddonCaptcha;
		$GLOBALS['__AddonCaptcha__']->setInfo($addon_info);
	}

	$oAddonCaptcha = &$GLOBALS['__AddonCaptcha__'];

	if(method_exists(&$oAddonCaptcha, $called_position))
	{
		if(!call_user_func(array(&$oAddonCaptcha, $called_position), &$this)) return false;
	}

	$addon_act = Context::get('captcha_action');
	if($addon_act && method_exists(&$oAddonCaptcha, $called_position.'_'.$addon_act))
	{
		if(!call_user_func(array(&$oAddonCaptcha, $called_position.'_'.$addon_act), &$this)) return false;
	}

?>
