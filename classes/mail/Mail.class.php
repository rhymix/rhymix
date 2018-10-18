<?php

/**
 * Mail class for XE Compatibility
 * 
 * @author Kijin Sung <kijin@kijinsung.com>
 */
class Mail extends Rhymix\Framework\Mail
{
	/**
	 * Set parameters for using Gmail
	 *
	 * @param string $account_name Email address
	 * @param string $account_passwd Email password
	 * @return void
	 */
	public static function useGmailAccount($account_name, $account_passwd)
	{
		self::useSMTP(null, 'smtp.gmail.com', $account_name, $account_passwd, 'ssl', 465);
	}
	
	/**
	 * Set parameters for using SMTP protocol
	 *
	 * @param bool $auth SMTP authentication
	 * @param string $host SMTP host address
	 * @param string $user SMTP user id
	 * @param string $pass STMP user password
	 * @param string $secure method ('ssl','tls')
	 * @param int $port STMP port
	 *
	 * @return bool TRUE if SMTP is set correct, otherwise return FALSE
	 */
	public static function useSMTP($auth = null, $host = null, $user = null, $pass = null, $secure = null, $port = 25)
	{
		self::setDefaultDriver(Rhymix\Framework\Drivers\Mail\SMTP::getInstance(array(
			'smtp_host' => $host,
			'smtp_port' => $port,
			'smtp_security' => $secure,
			'smtp_user' => $user,
			'smtp_pass' => $pass,
		)));
	}
	
	/**
	 * Set additional parameters
	 */
	public function setAdditionalParams($additional_params)
	{
		// no-op
	}
	
	/**
	 * Set the sender (From:).
	 *
	 * @param string $name Sender name
	 * @param string $email Sender email address
	 * @return void
	 */
	public function setSender($name, $email)
	{
		$this->setFrom($email, $name ?: null);
	}
	
	/**
	 * Get the sender.
	 *
	 * @return string
	 */
	public function getSender()
	{
		return $this->getFrom() ?: false;
	}
	
	/**
	 * Set Recipient (To:)
	 *
	 * @param string $name Recipient name
	 * @param string $email Recipient email address
	 * @return void
	 */
	public function setReceiptor($name, $email)
	{
		$this->message->setTo(array());
		return $this->addTo($email, $name ?: null);
	}
	
	/**
	 * Get Recipient (To:)
	 *
	 * @return string
	 */
	public function getReceiptor()
	{
		$list = $this->getRecipients();
		return $list ? array_first($list) : false;
	}
	
	/**
	 * Set BCC
	 *
	 * @param string $bcc
	 * @return void
	 */
	public function setBCC($bcc)
	{
		$this->message->setBcc(array());
		return $this->addBcc($bcc);
	}
	
	/**
	 * Get the Plain content of body message
	 *
	 * @return string
	 */
	public function getPlainContent()
	{
		return chunk_split(base64_encode(htmlspecialchars($this->message->getBody())));
	}
	
	/**
	 * Get the HTML content of body message
	 * 
	 * @return string
	 */
	public function getHTMLContent()
	{
		return chunk_split(base64_encode($this->content_type != 'text/html' ? nl2br($this->message->getBody()) : $this->message->getBody()));
	}
	
	/**
	 * Add file attachment
	 *
	 * @param string $original_filename Real path of file to attach
	 * @param string $filename File name to attach
	 * @return void
	 */
	public function addAttachment($original_filename, $filename)
	{
		return $this->attach($original_filename, $filename);
	}
	
	/**
	 * Add content attachment
	 *
	 * @param string $original_filename Real path of file to attach
	 * @param string $cid Content-CID
	 * @return void
	 */
	public function addCidAttachment($original_filename, $cid = null)
	{
		return $this->embed($original_filename, $cid);
	}
	
	/**
	 * Process the images from attachments
	 *
	 * @return void
	 */
	public function procAttachments()
	{
		// no-op
	}
	
	/**
	 * Process the images from body content. This functions is used if Mailer is set as mail not as SMTP
	 * 
	 * @return void
	 */
	public function procCidAttachments()
	{
		// no-op
	}
	
	/**
	 * Check if DNS of param is real or fake
	 * 
	 * @param string $email_address Email address to check
	 * @return bool
	 */
	public static function checkMailMX($email_address)
	{
		if(!self::isVaildMailAddress($email_address))
		{
			return FALSE;
		}
		list($user, $host) = explode("@", $email_address);
		if(function_exists('checkdnsrr'))
		{
			if(checkdnsrr($host, "MX") || checkdnsrr($host, "A"))
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		return TRUE;
	}
	
	/**
	 * Check if this class supports Advanced Mailer features.
	 * 
	 * @return bool
	 */
	public static function isAdvancedMailer()
	{
		return true;
	}
	
	/**
	 * Check if param is a valid email or not
	 * 
	 * @param string $email_address Email address to check
	 * @return string
	 */
	public static function isVaildMailAddress($email_address)
	{
		$validator = new \Egulias\EmailValidator\EmailValidator;
		$rfc = new \Egulias\EmailValidator\Validation\RFCValidation;
		if($validator->isValid($email_address, $rfc))
		{
			return $email_address;
		}
		else
		{
			return '';
		}
	}

	/**
	 * Gets the MIME type of param
	 *
	 * @param string $filename filename
	 * @return string MIME type of ext
	 */
	public static function returnMIMEType($filename)
	{
		return Rhymix\Framework\MIME::getTypeByFilename($filename);
	}

}
/* End of file Mail.class.php */
/* Location: ./classes/mail/Mail.class.php */
