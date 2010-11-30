<?php

class HTMLDisplayHandler {
	/**
	 * @brief Produce HTML compliant content given a module object.\n
	 * @param[in] $oModule the module object
	 **/
	function toDoc(&$oModule) 
	{
		$oTemplate = &TemplateHandler::getInstance();

		// compile module tpl
		$template_path = $oModule->getTemplatePath();
		$tpl_file = $oModule->getTemplateFile();
		$output = $oTemplate->compile($template_path, $tpl_file);

		// add #xeAdmin div for adminitration pages
		if(Context::getResponseMethod() == 'HTML') {
			if(Context::get('module')!='admin' && strpos(Context::get('act'),'Admin')>0) $output = '<div id="xeAdmin">'.$output.'</div>';

			if(Context::get('layout') != 'none') {
				if(__DEBUG__==3) $start = getMicroTime();

				Context::set('content', $output);

				$layout_path = $oModule->getLayoutPath();
				$layout_file = $oModule->getLayoutFile();

				$edited_layout_file = $oModule->getEditedLayoutFile();

				// 현재 요청된 레이아웃 정보를 구함
				$oLayoutModel = &getModel('layout');
				$layout_info = Context::get('layout_info');
				$layout_srl = $layout_info->layout_srl;

				// 레이아웃과 연결되어 있으면 레이아웃 컴파일
				if($layout_srl > 0){

					// faceoff 레이아웃일 경우 별도 처리
					if($layout_info && $layout_info->type == 'faceoff') {
						$oLayoutModel->doActivateFaceOff($layout_info);
						Context::set('layout_info', $layout_info);
					}

					// 관리자 레이아웃 수정화면에서 변경된 CSS가 있는지 조사
					$edited_layout_css = $oLayoutModel->getUserLayoutCss($layout_srl);

					if(file_exists($edited_layout_css)) Context::addCSSFile($edited_layout_css,true,'all','',100);
				}
				if(!$layout_path) $layout_path = "./common/tpl";
				if(!$layout_file) $layout_file = "default_layout";
				$output = $oTemplate->compile($layout_path, $layout_file, $edited_layout_file);

				if(__DEBUG__==3) $GLOBALS['__layout_compile_elapsed__'] = getMicroTime()-$start;

				if(preg_match('/MSIE/i',$_SERVER['HTTP_USER_AGENT']) && (Context::get("_use_ssl")=='optional'||Context::get("_use_ssl")=="always")) {
					Context::addHtmlFooter('<iframe id="xeTmpIframe" name="xeTmpIframe" style="width:1px;height:1px;position:absolute;top:-2px;left:-2px;"></iframe>');
				}
			}
		}
		return $output;
	}

	function prepareToPrint(&$output) {
		if(Context::getResponseMethod() != 'HTML') return;
		
		if(__DEBUG__==3) $start = getMicroTime();

		// body 내의 <style ..></style>를 header로 이동
		$output = preg_replace_callback('!<style(.*?)<\/style>!is', array($this,'_moveStyleToHeader'), $output);

		// 메타 파일 변경 (캐싱기능등으로 인해 위젯등에서 <!--Meta:경로--> 태그를 content에 넣는 경우가 있음
		$output = preg_replace_callback('/<!--Meta:([a-z0-9\_\/\.\@]+)-->/is', array($this,'_transMeta'), $output);

		// rewrite module 사용시 생기는 상대경로에 대한 처리를 함
		if(Context::isAllowRewrite()) {
			$url = parse_url(Context::getRequestUri());
			$real_path = $url['path'];

			$pattern = '/src=("|\'){1}(\.\/)?(files\/attach|files\/cache|files\/faceOff|files\/member_extra_info|modules|common|widgets|widgetstyle|layouts|addons)\/([^"\']+)\.(jpg|jpeg|png|gif)("|\'){1}/s';
			$output = preg_replace($pattern, 'src=$1'.$real_path.'$3/$4.$5$6', $output);

			$pattern = '/href=("|\'){1}(\?[^"\']+)/s';
			$output = preg_replace($pattern, 'href=$1'.$real_path.'$2', $output);

			if(Context::get('vid')) {
				$pattern = '/\/'.Context::get('vid').'\?([^=]+)=/is';
				$output = preg_replace($pattern, '/?$1=', $output);
			}
		}

		// 간혹 background-image에 url(none) 때문에 request가 한번 더 일어나는 경우가 생기는 것을 방지
		$output = preg_replace('/url\((["\']?)none(["\']?)\)/is', 'none', $output);

		if(__DEBUG__==3) $GLOBALS['__trans_content_elapsed__'] = getMicroTime()-$start;

		// 불필요한 정보 제거
		$output = preg_replace('/member\_\-([0-9]+)/s','member_0',$output);

		// 최종 레이아웃 변환
		Context::set('content', $output);
		$oTemplate = &TemplateHandler::getInstance();
		if(Mobile::isFromMobilePhone()) {
			$output = $oTemplate->compile('./common/tpl', 'mobile_layout');
		}
		else
		{
			$this->_loadJSCSS();
			$output = $oTemplate->compile('./common/tpl', 'common_layout');
		}

		// 사용자 정의 언어 변환
		$oModuleController = &getController('module');
		$oModuleController->replaceDefinedLangCode($output);
	}

	/**
	 * @brief add html style code extracted from html body to Context, which will be
	 * printed inside <header></header> later.
	 * @param[in] $oModule the module object
	 **/
	function _moveStyleToHeader($matches) {
		Context::addHtmlHeader($matches[0]);
	}

	/**
	 * @brief add given .css or .js file names in widget code to Context       
	 * @param[in] $oModule the module object
	 **/
	function _transMeta($matches) {
		if(substr($matches[1],'-4')=='.css') Context::addCSSFile($matches[1]);
		elseif(substr($matches[1],'-3')=='.js') Context::addJSFile($matches[1]);
	}

	function _loadJSCSS()
	{
		$oContext =& Context::getInstance();
		// add common JS/CSS files
		$oContext->_addJsFile("./common/js/jquery.js", '', -100000);
		$oContext->_addJsFile("./common/js/x.js", '', -100000);
		$oContext->_addJsFile("./common/js/common.js", '', -100000);
		$oContext->_addJsFile("./common/js/js_app.js", '', -100000);
		$oContext->_addJsFile("./common/js/xml_handler.js", '', -100000);
		$oContext->_addJsFile("./common/js/xml_js_filter.js", '', -100000);
		$oContext->_addCSSFile("./common/css/default.css", 'all', '', -100000);
		$oContext->_addCSSFile("./common/css/button.css", 'all', '', -100000);

		// for admin page, add admin css
		if(Context::get('module')=='admin' || strpos(Context::get('act'),'Admin')>0){
			$oContext->_addCSSFile("./modules/admin/tpl/css/font.css", 'all', '',10000);
			$oContext->_addCSSFile("./modules/admin/tpl/css/pagination.css", 'all', '', 100001);
			$oContext->_addCSSFile("./modules/admin/tpl/css/admin.css", 'all', '', 100002);
		}
	}
}
