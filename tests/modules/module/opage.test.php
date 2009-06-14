<?php
    class opageTest extends UnitTestCase {
        function testReplaceSrc() {
            $oController = &getController('opage');
            $this->assertNotNull($oController);

            $path = 'http://domain.com/test_path/opage.php';

            $content = 'src="images/foo.jpg"';
            $expected_result = 'src="http://domain.com/test_path/images/foo.jpg"';
            $result = $oController->replaceSrc($content, $path);
            $this->assertEqual($expected_result, $result);

            $content = 'src="/images/foo.jpg"';
            $expected_result = 'src="http://domain.com/images/foo.jpg"';
            $result = $oController->replaceSrc($content, $path);
            $this->assertEqual($expected_result, $result);

            $content = 'src="./images/foo.jpg"';
            $expected_result = 'src="http://domain.com/test_path/images/foo.jpg"';
            $result = $oController->replaceSrc($content, $path);
            $this->assertEqual($expected_result, $result);

            $content = 'src="../images/foo.jpg"';
            $expected_result = 'src="http://domain.com/test_path/../images/foo.jpg"';
            $result = $oController->replaceSrc($content, $path);
            $this->assertEqual($expected_result, $result);

            $content = 'url("./images/foo.jpg")';
            $expected_result = 'url("http://domain.com/test_path/images/foo.jpg")';
            $result = $oController->replaceSrc($content, $path);
            $this->assertEqual($expected_result, $result);

            /*
             * 프로토콜
             * http, https, ftp, telnet, mailto, mms
             */
            $content = 'href="https://domail.com/"';
            $expected_result = $content;
            $result = $oController->replaceSrc($content, $path);
            $this->assertEqual($expected_result, $result);

            $content = 'href="mailto:foo@domain.com"';
            $expected_result = $content;
            $result = $oController->replaceSrc($content, $path);
            $this->assertEqual($expected_result, $result);

            $content = 'href="mms://domain.com/bar.wmv"';
            $expected_result = $content;
            $result = $oController->replaceSrc($content, $path);
            $this->assertEqual($expected_result, $result);

            // + 포트번호
            $path = 'http://domain.com:123/test_path/opage.php';

            $content = 'src="./images/foo.jpg"';
            $expected_result = 'src="http://domain.com:123/test_path/images/foo.jpg"';
            $result = $oController->replaceSrc($content, $path);
            $this->assertEqual($expected_result, $result);

        }
    }
?>