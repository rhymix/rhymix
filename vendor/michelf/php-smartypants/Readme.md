PHP SmartyPants
===============

PHP SmartyPants Lib 1.6.0-beta1 - Sun 23 Jan 2013

by Michel Fortin  
<http://michelf.ca/>

Original SmartyPants by John Gruber  
<http://daringfireball.net/>


Introduction
------------

This is a library package that includes the PHP SmartyPants and its
sibling PHP SmartyPants Typographer with additional features.

SmartyPants is a free web typography prettifyier tool for web writers. It
easily translates plain ASCII punctuation characters into "smart" typographic 
punctuation HTML entities.

PHP SmartyPants is a port to PHP of the original SmartyPants written 
in Perl by John Gruber.

SmartyPants can perform the following transformations:

*   Straight quotes (`"` and `'`) into “curly” quote HTML entities
*   Backtick-style quotes (` ``like this'' `) into “curly” quote HTML
    entities
*   Dashes (`--` and `---`) into en- and em-dash entities
*   Three consecutive dots (`...`) into an ellipsis entity

SmartyPants Typographer can perform those additional transformations:

*	French guillements done using (`<<` and `>>`) into true « guillemets »
	HTML entities.
*	Replace existing spaces with non-break spaces around punctuation marks 
	where appropriate, can also add or remove them if configured to.
*	Replace existing spaces with non-break spaces for spaces used as 
	a thousand separator and between a number and the unit symbol that 
	follows it (for most common units).

This means you can write, edit, and save using plain old ASCII straight 
quotes, plain dashes, and plain dots, but your published posts (and 
final HTML output) will appear with smart quotes, em-dashes, proper
ellipses, and proper no-break spaces (with Typographer).

SmartyPants does not modify characters within `<pre>`, `<code>`,
`<kbd>`, or `<script>` tag blocks. Typically, these tags are used to
display text where smart quotes and other "smart punctuation" would not
be appropriate, such as source code or example markup.


### Backslash Escapes ###

If you need to use literal straight quotes (or plain hyphens and
periods), SmartyPants accepts the following backslash escape sequences
to force non-smart punctuation. It does so by transforming the escape
sequence into a decimal-encoded HTML entity:


    Escape  Value  Character
    ------  -----  ---------
      \\    &#92;    \
      \"    &#34;    "
      \'    &#39;    '
      \.    &#46;    .
      \-    &#45;    -
      \`    &#96;    `


This is useful, for example, when you want to use straight quotes as
foot and inch marks:

    6\'2\" tall

translates into:

    6&#39;2&#34; tall

in SmartyPants's HTML output. Which, when rendered by a web browser,
looks like:

    6'2" tall


Installation and Requirement
----------------------------

This library package requires PHP 5.3 or later.

Note: The older plugin/library hybrid package for PHP SmartyPants and
PHP SmartyPants Typographer is still will work with PHP 4.0.5 and later.


Usage
-----

This library package is meant to be used with class autoloading. For autoloading 
to work, your project needs have setup a PSR-0-compatible autoloader. See the 
included Readme.php file for a minimal autoloader setup. (If you don't want to 
use autoloading you can do a classic `require_once` to manually include the 
files prior use instead.)

With class autoloading in place, putting the 'Michelf' folder in your 
include path should be enough for this to work:

	use \Michelf\SmartyPants;
	$html_output = SmartyPants::defaultTransform($html_input);

SmartyPants Typographer is also available the same way:

	use \Michelf\SmartyPantsTypographer;
	$html_output = SmartyPantsTypographer::defaultTransform($html_input);

If you are using PHP SmartyPants with another text filter function that 
generates HTML such as Markdown, you should filter the text *after* the 
`transform` function call. This is an example with [PHP Markdown][pmd]:

	use \Michelf\Markdown, \Michelf\SmartyPants;
	$my_html = Markdown::defaultTransform($my_text);
	$my_html = SmartyPants::defaultTransform($my_html);

To learn more about configuration options, see the full list of
[configuration variables].

 [configuration variables]: http://michelf.ca/projects/php-smartypants/configuration/
 [pmd]: http://michelf.ca/projects/php-markdown/


Options and Configuration
-------------------------

To change the default behaviour, you can pass a second argument to the
`defaultTransform` function with a configuration string. You can also 
instantiate a parser object directly with the configuration string and then
call its `transform` method:

	$my_html = SmartyPants::defaultTransform($my_html, 'qBD');

	$parser = new SmartyPants('qBD');
	$my_html = $parser->transform($my_html);

Numeric values are the easiest way to configure SmartyPants's behavior:

"0"
:   Suppress all transformations. (Do nothing.)

"1"
:   Performs default SmartyPants transformations: quotes (including
    backticks-style), em-dashes, and ellipses. `--` (dash dash) is
    used to signify an em-dash; there is no support for en-dashes.

"2"
:   Same as smarty_pants="1", except that it uses the old-school
    typewriter shorthand for dashes: `--` (dash dash) for en-dashes,
    `---` (dash dash dash) for em-dashes.

"3"
:   Same as smarty_pants="2", but inverts the shorthand for dashes: `--`
    (dash dash) for em-dashes, and `---` (dash dash dash) for en-dashes.

"-1"
:   Stupefy mode. Reverses the SmartyPants transformation process,
    turning the HTML entities produced by SmartyPants into their ASCII
    equivalents. E.g. `&#8220;` is turned into a simple double-quote
    (`"`), `&#8212;` is turned into two dashes, etc. This is useful if you
    wish to suppress smart punctuation in specific pages, such as
    RSS feeds.

The following single-character attribute values can be combined to
toggle individual transformations from within the configuration parameter.
For example, to educate normal quotes and em-dashes, but not
ellipses or backticks-style quotes:

    $my_html = SmartyPants::defaultTransform($my_html, "qd");

"q"
:   Educates normal quote characters: (`"`) and (`'`).

"b"
:   Educates ` ``backticks'' ` double quotes.

"B"
:   Educates backticks-style double quotes and ` `single' ` quotes.

"d"
:   Educates em-dashes.

"D"
:   Educates em-dashes and en-dashes, using old-school typewriter
    shorthand: (dash dash) for en-dashes, (dash dash dash) for
    em-dashes.

"i"
:   Educates em-dashes and en-dashes, using inverted old-school
    typewriter shorthand: (dash dash) for em-dashes, (dash dash dash)
    for en-dashes.

"e"
:   Educates ellipses.

"w"
:   Translates any instance of `&quot;` into a normal double-quote
    character. This should be of no interest to most people, but of
    particular interest to anyone who writes their posts using
    Dreamweaver, as Dreamweaver inexplicably uses this entity to
    represent a literal double-quote character. SmartyPants only
    educates normal quotes, not entities (because ordinarily, entities
    are used for the explicit purpose of representing the specific
    character they represent). The "w" option must be used in
    conjunction with one (or both) of the other quote options ("q" or
    "b"). Thus, if you wish to apply all SmartyPants transformations
    (quotes, en- and em-dashes, and ellipses) and also translate
    `&quot;` entities into regular quotes so SmartyPants can educate
    them, you should set the configuration argument when calling the 
	function:

        $my_html = SmartyPants::defaultTransform($my_html, "qDew");


### Algorithmic Shortcomings ###

One situation in which quotes will get curled the wrong way is when
apostrophes are used at the start of leading contractions. For example:

    'Twas the night before Christmas.

In the case above, SmartyPants will turn the apostrophe into an opening
single-quote, when in fact it should be a closing one. I don't think
this problem can be solved in the general case -- every word processor
I've tried gets this wrong as well. In such cases, it's best to use the
proper HTML entity for closing single-quotes (`&#8217;` or `&rsquo;`) by
hand.


Public API and Versioning Policy
---------------------------------

Version numbers are of the form *major*.*minor*.*patch*.

The public API of PHP Markdown consist of the two parser classes `SmartyPants`
and `SmartyPantsTypographer`, their constructors, the `transform` and
`defaultTransform` functions. The public API is stable for a given major 
version number. It might get additions when the minor version number increments.

Public members are the public API. Protected members are not: while subclassing 
the parser might be useful in some case, generally its done to change how 
things works, most often in a way that requires specific knowleadge of the 
internals. I don't want to discourage such hacks, hence why most members are
protected, but I can't guarenty that new versions change the internals.


Bugs
----

To file bug reports or feature requests (other than topics listed in the
Caveats section above) please send email to:

<michel.fortin@michelf.ca>

If the bug involves quotes being curled the wrong way, please send
example text to illustrate.


Version History
---------------

PHP SmartyPants Lib 1.6.0-beta1 (23 Jan 2013)

Typographer 1.0.1 (23 Jan 2013)

1.5.1f (23 Jan 2013):

*	Fixed handling of HTML comments to match latest HTML specs instead of
	doing it the old SGML way.

*	Lowered WordPress filtering priority to avoid clashing with the 
	[caption] tag filter. Thanks to Mehdi Kabab for the fix.


Typographer 1.0 (28 Jun 2006)

*   First public release of PHP SmartyPants Typographer.


1.5.1oo (19 May 2006, unreleased)

*   Converted SmartyPants to a object-oriented design.


1.5.1e (9 Dec 2005)

*	Corrected a bug that prevented special characters from being 
    escaped.


1.5.1d (6 Jun 2005)

*	Correct a small bug in `_TokenizeHTML` where a Doctype declaration
	was not seen as HTML, making curly quotes inside it.


1.5.1c (13 Dec 2004)

*	Changed a regular expression in `_TokenizeHTML` that could lead
	to a segmentation fault with PHP 4.3.8 on Linux.


1.5.1b (6 Sep 2004)

*	Corrected a problem with quotes immediately following a dash
	with no space between: `Text--"quoted text"--text.`
	
*	PHP SmartyPants can now be used as a modifier by the Smarty 
	template engine. Rename the file to "modifier.smartypants.php"
	and put it in your smarty plugins folder.

*	Replaced a lot of spaces characters by tabs, saving about 4 KB.


1.5.1a (30 Jun 2004)

*	PHP Markdown and PHP Smartypants now share the same `_TokenizeHTML` 
	function when loaded simultanously.

*	Changed the internals of `_TokenizeHTML` to lower the PHP version
	requirement to PHP 4.0.5.


1.5.1 (6 Jun 2004)

*	Initial release of PHP SmartyPants, based on version 1.5.1 of the 
	original SmartyPants written in Perl.


Copyright and License
---------------------

Copyright (c) 2005-2013 Michel Fortin  
<http://michelf.ca/>
All rights reserved.

Copyright (c) 2003-2004 John Gruber
<http://daringfireball.net/>
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are
met:

*   Redistributions of source code must retain the above copyright notice,
    this list of conditions and the following disclaimer.

*   Redistributions in binary form must reproduce the above copyright
    notice, this list of conditions and the following disclaimer in the
    documentation and/or other materials provided with the distribution.

*   Neither the name "SmartyPants" nor the names of its contributors may
    be used to endorse or promote products derived from this software
    without specific prior written permission.
