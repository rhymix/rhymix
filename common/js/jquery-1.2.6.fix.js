
/*
 * jQuery 1.2.6
 * Opera 브라우저에서 $(window).height() / width() 값을 잘못 가져오는 문제 수정
 * jQuery 1.3에서 수정되었음
 * @link http://dev.jquery.com/changeset/5938
 */
if(jQuery.fn.jquery == '1.2.6') {
    jQuery.each([ "Height", "Width" ], function(i, name){
        var type = name.toLowerCase();

        jQuery.fn[ type ] = function( size ) {
            return this[0] == window ?
                // Opera 브라우저에서 $(window).height() / width() 값을 잘못 가져오는 문제 수정
                jQuery.browser.opera  && document.body.parentNode[ "client" + name ] ||

                jQuery.browser.safari && window[ "inner" + name ] ||
                document.compatMode == "CSS1Compat" && document.documentElement[ "client" + name ] || document.body[ "client" + name ] :

                this[0] == document ?
                    Math.max(
                        Math.max(document.body["scroll" + name], document.documentElement["scroll" + name]),
                        Math.max(document.body["offset" + name], document.documentElement["offset" + name])
                    ) :

                    size == undefined ?
                        (this.length ? jQuery.css( this[0], type ) : null) :
                        this.css( type, size.constructor == String ? size : size + "px" );
        };
    });
}
