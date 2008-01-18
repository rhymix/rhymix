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
	    $dates = sprintf("%s-%s-%s %s:%s:%s+0", substr($dates,0,4), substr($dates,4,2), substr($dates,6,2), substr($dates,9,2), substr($dates,11,2), substr($dates,13,2));
	    $dates = date("Y-m-d H:i:s", strtotime($dates) + $plus + zgap());
	    return $dates;
	}

        /**
         * @brief Displaying Calendar 
         **/
        function dispLifepodContent() {
            // check permission
            if(!$this->grant->view) return $this->dispLifepodMessage('msg_not_permitted');

            $oLifepodModel = &getModel('lifepod');
	    $caladdresses = split(", ", $this->module_info->calendar_address);
	    $cYear = Context::get('year');
	    $cMonth = Context::get('month');
	    $cDay = Context::get('day');

	    $calendars = array();
           
	    foreach($caladdresses as $key=>$val)
	    {
		$page = $oLifepodModel->getPage($val, $cYear, $cMonth, $cDay);
		for($j=0;$j<count($page->data);$j++)
		{
		    $data = &$page->data[$j];
		    if($data->childNodes["date-start"])
		    {
			$data->childNodes["date-start"]->body = $this->dateFormatChange($data->childNodes["date-start"]->body);
		    }

		    if($data->childNodes["date-end"])
		    {
			$plus = 0;
			if($data->childNodes["type"]->body == "daylong")
			    $plus = -1;
			$data->childNodes["date-end"]->body = $this->dateFormatChange($data->childNodes["date-end"]->body, $plus);
		    }

		    $data->childNodes["description"]->body = str_replace("\n", "<BR />", $data->childNodes["description"]->body);
		    $data->childNodes["description"]->body = str_replace("'", "\'", $data->childNodes["description"]->body);
		    $data->childNodes["title"]->body = str_replace("'", "\'", $data->childNodes["title"]->body);
		}
		$calendars[] = $page;
	    }

            Context::set('calendars', $calendars);

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
