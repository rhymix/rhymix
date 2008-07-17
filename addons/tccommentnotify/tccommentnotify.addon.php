<?php
    if(!defined("__ZBXE__")) exit();

    if(Context::getRequestMethod() == "XMLRPC" || Context::getResponseMethod() == "XMLRPC")
    {
        return;
    }

    if(Context::getRequestMethod() == "POST" && $called_position == 'before_module_proc') {
        $mode = $_REQUEST['mode'];
        if(!$mode || $mode != "fb")
        {
            return;
        }
        $oController = &getController('tccommentnotify');
        $oController->procNotifyReceived();
        return;
    }

    if($called_position == "after_module_proc")
    {
        $oModel = &getModel('tccommentnotify');
        if($oModel->checkShouldNotify())
        {
            $scriptCode = <<<EndOfScript
        <script type="text/javascript">
        // <![CDATA[
            exec_xml("tccommentnotify", "procDoNotify");
        // ]]>
        </script>

EndOfScript;
            Context::addHtmlHeader($scriptCode);
        }
    }
?>
