<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  krzipModel
 * @author NAVER (developers@xpressengine.com)
 * @brief  Krzip module model class.
 */

class krzipModel extends krzip
{
	/**
	 * @brief 한국 우편번호 모듈 설정 반환
	 * @return object
	 */
	function getConfig()
	{
		if(!isset($this->module_config))
		{
			$oModuleModel = getModel('module');
			$module_config = $oModuleModel->getModuleConfig('krzip');
			if(!is_object($module_config))
			{
				$module_config = new stdClass();
			}

			/* 기본 설정 추가 */
			$default_config = self::$default_config;
			foreach($default_config as $key => $val)
			{
				if(!isset($module_config->{$key}))
				{
					$module_config->{$key} = $val;
				}
			}

			$this->module_config = $module_config;
		}

		return $this->module_config;
	}

	/**
	 * @brief 여러 포맷의 우편번호를 모듈 표준 포맷으로 변환
	 * @param mixed $values
	 * @return array
	 */
	function getMigratedPostcode($values)
	{
		if(is_array($values))
		{
			$values = implode(' ', $values);
		}

		$output = array('', trim(preg_replace('/\s+/', ' ', $values)), '', '', '');

		/* 우편번호 */
		if(preg_match('/\(?([0-9]{3}-[0-9]{3}|^[0-9]{5})\)?/', $output[1], $matches))
		{
			$output[1] = trim(preg_replace('/\s+/', ' ', str_replace($matches[0], '', $output[1])));
			$output[0] = $matches[1];
		}

		/* 지번 주소 */
		if(preg_match('/\(.+\s.+[읍면동리(마을)(0-9+가)]\s[0-9-]+\)/', $output[1], $matches))
		{
			$output[1] = trim(str_replace($matches[0], '', $output[1]));
			$output[2] = $matches[0];
		}

		/* 부가 정보 */
		if(preg_match('/\(.+[읍면동리(마을)(0-9+가)](?:,.*)?\)/u', $output[1], $matches))
		{
			$output[1] = trim(str_replace($matches[0], '', $output[1]));
			$output[4] = $matches[0];
		}

		/* 상세 주소 */
		if(preg_match('/^(.+ [가-힝]+[0-9]*[동리로길]\s*[0-9-]+(?:번지?)?),?\s+(.+)$/u', $output[1], $matches))
		{
			$output[1] = trim($matches[1]);
			$output[3] = trim($matches[2]);
		}

		return $output;
	}

	/**
	 * @brief 외부 서버로부터 주소 검색 결과 반환
	 * @param string $query
	 * @return mixed
	 */
	function getKrzipCodeList($query)
	{
		$module_config = $this->getConfig();
		if($module_config->api_handler != 1)
		{
			return $this->setError('msg_invalid_request');
		}
		if(!isset($query))
		{
			$query = Context::get('query');
		}

		$query = trim(strval($query));
		if($query === '')
		{
			return $this->setError('msg_krzip_no_query');
		}

		$output = $this->getEpostapiSearch($query);
		/* XML Request에서는 Array 치환에 대한 문제로 이 함수를 제대로 사용할 수 없음 */
		$this->add('address_list', $output->get('address_list'));
		if(!$output->toBool())
		{
			return $output;
		}
	}

	/**
	 * @brief 우체국 우편번호 API 검색 결과 반환
	 * @param string $query
	 * @return object
	 */
	function getEpostapiSearch($query = '')
	{
		/**
		 * @brief 문자열 인코딩 변환
		 * @note 우체국 우편번호 API는 검색어를 EUC-KR로 넘겨주어야 함
		 */
		$encoding = strtoupper(mb_detect_encoding($query));
		if($encoding !== 'EUC-KR')
		{
			$query = iconv($encoding, 'EUC-KR', $query);
		}

		$module_config = $this->getConfig();
		$regkey = $module_config->epostapi_regkey;

		$fields = array(
			'target' => 'postRoad', /* 도로명 주소 */
			'regkey' => $regkey,
			'query' => $query
		);
		$headers = array(
			'accept-language' => 'ko'
		);
		$request_config = array(
			'ssl_verify_peer' => FALSE
		);

		$buff = FileHandler::getRemoteResource(
			self::$epostapi_host,
			NULL,
			30,
			'POST',
			'application/x-www-form-urlencoded',
			$headers,
			array(),
			$fields,
			$request_config
		);

		$oXmlParser = new XeXmlParser();
		$result = $oXmlParser->parse($buff);
		if($result->error)
		{
			$err_msg = trim($result->error->message->body);
			if(!$err_msg)
			{
				$err_code = intval(str_replace('ERR-', '', $result->error->error_code->body));
				switch($err_code)
				{
					case 1:
						$err_msg = 'msg_krzip_is_maintenance';
						break;
					case 2:
						$err_msg = 'msg_krzip_wrong_regkey';
						break;
					case 3:
						$err_msg = 'msg_krzip_no_result';
						break;
					default:
						$err_msg = 'msg_krzip_riddling_wrong';
						break;
				}
			}

			return $this->setError($err_msg);
		}
		if(!$result->post)
		{
			return $this->setError('msg_krzip_riddling_wrong');
		}

		$item_list = $result->post->itemlist->item;
		if(!is_array($item_list))
		{
			$item_list = array($item_list);
		}
		if(!$item_list)
		{
			return $this->setError('msg_krzip_no_result');
		}

		$addr_list = array();
		foreach($item_list as $key => $val)
		{
			$postcode = substr($val->postcd->body, 0, 3) . '-' . substr($val->postcd->body, 3, 3);
			$road_addr = $val->lnmaddress->body;
			$jibun_addr = $val->rnaddress->body;
			$addr_list[] = $this->getMigratedPostcode('(' . $postcode . ') (' . $jibun_addr . ') ' . $road_addr);
		}

		$output = new BaseObject();
		$output->add('address_list', $addr_list);

		return $output;
	}

	/**
	 * @brief HTML 입력 폼 반환
	 * @param string $column_name
	 * @param mixed $values
	 * @return string
	 */
	function getKrzipCodeSearchHtml($column_name, $values)
	{
		$template_config = $this->getConfig();
		$template_config->sequence_id = ++self::$sequence_id;
		$template_config->column_name = $column_name;
		$template_config->values = $this->getMigratedPostcode($values);
		Context::set('template_config', $template_config);

		$api_name = strval(self::$api_list[$template_config->api_handler]);
		$oTemplate = TemplateHandler::getInstance();
		$output = $oTemplate->compile($this->module_path . 'tpl', 'template.' . $api_name);

		return $output;
	}
}

/* End of file krzip.model.php */
/* Location: ./modules/krzip/krzip.model.php */
