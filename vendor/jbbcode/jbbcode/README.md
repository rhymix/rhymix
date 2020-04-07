jBBCode
=======
[![GitHub release](https://img.shields.io/github/release/jbowens/jBBCode.svg)](https://github.com/jbowens/jBBCode/releases)
[![Software License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/jbowens/jBBCode.svg)](https://travis-ci.org/jbowens/jBBCode)

jBBCode is a bbcode parser written in php 5.3. It's relatively lightweight and parses
bbcodes without resorting to expensive regular expressions.

Documentation
-------------

For complete documentation and examples visit [jbbcode.com](http://jbbcode.com).

### A basic example

jBBCode includes a few optional, default bbcode definitions that may be loaded through the
`DefaultCodeDefinitionSet` class. Below is a simple example of using these codes to convert
a bbcode string to html.

```php
<?php
require_once "/path/to/jbbcode/Parser.php";

$parser = new JBBCode\Parser();
$parser->addCodeDefinitionSet(new JBBCode\DefaultCodeDefinitionSet());

$text = "The default codes include: [b]bold[/b], [i]italics[/i], [u]underlining[/u], ";
$text .= "[url=http://jbbcode.com]links[/url], [color=red]color![/color] and more.";

$parser->parse($text);

print $parser->getAsHtml();
```

### Composer

You may load jBBCode via composer. In your composer.json file:

```json
"require": {
    "jbbcode/jbbcode": "1.3.*"
}
```

In your php file:

```php
require 'vendor/autoloader.php';

$parser = new JBBCode\Parser();
```

Contribute
----------

I would love help maintaining jBBCode. Look at [open issues](http://github.com/jbowens/jBBCode/issues) for ideas on
what needs to be done. Before submitting a pull request, verify that all unit tests still pass.

#### Running unit tests
To run the unit tests,
ensure that [phpunit](http://github.com/sebastianbergmann/phpunit) is installed, or install it through the composer
dev dependencies. Then run `phpunit` from the project directory. If you're adding new functionality, writing
additional unit tests is a great idea.


License
-------

The project is under MIT license. Please see the [license file](LICENSE.md) for details.
