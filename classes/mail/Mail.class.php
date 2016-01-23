<?php

/**
 * Mail class
 * 
 * This class was originally written for the Advanced Mailer module.
 * Advanced Mailer is licensed under GPLv2, but the author hereby relicenses
 * this class under the same license as the remainder of RhymiX.
 * All other parts of the Advanced Mailer module remain under GPLv2.
 * 
 * @author Kijin Sung <kijin@kijinsung.com>
 */
class Mail
{
	/**
	 * Properties for compatibility with XE Mail class
	 */
	public $content = '';
	public $content_type = 'html';
	public $attachments = array();
	public $cidAttachments = array();
	
	/**
	 * Properties used by Advanced Mailer
	 */
	public $error = null;
	public $caller = null;
	public $message = null;
	public static $transport = null;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->message = \Swift_Message::newInstance();
	}
	
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
		self::$transport = \Swift_SmtpTransport::newInstance($host, $port, $secure);
		self::$transport->setUsername($user);
		self::$transport->setPassword($pass);
		$local_domain = self::$transport->getLocalDomain();
		if (preg_match('/^\*\.(.+)$/', $local_domain, $matches))
		{
			self::$transport->setLocalDomain($matches[1]);
		}
	}
	
	/**
	 * Set additional parameters
	 */
	public function setAdditionalParams($additional_params)
	{
		// no-op
	}
	
	/**
	 * Set Sender (From:)
	 *
	 * @param string $name Sender name
	 * @param string $email Sender email address
	 * @return void
	 */
	public function setSender($name, $email)
	{
		try
		{
			$this->message->setFrom(array($email => $name));
		}
		catch (\Exception $e)
		{
			$this->errors[] = array($e->getMessage());
		}
	}
	
	/**
	 * Get Sender (From:)
	 *
	 * @return string
	 */
	public function getSender()
	{
		$from = $this->message->getFrom();
		foreach($from as $email => $name)
		{
			if($name === '')
			{
				return $email;
			}
			else
			{
				return $name . ' <' . $email . '>';
			}
		}
		return FALSE;
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
		try
		{
			$this->message->setTo(array($email => $name));
		}
		catch (\Exception $e)
		{
			$this->errors[] = array($e->getMessage());
		}
	}
	
	/**
	 * Get Recipient (To:)
	 *
	 * @return string
	 */
	public function getReceiptor()
	{
		$to = $this->message->getTo();
		foreach($to as $email => $name)
		{
			if($name === '')
			{
				return $email;
			}
			else
			{
				return $name . ' <' . $email . '>';
			}
		}
		return FALSE;
	}
	
	/**
	 * Set Subject
	 *
	 * @param string $subject The subject
	 * @return void
	 */
	public function setTitle($subject)
	{
		$this->message->setSubject(strval($subject));
	}
	
	/**
	 * Get Subject
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->message->getSubject();
	}
	
	/**
	 * Set BCC
	 *
	 * @param string $bcc
	 * @return void
	 */
	public function setBCC($bcc)
	{
		try
		{
			$this->message->setBcc(array($bcc));
		}
		catch (\Exception $e)
		{
			$this->errors[] = array($e->getMessage());
		}
	}
	
	/**
	 * Set ReplyTo
	 *
	 * @param string $replyTo
	 * @return void
	 */
	public function setReplyTo($replyTo)
	{
		try
		{
			$this->message->setReplyTo(array($replyTo));
		}
		catch (\Exception $e)
		{
			$this->errors[] = array($e->getMessage());
		}
	}
	
	/**
	 * Set Return Path
	 *
	 * @param string $returnPath
	 * @return void
	 */
	public function setReturnPath($returnPath)
	{
		try
		{
			$this->message->setReturnPath($returnPath);
		}
		catch (\Exception $e)
		{
			$this->errors[] = array($e->getMessage());
		}
	}
	
	/**
	 * Set Message ID
	 *
	 * @param string $messageId
	 * @return void
	 */
	public function setMessageID($messageId)
	{
		$this->message->getHeaders()->get('Message-ID')->setId($messageId);
	}
	
	/**
	 * Set references
	 *
	 * @param string $references
	 * @return void
	 */
	public function setReferences($references)
	{
		$headers = $this->message->getHeaders();
		$headers->addTextHeader('References', $references);
	}
	
	/**
	 * Set message content
	 *
	 * @param string $content Content
	 * @return void
	 */
	public function setContent($content)
	{
		$content = preg_replace_callback('/<img([^>]+)>/i', array($this, 'replaceResourceRealPath'), $content);
		$this->content = $content;
	}
	
	/**
	 * Set the type of message content (html or plain text)
	 * 
	 * @param string $mode The type
	 * @return void
	 */
	public function setContentType($type = 'html')
	{
		$this->content_type = $type === 'html' ? 'html' : '';
	}
	
	/**
	 * Get the Plain content of body message
	 *
	 * @return string
	 */
	public function getPlainContent()
	{
		return chunk_split(base64_encode(str_replace(array("<", ">", "&"), array("&lt;", "&gt;", "&amp;"), $this->content)));
	}
	
	/**
	 * Get the HTML content of body message
	 * 
	 * @return string
	 */
	public function getHTMLContent()
	{
		return chunk_split(base64_encode($this->content_type != 'html' ? nl2br($this->content) : $this->content));
	}
	
	/**
	 * Add file attachment
	 *
	 * @param string $filename File name to attach
	 * @param string $original_filename Real path of file to attach
	 * @return void
	 */
	public function addAttachment($filename, $original_filename)
	{
		$this->attachments[$original_filename] = $filename;
	}
	
	/**
	 * Add content attachment
	 *
	 * @param string $original_filename Real path of file to attach
	 * @param string $cid Content-CID
	 * @return void
	 */
	public function addCidAttachment($original_filename, $cid)
	{
		$this->cidAttachments[$cid] = $original_filename;
	}
	
	/**
	 * Replace resourse path of the files
	 *
	 * @see Mail::setContent()
	 * @param array $matches Match info.
	 * @return string
	 */
	public function replaceResourceRealPath($matches)
	{
		return preg_replace('/src=(["\']?)files/i', 'src=$1' . \Context::getRequestUri() . 'files', $matches[0]);
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
	 * Process the message before sending
	 * 
	 * @return void
	 */
	public function procAssembleMessage()
	{
		// Add all attachments
		foreach($this->attachments as $original_filename => $filename)
		{
			$attachment = \Swift_Attachment::fromPath($original_filename);
			$attachment->setFilename($filename);
			$this->message->attach($attachment);
		}
		
		// Add all CID attachments
		foreach($this->cidAttachments as $cid => $original_filename)
		{
			$embedded = \Swift_EmbeddedFile::fromPath($original_filename);
			$newcid = $this->message->embed($embedded);
			$this->content = str_replace(array("cid:$cid", $cid), $newcid, $this->content);
		}
		
		// Set content type
		$content_type = $this->content_type === 'html' ? 'text/html' : 'text/plain';
		$this->message->setBody($this->content, $content_type);
	}
	
	/**
	 * Send email
	 * 
	 * @return bool
	 */
	public function send()
	{
		try
		{
			$this->procAssembleMessage();
			if(!self::$transport)
			{
				self::$transport = \Swift_MailTransport::newInstance();
			}
			$mailer = \Swift_Mailer::newInstance(self::$transport);
			$result = $mailer->send($this->message, $this->errors);
			return (bool)$result;
		}
		catch(\Exception $e)		
		{
			$this->error = $e->getMessage();
			return false;
		}
	}
	
	/**
	 * Check if DNS of param is real or fake
	 * 
	 * @param string $email_address Email address to check
	 * @return bool
	 */
	public function checkMailMX($email_address)
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
	 * Check if param is a valid email or not
	 * 
	 * @param string $email_address Email address to check
	 * @return string
	 */
	public function isVaildMailAddress($email_address)
	{
		if(preg_match("/([a-z0-9\_\-\.]+)@([a-z0-9\_\-\.]+)/i", $email_address))
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
	function returnMIMEType($filename)
	{
		preg_match("|\.([a-z0-9]{2,4})$|i", $filename, $fileSuffix);
		switch(strtolower($fileSuffix[1]))
		{
			case "js" :
				return "application/x-javascript";
			case "json" :
				return "application/json";
			case "jpg" :
			case "jpeg" :
			case "jpe" :
				return "image/jpg";
			case "png" :
			case "gif" :
			case "bmp" :
			case "tiff" :
				return "image/" . strtolower($fileSuffix[1]);
			case "css" :
				return "text/css";
			case "xml" :
				return "application/xml";
			case "doc" :
			case "docx" :
				return "application/msword";
			case "xls" :
			case "xlt" :
			case "xlm" :
			case "xld" :
			case "xla" :
			case "xlc" :
			case "xlw" :
			case "xll" :
				return "application/vnd.ms-excel";
			case "ppt" :
			case "pps" :
				return "application/vnd.ms-powerpoint";
			case "rtf" :
				return "application/rtf";
			case "pdf" :
				return "application/pdf";
			case "html" :
			case "htm" :
			case "php" :
				return "text/html";
			case "txt" :
				return "text/plain";
			case "mpeg" :
			case "mpg" :
			case "mpe" :
				return "video/mpeg";
			case "mp3" :
				return "audio/mpeg3";
			case "wav" :
				return "audio/wav";
			case "aiff" :
			case "aif" :
				return "audio/aiff";
			case "avi" :
				return "video/msvideo";
			case "wmv" :
				return "video/x-ms-wmv";
			case "mov" :
				return "video/quicktime";
			case "zip" :
				return "application/zip";
			case "tar" :
				return "application/x-tar";
			case "swf" :
				return "application/x-shockwave-flash";
			default :
				if(function_exists("mime_content_type"))
				{
					$fileSuffix = mime_content_type($filename);
				}
				return "unknown/" . trim($fileSuffix[0], ".");
		}
	}

}
/* End of file Mail.class.php */
/* Location: ./classes/mail/Mail.class.php */
