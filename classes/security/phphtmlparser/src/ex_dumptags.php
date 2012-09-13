<?
    include ("htmlparser.inc");
    $htmlText = "<html><!-- comment --><body>This is the body</body></html>";
    $parser = new HtmlParser($htmlText);
    while ($parser->parse()) {
        echo "-----------------------------------\r\n";
        echo "Node type: " . $parser->iNodeType . "\r\n";
        echo "Node name: " . $parser->iNodeName . "\r\n";
        echo "Node value: " . $parser->iNodeValue . "\r\n";
    }
?>
