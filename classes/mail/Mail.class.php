<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

require_once _XE_PATH_ . "libs/phpmailer/phpmailer.php";

/**
 * Mailing class for XpressEngine
 *
 * @author NAVER (developers@xpressengine.com)
 */
class Mail extends PHPMailer
{

	/**
	 * Sender name
	 * @var string
	 */
	var $sender_name = '';

	/**
	 * Sender email address
	 * @var string
	 */
	var $sender_email = '';

	/**
	 * Receiptor name
	 * @var string
	 */
	var $receiptor_name = '';

	/**
	 * Receiptor email address
	 * @var string
	 */
	var $receiptor_email = '';

	/**
	 * Title of email
	 * @var string
	 */
	var $title = '';

	/**
	 * Content of email
	 * @var string
	 */
	var $content = '';

	/**
	 * Content type
	 * @var string
	 */
	var $content_type = 'html';

	/**
	 * Message id
	 * @var string
	 */
	var $messageId = NULL;

	/**
	 * Reply to
	 * @var string
	 */
	var $replyTo = NULL;

	/**
	 * BCC (Blind carbon copy)
	 * @var string
	 */
	var $bcc = NULL;

	/**
	 * Attachments
	 * @var array
	 */
	var $attachments = array();

	/**
	 * Content attachements
	 * @var array
	 */
	var $cidAttachments = array();

	/**
	 * ???
	 * @var ???
	 */
	var $mainMailPart = NULL;

	/**
	 * Raw body
	 * @var string
	 */
	var $body = '';

	/**
	 * Raw header
	 * @var string
	 */
	var $header = '';

	/**
	 * End of line
	 * @var string
	 */
	var $eol = '';

	/**
	 * Reference
	 * @var string
	 */
	var $references = '';

	/**
	 * Additional parameters
	 * @var string
	 */
	var $additional_params = NULL;

	/**
	 * Whether use or not use stmp
	 * @var bool
	 */
	var $use_smtp = FALSE;

	/**
	 * Constructor function
	 *
	 * @return void
	 */
	function Mail()
	{

	}

	/**
	 * Set parameters for using Gmail
	 *
	 * @param string $account_name Password
	 * @param string $account_passwd Secure method ('ssl','tls')
	 * @return void
	 */
	function useGmailAccount($account_name, $account_passwd)
	{
		$this->SMTPAuth = TRUE;
		$this->SMTPSecure = "tls";
		$this->Host = 'smtp.gmail.com';
		$this->Port = '587';
		if($this->isVaildMailAddress($account_name))
		{
			$this->Username = $account_name;
		}
		else
		{
			$this->Username = $account_name . '@gmail.com';
		}
		$this->Password = $account_passwd;
		$this->IsSMTP();
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
	function useSMTP($auth = NULL, $host = NULL, $user = NULL, $pass = NULL, $secure = NULL, $port = 25)
	{
		$this->SMTPAuth = $auth;
		$this->Host = $host;
		$this->Username = $user;
		$this->Password = $pass;
		$this->Port = $port;

		if($secure == 'ssl' || $secure == 'tls')
		{
			$this->SMTPSecure = $secure;
		}

		if(($this->SMTPAuth !== NULL && $this->Host !== NULL && $this->Username !== NULL && $this->Password !== NULL) || ($this->SMTPAuth === NULL && $this->Host !== NULL))
		{
			$this->IsSMTP();
			$this->AltBody = "To view the message, please use an HTML compatible email viewer!";
			return TRUE;
		}
		else
		{
			$this->IsMail();
			return FALSE;
		}
	}

	/**
	 * Set additional parameters
	 *
	 * @param string $additional_params Additional parameters
	 * @return void
	 */
	function setAdditionalParams($additional_params)
	{
		$this->additional_params = $additional_params;
	}

	/**
	 * Add file attachment
	 *
	 * @param string $filename File name to attach
	 * @param string $orgfilename Real path of file to attach
	 * @return void
	 */
	function addAttachment($filename, $orgfilename)
	{
		$this->attachments[$orgfilename] = $filename;
	}

	/**
	 * Add content attachment
	 *
	 * @param string $filename Real path of file to attach
	 * @param string $cid Content-CID
	 * @return void
	 */
	function addCidAttachment($filename, $cid)
	{
		$this->cidAttachments[$cid] = $filename;
	}

	/**
	 * Set Sender (From:)
	 *
	 * @param string $name Sender name
	 * @param string $email Sender email address
	 * @return void
	 */
	function setSender($name, $email)
	{
		if($this->Mailer == "mail")
		{
			$this->sender_name = $name;
			$this->sender_email = $email;
		}
		else
		{
			$this->SetFrom($email, $name);
		}
	}

	/**
	 * Get Sender (From:)
	 *
	 * @return string
	 */
	function getSender()
	{
		if(!stristr(PHP_OS, 'win') && $this->sender_name)
		{
			return sprintf("%s <%s>", '=?utf-8?b?' . base64_encode($this->sender_name) . '?=', $this->sender_email);
		}
		return $this->sender_email;
	}

	/**
	 * Set Receiptor (TO:)
	 *
	 * @param string $name Receiptor name
	 * @param string $email Receiptor email address
	 * @return void
	 */
	function setReceiptor($name, $email)
	{
		if($this->Mailer == "mail")
		{
			$this->receiptor_name = $name;
			$this->receiptor_email = $email;
		}
		else
		{
			$this->AddAddress($email, $name);
		}
	}

	/**
	 * Get Receiptor (TO:)
	 *
	 * @return string
	 */
	function getReceiptor()
	{
		if(!stristr(PHP_OS, 'win') && $this->receiptor_name && $this->receiptor_name != $this->receiptor_email)
		{
			return sprintf("%s <%s>", '=?utf-8?b?' . base64_encode($this->receiptor_name) . '?=', $this->receiptor_email);
		}
		return $this->receiptor_email;
	}

	/**
	 * Set Email's Title
	 *
	 * @param string $title Title to set
	 * @return void
	 */
	function setTitle($title)
	{
		if($this->Mailer == "mail")
		{
			$this->title = $title;
		}
		else
		{
			$this->Subject = $title;
		}
	}

	/**
	 * Get Email's Title
	 *
	 * @return string
	 */
	function getTitle()
	{
		return '=?utf-8?b?' . base64_encode($this->title) . '?=';
	}

	/**
	 * Set BCC
	 *
	 * @param string $bcc
	 * @return void
	 */
	function setBCC($bcc)
	{
		if($this->Mailer == "mail")
		{
			$this->bcc = $bcc;
		}
		else
		{
			$this->AddBCC($bcc);
		}
	}

	/**
	 * Set Message ID
	 *
	 * @param string $messageId
	 * @return void
	 */
	function setMessageID($messageId)
	{
		$this->messageId = $messageId;
	}

	/**
	 * Set references
	 *
	 * @param string $references
	 * @return void
	 */
	function setReferences($references)
	{
		$this->references = $references;
	}

	/**
	 * Set ReplyTo param
	 *
	 * @param string $replyTo
	 * @return void
	 */
	function setReplyTo($replyTo)
	{
		if($this->Mailer == "mail")
		{
			$this->replyTo = $replyTo;
		}
		else
		{
			$this->AddReplyTo($replyTo);
		}
	}

	/**
	 * Set message content
	 *
	 * @param string $content Content
	 * @return void
	 */
	function setContent($content)
	{
		$content = preg_replace_callback('/<img([^>]+)>/i', array($this, 'replaceResourceRealPath'), $content);
		if($this->Mailer == "mail")
		{
			$this->content = $content;
		}
		else
		{
			$this->MsgHTML($content);
		}
	}

	/**
	 * Replace resourse path of the files
	 *
	 * @see Mail::setContent()
	 * @param array $matches Match info.
	 * @return string
	 */
	function replaceResourceRealPath($matches)
	{
		return preg_replace('/src=(["\']?)files/i', 'src=$1' . Context::getRequestUri() . 'files', $matches[0]);
	}

	/**
	 * Get the Plain content of body message
	 *
	 * @return string
	 */
	function getPlainContent()
	{
		return chunk_split(base64_encode(str_replace(array("<", ">", "&"), array("&lt;", "&gt;", "&amp;"), $this->content)));
	}

	/**
	 * Get the HTML content of body message
	 *
	 * @return string
	 */
	function getHTMLContent()
	{
		return chunk_split(base64_encode($this->content_type != 'html' ? nl2br($this->content) : $this->content));
	}

	/**
	 * Set the type of body's content
	 *
	 * @param string $mode
	 * @return void
	 */
	function setContentType($mode = 'html')
	{
		$this->content_type = $mode == 'html' ? 'html' : '';
	}

	/**
	 * Process the images from attachments
	 *
	 * @return void
	 */
	function procAttachments()
	{
		if($this->Mailer == "mail")
		{
			if(count($this->attachments) > 0)
			{
				$this->body = $this->header . $this->body;
				$boundary = '----==' . uniqid(rand(), TRUE);
				$this->header = "Content-Type: multipart/mixed;" . $this->eol . "\tboundary=\"" . $boundary . "\"" . $this->eol . $this->eol;
				$this->body = "--" . $boundary . $this->eol . $this->body . $this->eol . $this->eol;
				$res = array();
				$res[] = $this->body;
				foreach($this->attachments as $filename => $attachment)
				{
					$type = $this->returnMIMEType($filename);
					$file_handler = new FileHandler();
					$file_str = $file_handler->readFile($attachment);
					$chunks = chunk_split(base64_encode($file_str));
					$tempBody = sprintf(
							"--" . $boundary . $this->eol .
							"Content-Type: %s;" . $this->eol .
							"\tname=\"%s\"" . $this->eol .
							"Content-Transfer-Encoding: base64" . $this->eol .
							"Content-Description: %s" . $this->eol .
							"Content-Disposition: attachment;" . $this->eol .
							"\tfilename=\"%s\"" . $this->eol . $this->eol .
							"%s" . $this->eol . $this->eol, $type, $filename, $filename, $filename, $chunks);
					$res[] = $tempBody;
				}
				$this->body = implode("", $res);
				$this->body .= "--" . $boundary . "--";
			}
		}
		else
		{
			if(count($this->attachments) > 0)
			{
				foreach($this->attachments as $filename => $attachment)
				{
					parent::AddAttachment($attachment);
				}
			}
		}
	}

	/**
	 * Process the images from body content. This functions is used if Mailer is set as mail not as SMTP
	 *
	 * @return void
	 */
	function procCidAttachments()
	{
		if(count($this->cidAttachments) > 0)
		{
			$this->body = $this->header . $this->body;
			$boundary = '----==' . uniqid(rand(), TRUE);
			$this->header = "Content-Type: multipart/relative;" . $this->eol . "\ttype=\"multipart/alternative\";" . $this->eol . "\tboundary=\"" . $boundary . "\"" . $this->eol . $this->eol;
			$this->body = "--" . $boundary . $this->eol . $this->body . $this->eol . $this->eol;
			$res = array();
			$res[] = $this->body;
			foreach($this->cidAttachments as $cid => $attachment)
			{
				$filename = basename($attachment);
				$type = $this->returnMIMEType(FileHandler::getRealPath($attachment));
				$file_str = FileHandler::readFile($attachment);
				$chunks = chunk_split(base64_encode($file_str));
				$tempBody = sprintf(
						"--" . $boundary . $this->eol .
						"Content-Type: %s;" . $this->eol .
						"\tname=\"%s\"" . $this->eol .
						"Content-Transfer-Encoding: base64" . $this->eol .
						"Content-ID: <%s>" . $this->eol .
						"Content-Description: %s" . $this->eol .
						"Content-Location: %s" . $this->eol . $this->eol .
						"%s" . $this->eol . $this->eol, $type, $filename, $cid, $filename, $filename, $chunks);
				$res[] = $tempBody;
			}
			$this->body = implode("", $res);
			$this->body .= "--" . $boundary . "--";
		}
	}

	/**
	 * Send email
	 *
	 * @return bool TRUE in case of success, FALSE if sending fails
	 */
	function send()
	{
		if($this->Mailer == "mail")
		{
			$boundary = '----==' . uniqid(rand(), TRUE);
			$this->eol = $GLOBALS['_qmail_compatibility'] == "Y" ? "\n" : "\r\n";
			$this->header = "Content-Type: multipart/alternative;" . $this->eol . "\tboundary=\"" . $boundary . "\"" . $this->eol . $this->eol;
			$this->body = sprintf(
					"--%s" . $this->eol .
					"Content-Type: text/plain; charset=utf-8; format=flowed" . $this->eol .
					"Content-Transfer-Encoding: base64" . $this->eol .
					"Content-Disposition: inline" . $this->eol . $this->eol .
					"%s" .
					"--%s" . $this->eol .
					"Content-Type: text/html; charset=utf-8" . $this->eol .
					"Content-Transfer-Encoding: base64" . $this->eol .
					"Content-Disposition: inline" . $this->eol . $this->eol .
					"%s" .
					"--%s--" .
					"", $boundary, $this->getPlainContent(), $boundary, $this->getHTMLContent(), $boundary
			);
			$this->procCidAttachments();
			$this->procAttachments();
			$headers = sprintf(
					"From: %s" . $this->eol .
					"%s" .
					"%s" .
					"%s" .
					"%s" .
					"MIME-Version: 1.0" . $this->eol . "", $this->getSender(), $this->messageId ? ("Message-ID: <" . $this->messageId . ">" . $this->eol) : "", $this->replyTo ? ("Reply-To: <" . $this->replyTo . ">" . $this->eol) : "", $this->bcc ? ("Bcc: " . $this->bcc . $this->eol) : "", $this->references ? ("References: <" . $this->references . ">" . $this->eol . "In-Reply-To: <" . $this->references . ">" . $this->eol) : ""
			);
			$headers .= $this->header;
			if($this->additional_params)
			{
				return mail($this->getReceiptor(), $this->getTitle(), $this->body, $headers, $this->additional_params);
			}
			return mail($this->getReceiptor(), $this->getTitle(), $this->body, $headers);
		}
		else
		{
			$this->procAttachments();
			return parent::Send();
		}
	}

	/**
	 * Check if DNS of param is real or fake
	 *
	 * @param string $email_address Email address
	 * @return boolean TRUE if param is valid DNS otherwise FALSE
	 */
	function checkMailMX($email_address)
	{
		if(!Mail::isVaildMailAddress($email_address))
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
	 * @param string $email_address Email address
	 * @return string email address if param is valid email address otherwise blank string
	 */
	function isVaildMailAddress($email_address)
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
