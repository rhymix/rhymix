/**
 * procFilter 함수를 가로채서 captcha 이미지 및 폼을 출력
 **/
var oldExecXml = null;
var calledArgs = null;
(function($){
    $(function() {
	
        var captchaXE = null;

        function xeCaptcha() {
            var body    = $(document.body);
            var captchaIma;
            
            if (!captchaXE) {
                captchaXE = $("<div>")
                    .attr("id","captcha_screen")
                    .css({
                        position:"absolute",
                        display:"none",
                        backgroundColor:"#111",
                        backgroundRepeat:"repeat",
                        backgroundPosition:"0 0",
                        zIndex:500
                    });

                $('<div id="captchaBox" style="display:none;*zoom:1;overflow:hidden;height:200px;">'+
                  '<img src="" id="captcha_image" />'+
                  '<p style="color:#666;width:250px;padding:0;margin:10px 0 20px 0; " id="captchaAbout">&nbsp;</p>'+
                  '<p style="color:#DDD;width:250px;font-size:15px;padding:0; margin:0 0 10px; font-weight:bold; text-align:center;" id="captchaText">&nbsp;</p>'+
                  '</div>').appendTo(captchaXE);
                    
                body.append(captchaXE);
                
                captchaXE.exec= function(act, args) {
                    if(act == 'procBoardInsertDocument' || act == 'procBoardInsertComment' || act == 'procIssuetrackerInsertIssue' || act == 'procIssuetrackerInsertHistory') {
                        oldExecXml('captcha','setCaptchaSession',new Array(),this.show,new Array('error','message','about','keyword'));
                        calledArgs = args;
                    } else {
                        oldExecXml(args.module, args.act,args.params,args.callback_func,args.response_tags,args.callback_func_arg,args.fo_obj);
                    }
                };

                captchaXE.show = function(ret_obj) {
                    var clientWidth  = $(window).width();
                    var clientHeight = $(window).height();

                    $(document).scrollTop(0);
                    $(document).scrollLeft(0);

                    $("#captcha_screen").css({
                        display:"block",
                        width  : clientWidth+"px",
                        height : clientHeight+"px",
                        left   : 0,
                        top    : 0
                    });

                    $("#captchaAbout").html(ret_obj['about']);
                    $("#captchaText").html(ret_obj['keyword']);

                    $("#captcha_image")
                        .css( {
                            width:"250px",
                            height:"100px",
                            margin:"0 0 10px 0",
                            cursor:"pointer"
                        })
                        .attr("src", request_uri.setQuery('act','captchaImage').setQuery('rnd',Math.round(Math.random() * 6)))
                        .click (captchaXE.compare)
                        .focus( function() { this.blur(); } );

                    $("#captchaBox")
                        .css({
                            display:"block",
                            border:"10px solid #222222",
                            padding:"10px",
                            position:"absolute",
                            backgroundColor:"#2B2523",
                            left   : (clientWidth/2-125)+"px",
                            top    : (clientHeight/2-100)+"px"
                        })
                };

                captchaXE.compare = function(e) {
                    var posX = parseInt($("#captchaBox").css("left").replace(/px$/,''),10);
                    var posY = parseInt($("#captchaBox").css("top").replace(/px$/,''),10);
                    var x = e.pageX - posX - 20;
                    var y = e.pageY - posY - 20;
                    var params = new Array();
                    params["mx"] = x;
                    params["my"] = y;
                    oldExecXml('captcha','captchaCompare',params, function() {
                        $("#captcha_screen").css({ display:"none" });
                        oldExecXml(calledArgs.module, calledArgs.act, calledArgs.params, calledArgs.callback_func, calledArgs.response_tags, calledArgs.callback_func_arg, calledArgs.fo_obj);
                    } );
                };
            }
            return captchaXE;
        }

        $(window).ready(function(){
            oldExecXml = exec_xml;
            exec_xml = null;
            var newFunc = function(module, act, params, callback_func, response_tags, callback_func_arg, fo_obj) {xeCaptcha().exec(act, {module:module, act:act,params:params,callback_func:callback_func,response_tags:response_tags,callback_func_arg:callback_func_arg,fo_obj:fo_obj})};
            exec_xml = newFunc;
        });
    });
})(jQuery);
