
jQuery(function($){
// Global Navigation Bar
	var gnb = $('.header>ul.gnb');
	var gnbItem = gnb.find('>li.m1');
	var subItem = gnb.find('>li.m1>div.sub');
	var lastEvent = null;
	subItem.hide();
	
	function gnbToggle(){
		var t = $(this);
		gnbItem.removeClass('m_on');
		t.parent('li.m1').addClass('m_on');
		if (t.next('div.sub').is(':hidden') || t.next('div.sub').length == 0) {
			gnbItem.find('div.sub').hide();
			gnbItem.find('a').removeClass('m_on');
			t.next('div.sub').show();
			t.next('div.sub').find('a').attr('class','');
			t.parent('li.m1').addClass('m_on');
		}; 
	};
	function gnbOut(){
		gnbItem.find('>div.sub').hide();
		$(this).removeClass('m_on');
	};

	gnbItem.find('>a.m1_a').mouseover(gnbToggle);
	gnbItem.mouseleave(gnbOut);

	gnbItem.find('>a.m1_a').focus(function() {
		var t = $(this);
		gnbItem.removeClass('m_on');
		if (t.next('div.sub').is(':hidden') || t.next('div.sub').length == 0) {
			gnbItem.find('div.sub').hide();
			gnbItem.find('a').removeClass('m_on');
			t.next('div.sub').show();
			t.next('div.sub').find('a').attr('class','');
			t.parent('li.m1').addClass('m_on');
		}; 
		t.parent('li.m1').addClass('m_on');
	});

	gnbItem.find('>a.m1_a').next('div.sub').find('a').focus(function() {
		var t = $(this);
		t.attr('class','a_on');

	});

// Select Language
	var langBtn = $('dl#selectLang>dd>div.langBtn');
	var langSet = $('dl#selectLang>dd>ul.langSet');
	langBtn.click(function(){
		//alert('Hi there');
		langBtn.next('ul.langSet').toggle();
	});
	langSet.find('>li').hover(
		function(){$(this).addClass('on')},
		function(){$(this).removeClass('on')}
	);
		
		

});