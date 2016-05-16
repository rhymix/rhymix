jQuery(function($)
{
	// Dashboard portlet UI
	$('.dashboard>div>section>ul>li')
	.bind('mouseenter focusin', function(){
		$(this).addClass('hover').find('>.action').show();
	})
	.bind('mouseleave focusout', function()
	{
		if(!$(this).find(':focus').length)
		{
			$(this).removeClass('hover').find('>.action').hide();
		}
	});
});

function obj2Array(htObj)
{
	var aRes = [];

	for(var x in htObj)
	{
		if(!htObj.hasOwnProperty(x)) continue;
		aRes.push(htObj[x]);
	}

	return aRes;
}

jQuery(function ($)
{
	$.exec_json("counter.getWeeklyUniqueVisitor", {}, function(htRes)
	{
		var aLastWeek = obj2Array(htRes.last_week.list);
		var aThisWeek = obj2Array(htRes.this_week.list);

		drawChart("visitors", "Weekly Visitors", aLastWeek, aThisWeek);
	});

	$.exec_json("counter.getWeeklyPageView", {}, function(htRes)
	{
		var aLastWeek = obj2Array(htRes.last_week.list);
		var aThisWeek = obj2Array(htRes.this_week.list);

		drawChart("page_views", "Weekly Page Views", aLastWeek, aThisWeek);
	});
});

function drawChart(sContainerId, sTitle, aLastWeek, aThisWeek)
{
	$ = jQuery;

	var s1 = aLastWeek;
	var s2 = aThisWeek;
	var ticks = [xe.lang.sun,xe.lang.mon,xe.lang.tue,xe.lang.wed,xe.lang.thu,xe.lang.fri,xe.lang.sat];

	var plot1 = $.jqplot(sContainerId, [s1, s2], {
		seriesDefaults:{
			renderer:$.jqplot.BarRenderer,
			rendererOptions: {fillToZero: true}
		},
		series:[
			{label: xe.lang.last_week},
			{label: xe.lang.this_week}
		],
		legend:
		{
			show: true,
			placement: 'outsideGrid'
		},
		axes: {
			xaxis: {
				renderer: $.jqplot.CategoryAxisRenderer, ticks: ticks
			},
			yaxis: {
				min: 0, ticks: 1, pad: 1.05
			}
		}
	});
}
