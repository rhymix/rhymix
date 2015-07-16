(function($){
  "user strict";
  $(function(){
    //$('.xeicon').parallax('50%', 0.4);

    $('.fe_box').on('mouseenter mouseleave',function(e){
      $this = $(this);
      if(e.type == 'mouseenter'){
        $this.addClass('on');
      } else {
        $this.removeClass('on');
      }

    });
  });
})(jQuery);

(function($) {
  "user strict";
  var $window = $(window);
  var windowHeight = $window.height();

  $window.resize(function() {
    windowHeight = $window.height();
  });


  $.fn.parallax = function(xpos, speedFactor, outerHeight) {
    var $this = $(this);
    var getHeight;
    var firstTop;
    $this.each(function() {
      if($this.hasClass('xeicon')){
        firstTop = $this.offset().top + 600;
      } else {
        firstTop = $this.offset().top;
      }
    });

    if (outerHeight) {
      getHeight = function(object) {
        return object.outerHeight(true);
      };
    } else {
      getHeight = function(object) {
        return object.height();
      };
    }
    if (arguments.length < 1 || xpos === null)
      xpos = "50%";
    if (arguments.length < 2 || speedFactor === null)
      speedFactor = 0.1;
    if (arguments.length < 3 || outerHeight === null)
      outerHeight = true;
    function update() {
      var pos = $window.scrollTop();
      $this.each(function() {
        var $element = $(this);
        var top = $element.offset().top;
        var height = getHeight($element);

        if (top + height < pos || top > pos + windowHeight) {
          return;
        }
        $this.css('backgroundPosition', xpos + " " + Math.round((firstTop - pos) * speedFactor) + "px");
      });
    }
    $window.bind('scroll', update).resize(update);
    update();
  };
})(jQuery);
