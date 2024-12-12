<?php

class SMSTest extends \Codeception\Test\Unit
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
		$driver = Rhymix\Framework\SMS::getDefaultDriver();
		$this->assertInstanceOf('\\Rhymix\\Framework\\Drivers\\SMSInterface', $driver);

		$driver = Rhymix\Framework\Drivers\SMS\Dummy::getInstance(array());
		Rhymix\Framework\SMS::setDefaultDriver($driver);
		$this->assertEquals($driver, Rhymix\Framework\SMS::getDefaultDriver());
	}

	public function testGetSupportedDrivers()
	{
		$drivers = Rhymix\Framework\SMS::getSupportedDrivers();
		$this->assertTrue(isset($drivers['dummy']));
		$this->assertTrue(isset($drivers['coolsms']));
		$this->assertTrue(isset($drivers['iwinv']));
		$this->assertTrue(isset($drivers['solapi']));
		$this->assertTrue(isset($drivers['ppurio']));
		$this->assertEquals('Dummy', $drivers['dummy']['name']);
		$this->assertTrue(in_array('api_key', $drivers['coolsms']['required']));
		$this->assertTrue(in_array('api_url', $drivers['iwinv']['required']));
		$this->assertTrue(in_array('api_user', $drivers['ppurio']['required']));
		$this->assertTrue(in_array('api_key', $drivers['solapi']['required']));
		$this->assertTrue($drivers['coolsms']['api_spec']['mms_supported']);
		$this->assertTrue($drivers['coolsms']['api_spec']['delay_supported']);
		$this->assertTrue($drivers['solapi']['api_spec']['mms_supported']);
		$this->assertTrue($drivers['solapi']['api_spec']['delay_supported']);
	}

	public function testSenderAndRecipients()
	{
		config('sms.default_from', '010-9999-8888');
		$sms = new Rhymix\Framework\SMS;
		$this->assertEquals('01099998888', $sms->getFrom());

		config('sms.default_from', '');
		$sms = new Rhymix\Framework\SMS;
		$this->assertNull($sms->getFrom());

		$sms->setFrom('010-1234-5678');
		$this->assertEquals('01012345678', $sms->getFrom());
		$sms->setFrom('010+1234 x 5679');
		$this->assertEquals('01012345679', $sms->getFrom());

		$sms->addTo('010-0987-6543');
		$sms->addTo('010-0987-6542', 0);
		$sms->addTo('010-0987-6541', 82);
		$this->assertEquals(array('01009876543', '01009876542', '01009876541'), $sms->getRecipients());
		$this->assertEquals(array(
			(object)array('number' => '01009876543', 'country' => 0),
			(object)array('number' => '01009876542', 'country' => 0),
			(object)array('number' => '01009876541', 'country' => 82),
		), $sms->getRecipientsWithCountry());
		$this->assertEquals(array(
			0 => array('01009876543', '01009876542'),
			82 => array('01009876541'),
		), $sms->getRecipientsGroupedByCountry());
	}

	public function testSMSSubject()
	{
		$sms = new Rhymix\Framework\SMS;

		$sms->setSubject('Foobar!');
		$this->assertEquals('Foobar!', $sms->getSubject());
		$sms->setTitle('Foobarbazz?');
		$this->assertEquals('Foobarbazz?', $sms->getTitle());
	}

	public function testSMSContent()
	{
		$sms = new Rhymix\Framework\SMS;

		$sms->setBody('Hello world!');
		$this->assertEquals('Hello world!', $sms->getContent());

		$sms->setContent('Hello world! Foobar?');
		$this->assertEquals('Hello world! Foobar?', $sms->getBody());
	}

	public function testSMSAttach()
	{
		$sms = new Rhymix\Framework\SMS;

		$success = $sms->attach(\RX_BASEDIR . 'tests/_data/formatter/minify.source.css');
		$this->assertTrue($success);
		$success = $sms->attach(\RX_BASEDIR . 'tests/_data/formatter/minify.target.css');
		$this->assertTrue($success);
		$success = $sms->attach(\RX_BASEDIR . 'tests/_data/nonexistent.file.jpg');
		$this->assertFalse($success);

		$attachments = $sms->getAttachments();
		$this->assertEquals(2, count($attachments));
		$this->assertEquals('mms', $attachments[0]->type);
		$this->assertEquals(\RX_BASEDIR . 'tests/_data/formatter/minify.source.css', $attachments[0]->local_filename);
		$this->assertEquals('minify.source.css', $attachments[0]->display_filename);
		$this->assertEquals('minify.target.css', $attachments[1]->display_filename);
	}

	public function testSMSExtraVars()
	{
		$sms = new Rhymix\Framework\SMS;

		$sms->setExtraVar('foo', 'bar');
		$this->assertEquals('bar', $sms->getExtraVar('foo'));
		$this->assertNull($sms->getExtraVar('nonexistent!'));
		$sms->setExtraVar('baz', 'moo');
		$this->assertEquals(array('foo' => 'bar', 'baz' => 'moo'), $sms->getExtraVars());
		$sms->setExtraVars(array('rhymix' => 'test'));
		$this->assertEquals(array('rhymix' => 'test'), $sms->getExtraVars());
	}

	public function testSMSDelay()
	{
		$sms = new Rhymix\Framework\SMS;

		$delay_absolute = time() + 3600;
		$this->assertTrue($sms->setDelay($delay_absolute));
		$this->assertEquals($delay_absolute, $sms->getDelay());

		$delay_relative = 86400;
		$this->assertTrue($sms->setDelay($delay_relative));
		$this->assertGreaterThanOrEqual(time() + $delay_relative - 1, $sms->getDelay());
		$this->assertLessThanOrEqual(time() + $delay_relative + 1, $sms->getDelay());

		$delay_relative = 86400 * 3650;
		$this->assertFalse($sms->setDelay($delay_relative));
		$this->assertEquals(0, $sms->getDelay());
	}

	public function testSMSForceSMS()
	{
		$sms = new Rhymix\Framework\SMS;

		$this->assertFalse($sms->isForceSMS());
		$sms->forceSMS();
		$this->assertTrue($sms->isForceSMS());
		$sms->unforceSMS();
		$this->assertFalse($sms->isForceSMS());
	}

	public function testSMSSplitSMSAndLMS()
	{
		config('sms.allow_split.sms', true);
		config('sms.allow_split.lms', true);
		$sms = new Rhymix\Framework\SMS;
		$this->assertTrue($sms->isSplitSMSAllowed());
		$this->assertTrue($sms->isSplitLMSAllowed());

		config('sms.allow_split.sms', false);
		config('sms.allow_split.lms', false);
		$sms = new Rhymix\Framework\SMS;
		$this->assertFalse($sms->isSplitSMSAllowed());
		$this->assertFalse($sms->isSplitLMSAllowed());

		$sms->allowSplitSMS();
		$this->assertTrue($sms->isSplitSMSAllowed());
		$this->assertFalse($sms->isSplitLMSAllowed());
		$sms->disallowSplitSMS();
		$this->assertFalse($sms->isSplitSMSAllowed());

		$sms->allowSplitLMS();
		$this->assertTrue($sms->isSplitLMSAllowed());
		$this->assertFalse($sms->isSplitSMSAllowed());
		$sms->disallowSplitLMS();
		$this->assertFalse($sms->isSplitLMSAllowed());
	}

	public function testForceSender()
	{
		config('sms.default_from', '010-9999-8888');
		config('sms.default_force', true);
		$driver = Rhymix\Framework\Drivers\SMS\Dummy::getInstance(array());
		Rhymix\Framework\SMS::setDefaultDriver($driver);

		$driver->resetSentMessages();
		$sms = new Rhymix\Framework\SMS;
		$sms->setFrom('010-7777-6666');
		$sms->addTo('010-5555-4444');
		$sms->setContent('Hello World');
		$sms->send();

		$messages = $driver->getSentMessages();
		$this->assertEquals('01099998888', $messages[0]->from);

		config('sms.default_force', false);

		$driver->resetSentMessages();
		$sms = new Rhymix\Framework\SMS;
		$sms->setFrom('010-7777-6666');
		$sms->addTo('010-5555-4444');
		$sms->setContent('Hello World');
		$sms->send();

		$messages = $driver->getSentMessages();
		$this->assertEquals('01077776666', $messages[0]->from);
	}

	public function testSMSSendSMSAndLMS()
	{
		config('sms.default_force', false);
		config('sms.allow_split.sms', true);
		config('sms.allow_split.lms', true);

		$driver = Rhymix\Framework\Drivers\SMS\Dummy::getInstance(array());
		Rhymix\Framework\SMS::setDefaultDriver($driver);

		// Test sending a message with no recipients.
		$sms = new Rhymix\Framework\SMS;
		$this->assertFalse($sms->send());
		$this->assertEquals(1, count($sms->getErrors()));

		// Test SMS content splitting, grouping by country code, and forcing SMS to unsupported country code.
		$driver->resetSentMessages();
		$sms = new Rhymix\Framework\SMS;
		$sms->setFrom('010-1234-5678');
		$sms->addTo('010-0001-2345');
		$sms->addTo('010-0002-3456');
		$sms->addTo('010-0003-4567', 83);
		$sms->addTo('010-0004-5678', 83);
		$sms->setContent('이것은 다소 긴 메시지입니다. SMS에 들어가기에는 글자 수가 너무 많아요. 한 번에 발송하려면 LMS로 변환해야 합니다. 자동으로 변환되어야 정상입니다.');
		$this->assertTrue($sms->send());

		$messages = $driver->getSentMessages();
		$this->assertEquals(3, count($messages));
		$this->assertEquals(0, $messages[0]->country);
		$this->assertEquals(83, $messages[1]->country);
		$this->assertEquals(83, $messages[2]->country);
		$this->assertEquals('01012345678', $messages[0]->from);
		$this->assertEquals('01012345678', $messages[1]->from);
		$this->assertEquals(array('01000012345', '01000023456'), $messages[0]->to);
		$this->assertEquals(array('01000034567', '01000045678'), $messages[1]->to);
		$this->assertEquals(array('01000034567', '01000045678'), $messages[2]->to);
		$this->assertEquals('LMS', $messages[0]->type);
		$this->assertEquals('SMS', $messages[1]->type);
		$this->assertEquals('SMS', $messages[2]->type);
		$this->assertEquals('이것은 다소 긴 메시지입니다. SMS에 들어가기에는 글자 수가 너무 많아요. 한 번에 발송하려면 LMS로 변환해야 합니다. 자동으로 변환되어야 정상입니다.', $messages[0]->content);
		$this->assertEquals('이것은 다소 긴 메시지입니다. SMS에 들어가기에는 글자 수가 너무 많아요. 한 번에 발송하려면', $messages[1]->content);
		$this->assertEquals('LMS로 변환해야 합니다. 자동으로 변환되어야 정상입니다.', $messages[2]->content);

		// Test LMS content splitting.
		$driver->resetSentMessages();
		$sms = new Rhymix\Framework\SMS;
		$sms->setFrom('010-1234-5678');
		$sms->addTo('010-0000-1234');
		$sms->setContent(str_repeat('이것은 다소 긴 메시지입니다. SMS에 들어가기에는 글자 수가 너무 많아요. 한 번에 발송하려면 LMS로 변환해야 합니다. 자동으로 변환되어야 정상입니다.' . "\n", 50));
		$this->assertTrue($sms->send());

		$messages = $driver->getSentMessages();
		$this->assertEquals(4, count($messages));
		foreach ($messages as $message)
		{
			$this->assertEquals('LMS', $message->type);
		}

		// Test subject splitting.
		$driver->resetSentMessages();
		$sms = new Rhymix\Framework\SMS;
		$sms->setFrom('010-1234-5678');
		$sms->addTo('010-0000-1234');
		$sms->setSubject('이것은 제목입니다. 제목이 길기 때문에 내용으로 넘어갈 것입니다.');
		$sms->setContent('이것은 내용입니다.' . "\r\n" . '줄을 바꿔서 내용이 계속됩니다.');
		$sms->send();

		$messages = $driver->getSentMessages();
		$this->assertEquals(1, count($messages));
		$this->assertEquals('LMS', $messages[0]->type);
		$this->assertEquals('이것은 제목입니다. 제목이 길기 때문에', $messages[0]->subject);
		$this->assertEquals('내용으로 넘어갈 것입니다.' . "\n" . '이것은 내용입니다.' . "\n" . '줄을 바꿔서 내용이 계속됩니다.', $messages[0]->content);

		// Test subject splitting when forced to use SMS.
		$driver->resetSentMessages();
		$sms = new Rhymix\Framework\SMS;
		$sms->setFrom('010-1234-5678');
		$sms->addTo('010-0000-1234');
		$sms->setSubject('이것은 제목입니다. 제목이 길기 때문에 내용으로 넘어갈 것입니다.');
		$sms->setContent('이것은 내용입니다.');
		$sms->forceSMS();
		$sms->send();

		$messages = $driver->getSentMessages();
		$this->assertEquals(1, count($messages));
		$this->assertEquals('SMS', $messages[0]->type);
		$this->assertEquals('이것은 제목입니다. 제목이 길기 때문에 내용으로 넘어갈 것입니다.' . "\n" . '이것은 내용입니다.', $messages[0]->content);
	}

	public function testSMSSendMMS()
	{
		config('sms.default_force', false);
		config('sms.allow_split.sms', true);
		config('sms.allow_split.lms', true);

		$driver = Rhymix\Framework\Drivers\SMS\Dummy::getInstance(array());
		Rhymix\Framework\SMS::setDefaultDriver($driver);

		// Test MMS with a single attachment.
		$driver->resetSentMessages();
		$sms = new Rhymix\Framework\SMS;
		$sms->setFrom('010-1234-5678');
		$sms->addTo('010-0000-1234');
		$sms->setContent('MMS 내용입니다.');
		$sms->attach(\RX_BASEDIR . 'tests/_data/images/rhymix.png');
		$sms->send();

		$messages = $driver->getSentMessages();
		$this->assertEquals(1, count($messages));
		$this->assertEquals('MMS', $messages[0]->type);
		$this->assertEquals('MMS 내용입니다.', $messages[0]->content);
		$this->assertEquals(\RX_BASEDIR . 'tests/_data/images/rhymix.png', $messages[0]->image);

		// Test MMS with multiple attachments.
		$driver->resetSentMessages();
		$sms = new Rhymix\Framework\SMS;
		$sms->setFrom('010-1234-5678');
		$sms->addTo('010-0000-1234');
		$sms->setContent('MMS 내용입니다.');
		$sms->attach(\RX_BASEDIR . 'tests/_data/images/rhymix.png');
		copy(\RX_BASEDIR . 'tests/_data/images/rhymix.png', \RX_BASEDIR . 'tests/_output/rhymix-copy.png');
		$sms->attach(\RX_BASEDIR . 'tests/_output/rhymix-copy.png');
		unlink(\RX_BASEDIR . 'tests/_output/rhymix-copy.png');
		$sms->send();

		$messages = $driver->getSentMessages();
		$this->assertEquals(2, count($messages));
		$this->assertEquals($messages[0]->to, $messages[1]->to);
		$this->assertEquals('MMS', $messages[0]->type);
		$this->assertEquals('MMS', $messages[1]->type);
		$this->assertEquals('MMS 내용입니다.', $messages[0]->content);
		$this->assertEquals('MMS 내용입니다.', $messages[1]->content);
		$this->assertEquals(\RX_BASEDIR . 'tests/_data/images/rhymix.png', $messages[0]->image);
		$this->assertEquals(\RX_BASEDIR . 'tests/_output/rhymix-copy.png', $messages[1]->image);

		// Test MMS with multiple attachments and very long content (less than the number of attachments).
		$driver->resetSentMessages();
		$sms = new Rhymix\Framework\SMS;
		$sms->setFrom('010-1234-5678');
		$sms->addTo('010-0000-1234');
		$sms->setContent(str_repeat('MMS 테스트용입니다. ', 120));
		$sms->attach(\RX_BASEDIR . 'tests/_data/images/rhymix.png');
		copy(\RX_BASEDIR . 'tests/_data/images/rhymix.png', \RX_BASEDIR . 'tests/_output/rhymix-copy-1.png');
		copy(\RX_BASEDIR . 'tests/_data/images/rhymix.png', \RX_BASEDIR . 'tests/_output/rhymix-copy-2.png');
		$sms->attach(\RX_BASEDIR . 'tests/_output/rhymix-copy-1.png');
		$sms->attach(\RX_BASEDIR . 'tests/_output/rhymix-copy-2.png');
		unlink(\RX_BASEDIR . 'tests/_output/rhymix-copy-1.png');
		unlink(\RX_BASEDIR . 'tests/_output/rhymix-copy-2.png');
		$sms->send();

		$messages = $driver->getSentMessages();
		$this->assertEquals(3, count($messages));
		$this->assertEquals('MMS', $messages[0]->type);
		$this->assertEquals('MMS', $messages[1]->type);
		$this->assertEquals('MMS', $messages[2]->type);
		$this->assertEquals(trim(str_repeat('MMS 테스트용입니다. ', 100)), $messages[0]->content);
		$this->assertEquals(trim(str_repeat('MMS 테스트용입니다. ', 20)), $messages[1]->content);
		$this->assertEquals(trim(str_repeat('MMS 테스트용입니다. ', 20)), $messages[2]->content);
		$this->assertEquals(\RX_BASEDIR . 'tests/_data/images/rhymix.png', $messages[0]->image);
		$this->assertEquals(\RX_BASEDIR . 'tests/_output/rhymix-copy-1.png', $messages[1]->image);
		$this->assertEquals(\RX_BASEDIR . 'tests/_output/rhymix-copy-2.png', $messages[2]->image);

		// Test MMS with multiple attachments and very long content (more than the number of attachments).
		$driver->resetSentMessages();
		$sms = new Rhymix\Framework\SMS;
		$sms->setFrom('010-1234-5678');
		$sms->addTo('010-0000-1234');
		$sms->setContent(str_repeat('MMS 테스트용입니다. ', 403));
		$sms->attach(\RX_BASEDIR . 'tests/_data/images/rhymix.png');
		copy(\RX_BASEDIR . 'tests/_data/images/rhymix.png', \RX_BASEDIR . 'tests/_output/rhymix-copy-1.png');
		copy(\RX_BASEDIR . 'tests/_data/images/rhymix.png', \RX_BASEDIR . 'tests/_output/rhymix-copy-2.png');
		$sms->attach(\RX_BASEDIR . 'tests/_output/rhymix-copy-1.png');
		$sms->attach(\RX_BASEDIR . 'tests/_output/rhymix-copy-2.png');
		unlink(\RX_BASEDIR . 'tests/_output/rhymix-copy-1.png');
		unlink(\RX_BASEDIR . 'tests/_output/rhymix-copy-2.png');
		$sms->send();

		$messages = $driver->getSentMessages();
		$this->assertEquals(5, count($messages));
		$this->assertEquals('MMS', $messages[0]->type);
		$this->assertEquals('MMS', $messages[1]->type);
		$this->assertEquals('MMS', $messages[2]->type);
		$this->assertEquals('LMS', $messages[3]->type);
		$this->assertEquals('SMS', $messages[4]->type);
		$this->assertEquals(trim(str_repeat('MMS 테스트용입니다. ', 100)), $messages[0]->content);
		$this->assertEquals(trim(str_repeat('MMS 테스트용입니다. ', 100)), $messages[1]->content);
		$this->assertEquals(trim(str_repeat('MMS 테스트용입니다. ', 100)), $messages[2]->content);
		$this->assertEquals(trim(str_repeat('MMS 테스트용입니다. ', 100)), $messages[3]->content);
		$this->assertEquals(trim(str_repeat('MMS 테스트용입니다. ', 3)), $messages[4]->content);
		$this->assertEquals(\RX_BASEDIR . 'tests/_data/images/rhymix.png', $messages[0]->image);
		$this->assertEquals(\RX_BASEDIR . 'tests/_output/rhymix-copy-1.png', $messages[1]->image);
		$this->assertEquals(\RX_BASEDIR . 'tests/_output/rhymix-copy-2.png', $messages[2]->image);
		$this->assertNull($messages[3]->image ?? null);
		$this->assertNull($messages[4]->image ?? null);

		// Test MMS with no text content.
		$driver->resetSentMessages();
		$sms = new Rhymix\Framework\SMS;
		$sms->setFrom('010-1234-5678');
		$sms->addTo('010-0000-1234');
		$sms->attach(\RX_BASEDIR . 'tests/_data/images/rhymix.png');
		$sms->send();

		$messages = $driver->getSentMessages();
		$this->assertEquals(1, count($messages));
		$this->assertEquals('MMS', $messages[0]->type);
		$this->assertEquals('MMS', $messages[0]->content);
		$this->assertEquals(\RX_BASEDIR . 'tests/_data/images/rhymix.png', $messages[0]->image);
	}

	public function testSMSIsSent()
	{
		$sms = new Rhymix\Framework\SMS;
		$this->assertFalse($sms->isSent());
		$this->assertFalse($sms->send());
		$this->assertFalse($sms->isSent());

		$sms = new Rhymix\Framework\SMS;
		$sms->setFrom('010-1234-5678');
		$sms->addTo('010-0000-1234');
		$sms->setContent('Dummy Content');
		$this->assertTrue($sms->send());
		$this->assertTrue($sms->isSent());
	}

	public function testSMSCaller()
	{
		$sms = new Rhymix\Framework\SMS;
		$sms->send();
		$line = strval(__LINE__ - 1);

		$caller = $sms->getCaller();
		$this->assertRegexp('/^.+ line \d+$/', $caller);
		$this->assertTrue(starts_with(__FILE__, $caller));
		$this->assertEquals($line, substr($caller, -1 * strlen($line)));
	}

	public function testSMSError()
	{
		$sms = new Rhymix\Framework\SMS;
		$sms->addError('foobar');

		$errors = $sms->getErrors();
		$this->assertTrue(is_array($errors));
		$this->assertContains('foobar', $errors);
	}
}
