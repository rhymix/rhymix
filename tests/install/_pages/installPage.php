<?php

class installPage
{
    // include url of current page
    public static $URL = '';

    /**
     * Declare UI map for this page here. CSS or XPath allowed.
     * public static $usernameField = '#username';
     * public static $formSubmitButton = "#mainForm input[type=submit]";
     */

    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: EditPage::route('/123-post');
     */
     public static function route($param)
     {
        return static::$URL.$param;
     }

    /**
     * @var InstallTester;
     */
    protected $installTester;

    public function __construct(InstallTester $I)
    {
        $this->installTester = $I;
    }

    /**
     * @return installPage
     */
    public static function of(InstallTester $I)
    {
        return new static($I);
    }
}