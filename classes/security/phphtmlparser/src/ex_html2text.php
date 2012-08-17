<?
    // Example: html2text
    // Converts HTML to formatted ASCII text.
    // Run with: php < ex_html2text.php

    include ("html2text.inc");

    $htmlText = "Html2text is a tool that allows you to<br>" .
                "convert HTML to text.<p>" .
                "Does it work?";

    $htmlToText = new Html2Text ($htmlText, 15);
    $text = $htmlToText->convert();
    echo "Conversion follows:\r\n";
    echo "-------------------\r\n";
    echo $text;

?>
