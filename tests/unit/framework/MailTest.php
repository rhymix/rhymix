<?php

class MailTest extends \Codeception\Test\Unit
{
	protected $_prev_queue_config;

	public function _before()
	{
		$this->_prev_queue_config = config('queue');
		config('queue.enabled', false);
	}

	public function _after()
	{
		config('queue', $this->_prev_queue_config);
	}

	public function testGetSetDefaultDriver()
	{
		$driver = Rhymix\Framework\Mail::getDefaultDriver();
		$this->assertInstanceOf('\\Rhymix\\Framework\\Drivers\\MailInterface', $driver);

		$driver = Rhymix\Framework\Drivers\Mail\Dummy::getInstance(array());
		Rhymix\Framework\Mail::setDefaultDriver($driver);
		$this->assertEquals($driver, Rhymix\Framework\Mail::getDefaultDriver());
	}

	public function testGetSupportedDrivers()
	{
		$drivers = Rhymix\Framework\Mail::getSupportedDrivers();
		$this->assertTrue(isset($drivers['dummy']));
		$this->assertTrue(isset($drivers['mailfunction']));
		$this->assertTrue(isset($drivers['smtp']));
		$this->assertEquals('SMTP', $drivers['smtp']['name']);
		$this->assertEquals(array('api_token'), $drivers['sparkpost']['required']);
		$this->assertNotEmpty($drivers['woorimail']['spf_hint']);
	}

	public function testSenderAndRecipients()
	{
		$mail = new Rhymix\Framework\Mail;

		$this->assertNull($mail->getFrom());
		$mail->setFrom('devops@rhymix.org', 'Rhymix Developers');
		$this->assertEquals('Rhymix Developers <devops@rhymix.org>', $mail->getFrom());

		$this->assertEquals(null, $mail->message->getTo());
		$mail->addTo('whoever@rhymix.org', 'Name');
		$this->assertEquals(array('whoever@rhymix.org' => 'Name'), $mail->message->getTo());

		$this->assertEquals(null, $mail->message->getCc());
		$mail->addCc('whatever@rhymix.org', 'Nick');
		$this->assertEquals(array('whatever@rhymix.org' => 'Nick'), $mail->message->getCc());

		$this->assertEquals(null, $mail->message->getBcc());
		$mail->addBcc('wherever@rhymix.org', 'User');
		$this->assertEquals(array('wherever@rhymix.org' => 'User'), $mail->message->getBcc());

		$this->assertEquals(null, $mail->message->getReplyTo());
		$mail->setReplyTo('replyto@rhymix.org');
		$this->assertEquals(array('replyto@rhymix.org' => ''), $mail->message->getReplyTo());

		$recipients = $mail->getRecipients();
		$this->assertEquals(3, count($recipients));
		$this->assertContains('Name <whoever@rhymix.org>', $recipients);
		$this->assertContains('Nick <whatever@rhymix.org>', $recipients);
		$this->assertContains('User <wherever@rhymix.org>', $recipients);
	}

	public function testMiscHeaders()
	{
		$mail = new Rhymix\Framework\Mail;

		$mail->setReturnPath('envelope@rhymix.org');
		$this->assertEquals('envelope@rhymix.org', $mail->message->getReturnPath());

		$mail->setMessageID('some.random.string@rhymix.org');
		$this->assertEquals('some.random.string@rhymix.org', $mail->message->getId());

		$mail->setInReplyTo('<previous.message@rhymix.org>');
		$this->assertEquals('<previous.message@rhymix.org>', $mail->message->getHeaders()->get('In-Reply-To')->getValue());

		$mail->setReferences('<thread-1@rhymix.org>, <thread-2@rhymix.org>, <thread-3@rhymix.org>');
		$this->assertEquals('<thread-1@rhymix.org>, <thread-2@rhymix.org>, <thread-3@rhymix.org>', $mail->message->getHeaders()->get('References')->getValue());
	}

	public function testMailSubject()
	{
		$mail = new Rhymix\Framework\Mail;

		$mail->setSubject('Foobar!');
		$this->assertEquals('Foobar!', $mail->getSubject());
		$mail->setTitle('Foobarbazz?');
		$this->assertEquals('Foobarbazz?', $mail->getTitle());
	}

	public function testMailBody()
	{
		$baseurl = '/' . basename(dirname(dirname(dirname(__DIR__)))) . '/';
		$mail = new Rhymix\Framework\Mail;

		$mail->setBody('<p>Hello world!</p>', 'text/html');
		$this->assertEquals('<p>Hello world!</p>', $mail->getBody());
		$this->assertEquals('text/html', $mail->getContentType());

		$mail->setContent('<p>Hello world! Foobar?</p>', 'text/plain');
		$this->assertEquals('<p>Hello world! Foobar?</p>', $mail->getBody());
		$this->assertEquals('text/plain', $mail->getContentType());

		$mail->setBody('<p>Hello foobar...</p>', 'invalid value');
		$this->assertEquals('<p>Hello foobar...</p>', $mail->getContent());
		$this->assertEquals('text/plain', $mail->getContentType());

		$mail->setBody('<p><img src="files/attach/foobar.jpg" alt="TEST" /></p>', 'text/html');
		$this->assertEquals('<p><img src="https://www.rhymix.org' . $baseurl . 'files/attach/foobar.jpg" alt="TEST" /></p>', $mail->getBody());
		$mail->setBody('<p><img src="./files/attach/foobar.jpg" alt="TEST" /></p>', 'text/html');
		$this->assertEquals('<p><img src="https://www.rhymix.org' . $baseurl . 'files/attach/foobar.jpg" alt="TEST" /></p>', $mail->getBody());
		$mail->setBody('<p><img src="' . $baseurl . 'files/attach/foobar.jpg" alt="TEST" /></p>', 'text/html');
		$this->assertEquals('<p><img src="https://www.rhymix.org' . $baseurl . 'files/attach/foobar.jpg" alt="TEST" /></p>', $mail->getBody());
		$mail->setBody('<p><img src="./files/attach/foobar.jpg" alt="TEST" /></p>', 'text/plain');
		$this->assertEquals('<p><img src="./files/attach/foobar.jpg" alt="TEST" /></p>', $mail->getBody());

		$mail->setContentType('html');
		$this->assertEquals('text/html', $mail->getContentType());
		$mail->setContentType('invalid');
		$this->assertEquals('text/plain', $mail->getContentType());
	}

	public function testMailAttach()
	{
		$mail = new Rhymix\Framework\Mail;

		$success = $mail->attach(\RX_BASEDIR . 'tests/_data/formatter/minify.source.css');
		$this->assertTrue($success);
		$success = $mail->attach(\RX_BASEDIR . 'tests/_data/formatter/minify.target.css', 'target.css');
		$this->assertTrue($success);
		$success = $mail->attach(\RX_BASEDIR . 'tests/_data/nonexistent.file.jpg');
		$this->assertFalse($success);

		$attachments = $mail->getAttachments();
		$this->assertEquals(2, count($attachments));
		$this->assertEquals('attach', $attachments[0]->type);
		$this->assertEquals(\RX_BASEDIR . 'tests/_data/formatter/minify.source.css', $attachments[0]->local_filename);
		$this->assertEquals('minify.source.css', $attachments[0]->display_filename);
		$this->assertEquals('target.css', $attachments[1]->display_filename);
	}

	public function testMailEmbed()
	{
		$mail = new Rhymix\Framework\Mail;

		$cid = $mail->embed(\RX_BASEDIR . 'tests/_data/formatter/minify.source.css', 'thisismyrandomcid@rhymix.org');
		$this->assertEquals('cid:thisismyrandomcid@rhymix.org', $cid);

		$cid = $mail->embed(\RX_BASEDIR . 'tests/_data/formatter/minify.target.css');
		$this->assertRegexp('/^cid:[0-9a-z]+@[^@]+$/i', $cid);
	}

	public function testMailClassCompatibility()
	{
		\Mail::useGmailAccount('devops@rhymix.org', 'password');
		$this->assertInstanceOf('\\Rhymix\\Framework\\Drivers\\Mail\\SMTP', \Mail::getDefaultDriver());

		\Mail::useSMTP(null, 'rhymix.org', 'devops@rhymix.org', 'password', 'tls', 587);
		$this->assertInstanceOf('\\Rhymix\\Framework\\Drivers\\Mail\\SMTP', \Mail::getDefaultDriver());

		$mail = new \Mail;

		$mail->setSender('Rhymix', 'devops@rhymix.org');
		$this->assertEquals('Rhymix <devops@rhymix.org>', $mail->getSender());

		$mail->setReceiptor('Recipient', 'whoever@rhymix.org');
		$this->assertEquals('Recipient <whoever@rhymix.org>', $mail->getReceiptor());
		$mail->setReceiptor('Another Recipient', 'whatever@rhymix.org');
		$this->assertEquals('Another Recipient <whatever@rhymix.org>', $mail->getReceiptor());
		$this->assertEquals(1, count($mail->message->getTo()));
		$this->assertEquals(null, $mail->message->getCc());

		$mail->setBcc('bcc-1@rhymix.org');
		$mail->setBcc('bcc-2@rhymix.org');
		$this->assertEquals(array('bcc-2@rhymix.org' => ''), $mail->message->getBcc());

		$content = '<p>Hello world!</p><p>This is a long message to test chunk splitting.</p><p>This feature is only available using the legacy Mail class.</p>';
		$mail->setBody($content);
		$this->assertEquals(chunk_split(base64_encode($content)), $mail->getHTMLContent());
		$this->assertEquals(chunk_split(base64_encode(htmlspecialchars($content))), $mail->getPlainContent());

		$mail->addAttachment(\RX_BASEDIR . 'tests/_data/formatter/minify.target.css', 'target.css');
		$cid = $mail->addCidAttachment(\RX_BASEDIR . 'tests/_data/formatter/minify.target.css', 'thisismyrandomcid@rhymix.org');
		$this->assertEquals('cid:thisismyrandomcid@rhymix.org', $cid);

		$attachments = $mail->getAttachments();
		$this->assertEquals(2, count($attachments));
		$this->assertEquals('attach', $attachments[0]->type);
		$this->assertEquals('target.css', $attachments[0]->display_filename);
		$this->assertEquals('embed', $attachments[1]->type);
		$this->assertEquals('cid:thisismyrandomcid@rhymix.org', $attachments[1]->cid);
	}

	public function testEmailAddressValidator()
	{
		$this->assertEquals('devops@rhymix.org', Mail::isVaildMailAddress('devops@rhymix.org'));
		$this->assertEquals('some+thing@gmail.com', Mail::isVaildMailAddress('some+thing@gmail.com'));
		$this->assertEquals('', Mail::isVaildMailAddress('weird@localhost'));
		$this->assertEquals('weird@localhost', Mail::isVaildMailAddress('weird@localhost', false));
		$this->assertEquals('', Mail::isVaildMailAddress('invalid@'));
		$this->assertEquals('', Mail::isVaildMailAddress('invalid@', false));
	}
}
