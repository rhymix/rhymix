<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

if(!defined("__XE__")) exit();

/**
 * @file captcha.addon.php
 * @author NAVER (developers@xpressengine.com)
 * @brief Captcha for a particular action
 * English alphabets and voice verification added
 * */
if(!class_exists('AddonMemberCaptcha', false))
{
	// On the mobile mode, XE Core does not load jquery and xe.js as normal.
	if(Mobile::isFromMobilePhone())
	{
		Context::loadFile(array('./common/js/jquery.min.js', 'head', NULL, -100000), true);
		Context::loadFile(array('./common/js/xe.min.js', 'head', NULL, -100000), true);
	}

	class AddonMemberCaptcha
	{
		var $addon_info;
		var $target_acts = NULL;

		function setInfo(&$addon_info)
		{
			$this->addon_info = $addon_info;
		}

		function before_module_proc()
		{
			// if($_SESSION['member_captcha_authed'])
			// {
				unset($_SESSION['member_captcha_authed']);
			// }
		}

		function before_module_init(&$ModuleHandler)
		{
			$logged_info = Context::get('logged_info');
			if($logged_info->is_admin == 'Y' || $logged_info->is_site_admin)
			{
				return false;
			}
			// if($this->addon_info->target != 'all' && Context::get('is_logged'))
			// {
			// 	return false;
			// }
			if($_SESSION['XE_VALIDATOR_ERROR'] == -1)
			{
				$_SESSION['member_captcha_authed'] = false;
			}
			if($_SESSION['member_captcha_authed'])
			{
				return false;
			}

			$type = Context::get('captchaType');

			$this->target_acts = array();
			if($this->addon_info->apply_find_account == 'apply')
			{
				$this->target_acts[] = 'procMemberFindAccount';
			}
			if($this->addon_info->apply_resend_auth_mail == 'apply')
			{
				$this->target_acts[] = 'procMemberResendAuthMail';
			}
			if($this->addon_info->apply_signup == 'apply')
			{
				$this->target_acts[] = 'procMemberInsert';
			}

			if(Context::getRequestMethod() != 'XMLRPC' && Context::getRequestMethod() !== 'JSON')
			{
				if($type == 'inline')
				{
					if(!$this->compareCaptcha())
					{
						Context::loadLang(_XE_PATH_ . 'addons/captcha_member/lang');
						$_SESSION['XE_VALIDATOR_ERROR'] = -1;
						$_SESSION['XE_VALIDATOR_MESSAGE'] = Context::getLang('captcha_denied');
						$_SESSION['XE_VALIDATOR_MESSAGE_TYPE'] = 'error';
						$_SESSION['XE_VALIDATOR_RETURN_URL'] = Context::get('error_return_url');
						$ModuleHandler->_setInputValueToSession();
					}
				}
				else
				{
					Context::addHtmlHeader('<script>
						if(!captchaTargetAct) {var captchaTargetAct = [];}
						captchaTargetAct.push("' . implode('","', $this->target_acts) . '");
						</script>');
					Context::loadFile(array('./addons/captcha_member/captcha.min.js', 'body', '', null), true);
				}
			}

			// compare session when calling actions such as writing a post or a comment on the board/issue tracker module
			if(!$_SESSION['member_captcha_authed'] && in_array(Context::get('act'), $this->target_acts))
			{
				Context::loadLang(_XE_PATH_ . 'addons/captcha_member/lang');
				$ModuleHandler->error = "captcha_denied";
			}

			return true;
		}

		function createKeyword()
		{
			$type = Context::get('captchaType');
			if($type == 'inline' && $_SESSION['captcha_keyword'])
			{
				return;
			}

			$arr = range('A', 'Y');
			shuffle($arr);
			$arr = array_slice($arr, 0, 6);
			$_SESSION['captcha_keyword'] = join('', $arr);
		}

		function before_module_init_setCaptchaSession()
		{
			if($_SESSION['member_captcha_authed'])
			{
				return false;
			}
			// Load language files
			Context::loadLang(_XE_PATH_ . 'addons/captcha_member/lang');
			// Generate keywords
			$this->createKeyword();

			$target = Context::getLang('target_captcha');
			header("Content-Type: text/xml; charset=UTF-8");
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
			printf("<response>\r\n <error>0</error>\r\n <message>success</message>\r\n <about_captcha><![CDATA[%s]]></about_captcha>\r\n <captcha_reload><![CDATA[%s]]></captcha_reload>\r\n <captcha_play><![CDATA[%s]]></captcha_play>\r\n <cmd_input><![CDATA[%s]]></cmd_input>\r\n <cmd_cancel><![CDATA[%s]]></cmd_cancel>\r\n </response>"
					, Context::getLang('about_captcha')
					, Context::getLang('captcha_reload')
					, Context::getLang('captcha_play')
					, Context::getLang('cmd_input')
					, Context::getLang('cmd_cancel')
			);
			Context::close();
			exit();
		}

		function before_module_init_captchaImage()
		{
			if($_SESSION['member_captcha_authed'])
			{
				return false;
			}
			if(Context::get('renew'))
			{
				$this->createKeyword();
			}

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
			for($i = 0, $c = strlen($string); $i < $c; $i++)
			{
				$arr[] = $string{$i};
			}

			// Font site
			$w = 18;
			$h = 25;

			// Character length
			$c = count($arr);

			// Character image
			$im = array();

			// Create an image by total size
			$im[] = imagecreate(($w + 2) * count($arr), $h);

			$deg = range(-30, 30);
			shuffle($deg);

			// Create an image for each letter
			foreach($arr as $i => $str)
			{
				$im[$i + 1] = @imagecreate($w, $h);
				$background_color = imagecolorallocate($im[$i + 1], 255, 255, 255);
				$text_color = imagecolorallocate($im[$i + 1], 0, 0, 0);

				// Control font size
				$ran = range(1, 20);
				shuffle($ran);

				if(function_exists('imagerotate'))
				{
					imagestring($im[$i + 1], (array_pop($ran) % 3) + 3, 2, (array_pop($ran) % 8), $str, $text_color);
					$im[$i + 1] = imagerotate($im[$i + 1], array_pop($deg), 0);

					$background_color = imagecolorallocate($im[$i + 1], 255, 255, 255);
					imagecolortransparent($im[$i + 1], $background_color);
				}
				else
				{
					imagestring($im[$i + 1], (array_pop($ran) % 3) + 3, 2, (array_pop($ran) % 4), $str, $text_color);
				}
			}

			// Combine images of each character
			for($i = 1; $i < count($im); $i++)
			{
				imagecopy($im[0], $im[$i], (($w + 2) * ($i - 1)), 0, 0, 0, $w, $h);
				imagedestroy($im[$i]);
			}

			// Larger image
			$big_count = 2;
			$big = imagecreatetruecolor(($w + 2) * $big_count * $c, $h * $big_count);
			imagecopyresized($big, $im[0], 0, 0, 0, 0, ($w + 2) * $big_count * $c, $h * $big_count, ($w + 2) * $c, $h);
			imagedestroy($im[0]);

			if(function_exists('imageantialias'))
			{
				imageantialias($big, true);
			}

			// Background line
			$line_color = imagecolorallocate($big, 0, 0, 0);

			$w = ($w + 2) * $big_count * $c;
			$h = $h * $big_count;
			$d = array_pop($deg);

			for($i = -abs($d); $i < $h + abs($d); $i = $i + 7)
			{
				imageline($big, 0, $i + $d, $w, $i, $line_color);
			}

			$x = range(0, ($w - 10));
			shuffle($x);

			for($i = 0; $i < 200; $i++)
			{
				imagesetpixel($big, $x[$i] % $w, $x[$i + 1] % $h, $line_color);
			}

			return $big;
		}

		function before_module_init_captchaAudio()
		{
			if($_SESSION['member_captcha_authed'])
			{
				return false;
			}

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
			$_audio = './addons/captcha_member/audio/F_%s.mp3';
			for($i = 0, $c = strlen($string); $i < $c; $i++)
			{
				$_data = FileHandler::readFile(sprintf($_audio, $string{$i}));

				$start = rand(5, 68); // Random start in 4-byte header and 64 byte data
				$datalen = strlen($_data) - $start - 256; // Last unchanged 256 bytes

				for($j = $start; $j < $datalen; $j+=64)
				{
					$ch = ord($_data{$j});
					if($ch < 9 || $ch > 119)
					{
						continue;
					}
					$_data{$j} = chr($ch + rand(-8, 8));
				}

				$data .= $_data;
			}

			return $data;
		}

		function compareCaptcha()
		{
			if(!in_array(Context::get('act'), $this->target_acts)) return true;

			if($_SESSION['member_captcha_authed'])
			{
				return true;
			}

			if(strtoupper($_SESSION['captcha_keyword']) == strtoupper(Context::get('secret_text')))
			{
				$_SESSION['member_captcha_authed'] = true;
				return true;
			}

			unset($_SESSION['member_captcha_authed']);

			return false;
		}

		function before_module_init_captchaCompare()
		{
			if(!$this->compareCaptcha())
			{
				return false;
			}

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

		function inlineDisplay()
		{
			unset($_SESSION['member_captcha_authed']);
			$this->createKeyword();

			$swfURL = getUrl() . 'addons/captcha_member/swf/play.swf';
			Context::unloadFile('./addons/captcha_member/captcha.min.js');
			Context::loadFile(array('./addons/captcha_member/inline_captcha.js', 'body'));

			global $lang;

			$tags = <<<EOD
<img src="%s" id="captcha_image" alt="CAPTCHA" width="240" height="50" style="width:240px; height:50px; border:1px solid #b0b0b0" />
<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="0" height="0" id="captcha_audio" align="middle">
<param name="allowScriptAccess" value="always" />
<param name="quality" value="high" />
<param name="movie" value="%s" />
<param name="wmode" value="window" />
<param name="allowFullScreen" value="false">
<param name="bgcolor" value="#fffff" />
<embed src="%s" quality="high" wmode="window" allowFullScreen="false" bgcolor="#ffffff" width="0" height="0" name="captcha_audio" align="middle" allowScriptAccess="always" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
</object>
<button type="button" class="captchaReload text">%s</button>
<button type="button" class="captchaPlay text">%s</button><br />
<input type="hidden" name="captchaType" value="inline" />
<input name="secret_text" type="text" id="secret_text" />
EOD;
			$tags = sprintf($tags, getUrl('captcha_action', 'captchaImage', 'rand', mt_rand(10000, 99999))
					, $swfURL
					, $swfURL
					, $lang->reload
					, $lang->play);
			return $tags;
		}

	}
	$GLOBALS['__AddonMemberCaptcha__'] = new AddonMemberCaptcha;
	$GLOBALS['__AddonMemberCaptcha__']->setInfo($addon_info);
	Context::set('oMemberCaptcha', $GLOBALS['__AddonMemberCaptcha__']);
}

$oAddonMemberCaptcha = &$GLOBALS['__AddonMemberCaptcha__'];

if(method_exists($oAddonMemberCaptcha, $called_position))
{
	if(!call_user_func_array(array(&$oAddonMemberCaptcha, $called_position), array(&$this)))
	{
		return false;
	}
}

$addon_act = Context::get('captcha_action');
if($addon_act && method_exists($oAddonMemberCaptcha, $called_position . '_' . $addon_act))
{
	if(!call_user_func_array(array(&$oAddonMemberCaptcha, $called_position . '_' . $addon_act), array(&$this)))
	{
		return false;
	}
}
/* End of file captcha_member.addon.php */
/* Location: ./addons/captcha_member/captcha_member.addon.php */
