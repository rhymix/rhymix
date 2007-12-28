/*
 * JsMin
 * Javascript Compressor
 * http://www.crockford.com/
 * http://www.smallsharptools.com/
*/

dp.sh.Brushes.CSS=function() {
var keywords='ascent azimuth background background-attachment background-color background-image background-position background-repeat baseline bbox border border-collapse border-color border-spacing border-style border-top border-right border-bottom border-left border-top-color border-right-color border-bottom-color border-left-color border-top-style border-right-style border-bottom-style border-left-style border-top-width border-right-width border-bottom-width border-left-width border-width bottom cap-height caption-side centerline clear clip color content counter-increment counter-reset cue cue-after cue-before cursor definition-src descent direction display elevation empty-cells float font font-family font-size font-size-adjust font-stretch font-style font-variant font-weight height left letter-spacing line-height list-style list-style-image list-style-position list-style-type margin margin-top margin-right margin-bottom margin-left marker-offset marks mathline max-height max-width min-height min-width orphans outline outline-color outline-style outline-width overflow padding padding-top padding-right padding-bottom padding-left page page-break-after page-break-before page-break-inside panose-1 pause pause-after pause-before pitch pitch-range play-during position quotes richness right size slope src speak speak-header speak-numeral speak-punctuation speech-rate stemh stemv stress table-layout text-align text-decoration text-indent text-shadow text-transform top unicode-bidi unicode-range units-per-em vertical-align visibility voice-family volume white-space widows width widths word-spacing x-height z-index';
var values='above absolute ActiveBorder ActiveCaption all always AppWorkspace aqua armenian attr aural auto avoid Background baseline behind below bidi-override black blink block blue bold bolder both bottom braille ButtonFace ButtonHighlight ButtonShadow ButtonText capitalize caption CaptionText center center-left center-right circle cjk-ideographic close-quote code collapse compact condensed continuous counter counters crop cross crosshair cursive dashed decimal decimal-leading-zero default digits disc dotted double embed embossed e-resize expanded extra-condensed extra-expanded fantasy far-left far-right fast faster fixed format fuchsia georgian gray GrayText green groove handheld hebrew help hidden hide high higher Highlight HighlightText hiragana hiragana-iroha icon InactiveBorder InactiveCaption InactiveCaptionText InfoBackground InfoText inline inline-table inset inside invert italic justify katakana katakana-iroha landscape large larger left left-side leftwards level lighter lime line-through list-item local loud low lower lower-alpha lowercase lower-greek lower-latin lower-roman ltr marker maroon medium Menu MenuText message-box middle mix monospace move narrower navy ne-resize no-close-quote none no-open-quote no-repeat normal nowrap n-resize nw-resize oblique olive once open-quote outset outside overline pointer portrait pre print projection purple red relative repeat repeat-x repeat-y rgb ridge right right-side rightwards rtl run-in sans-serif screen scroll Scrollbar semi-condensed semi-expanded separate se-resize serif show silent silver slow slower small small-caps small-caption smaller soft solid speech spell-out square s-resize static status-bar sub super sw-resize table table-caption table-cell table-column table-column-group table-footer-group table-header-group table-row table-row-group teal text text-bottom text-top thick thin ThreeDDarkShadow ThreeDFace ThreeDHighlight ThreeDLightShadow ThreeDShadow top transparent tty tv ultra-condensed ultra-expanded underline upper-alpha uppercase upper-latin upper-roman url visible wait white wider Window WindowFrame WindowText w-resize x-fast x-high x-large x-loud x-low x-slow x-small x-soft xx-large xx-small yellow';
var fonts='[mM]onospace [tT]ahoma [vV]erdana [aA]rial [hH]elvetica [sS]ans-serif [sS]erif [cC]ourier [mM]ono [gG]eneva ';

this.regexList=[
{regex:dp.sh.RegexLib.MultiLineCComments,css:'comment'},
{regex:dp.sh.RegexLib.DoubleQuotedString,css:'string'},
{regex:dp.sh.RegexLib.SingleQuotedString,css:'string'},
{regex:new RegExp('\\#[a-zA-Z0-9]{3,6}','g'),css:'value'},
{regex:new RegExp('(-?\\d+)(\.\\d+)?(px|em|pt|\:|\%|)','g'),css:'value'},
{regex:new RegExp('!important','g'),css:'important'},
{regex:new RegExp(this.GetKeywordsCSS(keywords),'gm'),css:'keyword'},
{regex:new RegExp(this.GetValuesCSS(values),'g'),css:'value'},
{regex:new RegExp(this.GetValuesCSS(fonts),'g'),css:'value'}
];

this.CssClass='dp-css';
this.Style='.dp-css .important { color: black; }';
}

dp.sh.Highlighter.prototype.GetKeywordsCSS=function(str)
{return'\\b([a-z_]|)'+str.replace(/ /g,'(?= ?: ?)\\b|\\b([a-z_\\*]|\\*|)')+'(?=:)\\b';}
dp.sh.Highlighter.prototype.GetValuesCSS=function(str)
{return'\\b'+str.replace(/ /g,'(?!-)(?!:)\\b|\\b()')+'\:\\b';}
dp.sh.Brushes.CSS.prototype=new dp.sh.Highlighter();dp.sh.Brushes.CSS.Aliases=['css'];
