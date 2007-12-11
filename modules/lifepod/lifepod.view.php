<?php
    /**
     * @class  lifepodView
     * @author haneul (haneul0318@gmail.com)
     * @brief  lifepod 모듈의 admin view 클래스
     **/

    class lifepodView extends lifepod {

        /**
         * @brief 초기화
         **/
        function init() {
            /**
             * 템플릿에서 사용할 변수를 Context::set()
             * 혹시 사용할 수 있는 module_srl 변수를 설정한다.
             **/
            if($this->module_srl) Context::set('module_srl',$this->module_srl);

            Context::set('module_info',$this->module_info);

            /**
             * 모듈정보에서 넘어오는 skin값을 이용하여 최종 출력할 템플릿의 위치를 출력한다.
             * $this->module_path는 ./modules/guestbook/의 값을 가지고 있다
             **/
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            $this->setTemplatePath($template_path);
        }

        /**
         * @brief Reformatting date data from Lifepod API into data type compatible to Lifepod UI 
         **/
	function dateFormatChange($dates, $plus = 0) {
	    $dates = ereg_replace("(\d\d\d\d)(\d\d)(\d\d)T(\d\d)(\d\d)(\d\d)Z", "\\1-\\2-\\3 \\4:\\5:\\6+0", $dates);
	    $dates = date("Y-m-d H:i:s", strtotime($dates) + $plus);
	    return $dates;
	}

        /**
         * @brief Displaying Calendar 
         **/
        function dispLifepodContent() {
            // check permission
            if(!$this->grant->view) return $this->dispLifepodMessage('msg_not_permitted');

            $oLifepodModel = &getModel('lifepod');
            $oLifepodModel->setInfo($this->module_info->calendar_address);
	    $cYear = Context::get('year');
	    $cMonth = Context::get('month');
	    $cDay = Context::get('day');
            
            $page = $oLifepodModel->getPage($cYear, $cMonth, $cDay);
	    foreach ($page->data as $key => $val)
	    {
		if($val->childNodes["date-start"])
		{
		    $val->childNodes["date-start"]->body = $this->dateFormatChange($val->childNodes["date-start"]->body);
		}
		if($val->childNodes["date-end"])
		{
		    $plus = 0;
		    if($val->childNodes["type"]->body == "daylong")
			$plus = -1;
		    $val->childNodes["date-end"]->body = $this->dateFormatChange($val->childNodes["date-end"]->body, $plus);
		}
	    }

            Context::set('page', $page);

            $this->setTemplateFile('list');
        }

        /**
         * @brief 메세지 출력
         **/
        function dispLifepodMessage($msg_code) {
            $msg = Context::getLang($msg_code);
            if(!$msg) $msg = $msg_code;
            Context::set('message', $msg);
            $this->setTemplateFile('message');
        }

    }
?>
