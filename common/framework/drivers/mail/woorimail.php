<?php

namespace Rhymix\Framework\Drivers\Mail;

/**
 * The Woorimail mail driver.
 */
class Woorimail extends Base implements \Rhymix\Framework\Drivers\MailInterface
{
	/**
	 * The API URL.
	 */
	protected static $_url = 'https://woorimail.com/index.php';
	
	/**
	 * Error codes and messages.
	 */
	protected static $_error_codes = array(
		'me_001' => '@ 없는 이메일 주소가 있습니다.',
		'me_002' => '이메일 주소가 존재하지 않습니다.',
		'me_003' => '닉네임이 존재하지 않습니다.',
		'me_004' => '등록일이 존재하지 않습니다.',
		'me_005' => '이메일과 닉네임 갯수가 다릅니다.',
		'me_006' => '닉네임과 등록일 갯수가 다릅니다.',
		'me_007' => '이메일과 등록일 갯수가 다릅니다.',
		'me_008' => '이메일 갯수가 2,000개가 넘습니다.',
		'me_009' => 'type이 api가 아닙니다.',
		'me_010' => '인증키가 없습니다.',	
		'me_011' => '인증키가 부정확합니다.',
		'me_012' => '포인트가 부족합니다.',
		'me_013' => '전용채널에 도메인이 등록되어 있지 않습니다.',
	);
	
	/**
	 * Get the list of configuration fields required by this mail driver.
	 * 
	 * @return array
	 */
	public static function getRequiredConfig()
	{
		return array('api_domain', 'api_token', 'api_type');
	}
	
	/**
	 * Get the list of API types supported by this mail driver.
	 * 
	 * @return array
	 */
	public static function getAPITypes()
	{
		return array('free', 'paid');
	}
	
	/**
	 * Get the SPF hint.
	 * 
	 * @return string
	 */
	public static function getSPFHint()
	{
		return 'include:woorimail.com';
	}
	
	/**
	 * Get the DKIM hint.
	 * 
	 * @return string
	 */
	public static function getDKIMHint()
	{
		return '';
	}
	
	/**
	 * Check if the current mail driver is supported on this server.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @return bool
	 */
	public static function isSupported()
	{
		return true;
	}
	
	/**
	 * Send a message.
	 * 
	 * This method returns true on success and false on failure.
	 * 
	 * @param object $message
	 * @return bool
	 */
	public function send(\Rhymix\Framework\Mail $message)
	{
		// Assemble the POST data.
		$data = array(
			'type' => 'api',
			'mid' => 'auth_woorimail',
			'act' => 'dispWwapimanagerMailApi',
			'title' => $message->getSubject(),
			'content' => $message->getBody(),
			'sender_email' => '',
			'sender_nickname' => '',
			'receiver_email' => array(),
			'receiver_nickname' => array(),
			'member_regdate' => array(),
			'domain' => $this->_config['api_domain'],
			'authkey' => $this->_config['api_token'],
			'wms_domain' => 'woorimail.com',
			'wms_nick' => 'NOREPLY',
			'callback' => '',
			'is_sendok' => 'W',
		);
		
		// Fill the sender info.
		$from = $message->message->getFrom();
		foreach($from as $email => $name)
		{
			$data['sender_email'] = $email;
			$data['sender_nickname'] = trim($name) ?: substr($email, 0, strpos($email, '@'));
			break;
		}
		if(isset($this->_config['api_type']) && $this->_config['api_type'] === 'paid')
		{
			$sender_email = explode('@', $data['sender_email']);
			if(count($sender_email) === 2)
			{
				$data['wms_nick'] = $sender_email[0];
				$data['wms_domain'] = $sender_email[1];
			}
		}
		if($replyTo = $message->message->getReplyTo())
		{
			if ($replyTo = key($replyTo))
			{
				$data['sender_email'] = $replyTo;
			}
		}
		
		// Fill the recipient info.
		if ($to = $message->message->getTo())
		{
			foreach($to as $email => $name)
			{
				$data['receiver_email'][] = $email;
				$data['receiver_nickname'][] = str_replace(',', '', trim($name) ?: substr($email, 0, strpos($email, '@')));
			}
		}
		if ($cc = $message->message->getCc())
		{
			foreach($cc as $email => $name)
			{
				$data['receiver_email'][] = $email;
				$data['receiver_nickname'][] = str_replace(',', '', trim($name) ?: substr($email, 0, strpos($email, '@')));
			}
		}
		if ($bcc = $message->message->getBcc())
		{
			foreach($bcc as $email => $name)
			{
				$data['receiver_email'][] = $email;
				$data['receiver_nickname'][] = str_replace(',', '', trim($name) ?: substr($email, 0, strpos($email, '@')));
			}
		}
		$data['member_regdate'] = implode(',', array_fill(0, count($data['receiver_email']), date('YmdHis')));
		$data['receiver_email'] = implode(',', $data['receiver_email']);
		$data['receiver_nickname'] = implode(',', $data['receiver_nickname']);
		
		// Define connection options.
		$headers = array(
			'Accept' => 'application/json, text/javascript, */*; q=0.1',
		);
		$options = array(
			'timeout' => 5,
			'useragent' => 'PHP',
		);
		
		// Send the API request.
		try
		{
			$request = \Requests::post(self::$_url, $headers, $data, $options);
			$result = @json_decode($request->body);
		}
		catch (\Requests_Exception $e)
		{
			$message->errors[] = 'Woorimail: ' . $e->getMessage();
			return false;
		}
		
		// Parse the result.
		if (!$result)
		{
			$message->errors[] = 'Woorimail: Connection error: ' . $request->body;
			return false;
		}
		elseif($result->result === 'OK')
		{
			return true;
		}
		else
		{
			if(isset($result->error_msg))
			{
				if(isset(self::$_error_codes[$result->error_msg]))
				{
					$result->error_msg .= ' ' . self::$_error_codes[$result->error_msg];
				}
				$message->errors[] = 'Woorimail: ' . $result->error_msg;
			}
			else
			{
				$message->errors[] = 'Woorimail: Connection error';
			}
			return false;
		}
	}
}
