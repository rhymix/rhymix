/**
 * Client-side script for manipulating the debug panel on Rhymix.
 * 
 * @file debug.js
 * @author Kijin Sung <kijin@kijinsung.com>
 */
$(function() {
	
	"use strict";
	
	// Disable debug panel?
	if ($('body').hasClass("disable_debug_panel")) {
		return;
	}
	
	// Find debug panel elements.
	var panel = $("#rhymix_debug_panel");
	var button = $("#rhymix_debug_button").addClass('visible');
	
	// Initialize the debug button.
	var button_link = $('<a href="#"></a>').text("DEBUG").appendTo(button).click(function(event) {
		event.preventDefault();
		var max_width = Math.min(540, $(window).width());
		panel.css({ width: max_width, left: max_width * -1 }).show().animate({ left: 0 }, 200);
		button.hide();
	});
	
	// Initialize the debug panel.
	var header = $('<div class="debug_header"></div>').appendTo(panel);
	header.append('<h2>RHYMIX DEBUG</h2>');
	header.append($('<a class="debug_maximize" href="#">+</a>').click(function(event) {
		panel.animate({ width: "100%" }, 300);
	}));
	header.append($('<a class="debug_close" href="#">&times;</a>').click(function(event) {
		event.preventDefault();
		panel.animate({ left: panel.width() * -1 }, 200, function() {
			panel.hide();
			button.show();
		});
	}));
	
	// Define a function for adding debug data to the panel.
	window.rhymix_debug_add_data = function(data, open) {
		
		// Define loop variables.
		var i, j, entry, num, backtrace, description;
		
		// New pages are open by default.
		if (open !== true && open !== false)
		{
			open = true;
		}
		
		// Create the page.
		var page = $('<div class="debug_page"></div>').appendTo(panel);
		var page_body = $('<div class="debug_page_body"></div>').appendTo(page);
		if (!open)
		{
			page_body.hide();
		}
		
		// Create the page header.
		var page_header = $('<div class="debug_page_header"></div>').prependTo(page).click(function() {
			$(this).find("a.debug_page_collapse").triggerHandler("click");
		});
		page_header.append($('<h3></h3>').text(data.page_title).attr("title", data.url));
		page_header.append($('<a class="debug_page_collapse" href="#"></a>').text(open ? "▲" : "▼").click(function(event) {
			event.preventDefault();
			event.stopPropagation();
			if (page_body.is(":visible")) {
				page_body.slideUp(200);
				$(this).text("▼");
			} else {
				page_body.slideDown(200);
				$(this).text("▲");
			}
		}));
		
		// Add general information.
		page_body.append($('<h4></h4>').text('General Information'));
		entry = $('<div class="debug_entry"></div>').appendTo(page_body);
		var metadata = $('<ul class="debug_metadata"></ul>').appendTo(entry);
		metadata.append($('<li></li>').text('Request: ' + data.request.method + (data.request.method !== "GET" ? (' - ' + data.request.size + ' bytes') : "")));
		metadata.append($('<li></li>').text('Response: ' + data.response.method + ' - ' + data.response.size + ' bytes'));
		metadata.append($('<li></li>').text('Memory Usage: ' + (data.memory ? XE.filesizeFormat(data.memory) : 'unknown')));
		metadata.append($('<li></li>').text('Total Time: ' + data.timing.total));
		metadata.append($('<li></li>').text('Query Time: ' + data.timing.db_query));
		
		// Add debug entries.
		if (data.entries && data.entries.length) {
			page_body.append($('<h4></h4>').text('Debug Entries (' + data.entries.length + ')'));
			for (i in data.entries) {
				entry = $('<div class="debug_entry pre_wrap"></div>').appendTo(page_body);
				num = parseInt(i) + 1; if (num < 10) num = "0" + num;
				entry.text(num + ". " + data.entries[i].message);
				backtrace = $('<ul class="debug_backtrace"></ul>').appendTo(entry);
				for (j in data.entries[i].backtrace) {
					if (data.entries[i].backtrace[j].file) {
						backtrace.append($('<li></li>').text(data.entries[i].backtrace[j].file + ":" + data.entries[i].backtrace[j].line));
					}
				}
			}
		}
		
		// Add errors.
		if (data.errors && data.errors.length) {
			page_body.append($('<h4></h4>').text('Errors (' + data.errors.length + ')'));
			for (i in data.errors) {
				entry = $('<div class="debug_entry"></div>').appendTo(page_body);
				num = parseInt(i) + 1; if (num < 10) num = "0" + num;
				entry.text(num + ". " + data.errors[i].type + ": " + data.errors[i].message);
				backtrace = $('<ul class="debug_backtrace"></ul>').appendTo(entry);
				for (j in data.errors[i].backtrace) {
					if (data.errors[i].backtrace[j].file) {
						backtrace.append($('<li></li>').text(data.errors[i].backtrace[j].file + ":" + data.errors[i].backtrace[j].line));
					}
				}
			}
		}
		
		// Add queries.
		if (data.queries && data.queries.length) {
			page_body.append($('<h4></h4>').text('Queries (' + data.queries.length + ')'));
			for (i in data.queries) {
				entry = $('<div class="debug_entry"></div>').appendTo(page_body);
				num = parseInt(i) + 1; if (num < 10) num = "0" + num;
				entry.text(num + ". " + data.queries[i].query_string);
				description = $('<ul class="debug_backtrace"></ul>').appendTo(entry);
				if (data.queries[i].file && data.queries[i].line) {
					description.append($('<li></li>').text("Caller: " + data.queries[i].file + ":" + data.queries[i].line).append("<br>(" + data.queries[i].method + ")"));
					description.append($('<li></li>').text("Connection: " + data.queries[i].query_connection));
					description.append($('<li></li>').text("Query ID: " + data.queries[i].query_id));
					description.append($('<li></li>').text("Query Time: " + (data.queries[i].query_time ? (data.queries[i].query_time.toFixed(4) + " sec") : "")));
				}
				description.append($('<li></li>').text("Result: " + ((data.queries[i].message === "success" || !data.queries[i].message) ? "success" : ("error " + data.queries[i].error_code + " " + data.queries[i].message))));
			}
		}
		
		// Add slow queries.
		if (data.slow_queries && data.slow_queries.length) {
			page_body.append($('<h4></h4>').text('Slow Queries (' + data.slow_queries.length + ')'));
			for (i in data.slow_queries) {
				entry = $('<div class="debug_entry"></div>').appendTo(page_body);
				num = parseInt(i) + 1; if (num < 10) num = "0" + num;
				entry.text(num + ". " + data.slow_queries[i].query_string);
				description = $('<ul class="debug_backtrace"></ul>').appendTo(entry);
				if (data.slow_queries[i].file && data.slow_queries[i].line) {
					description.append($('<li></li>').text("Caller: " + data.slow_queries[i].file + ":" + data.slow_queries[i].line).append("<br>(" + data.slow_queries[i].method + ")"));
					description.append($('<li></li>').text("Connection: " + data.slow_queries[i].query_connection));
					description.append($('<li></li>').text("Query ID: " + data.slow_queries[i].query_id));
					description.append($('<li></li>').text("Query Time: " + (data.slow_queries[i].query_time ? (data.slow_queries[i].query_time.toFixed(4) + " sec") : "")));
				}
				description.append($('<li></li>').text("Result: " + ((data.slow_queries[i].message === "success" || !data.slow_queries[i].message) ? "success" : ("error " + data.slow_queries[i].error_code + " " + data.slow_queries[i].message))));
			}
		}
		
		// Add slow triggers.
		if (data.slow_triggers && data.slow_triggers.length) {
			page_body.append($('<h4></h4>').text('Slow Triggers (' + data.slow_triggers.length + ')'));
			for (i in data.slow_triggers) {
				entry = $('<div class="debug_entry"></div>').appendTo(page_body);
				num = parseInt(i) + 1; if (num < 10) num = "0" + num;
				entry.text(num + ". " + data.slow_triggers[i].trigger_name);
				description = $('<ul class="debug_backtrace"></ul>').appendTo(entry);
				description.append($('<li></li>').text("Target: " + data.slow_triggers[i].trigger_target));
				description.append($('<li></li>').text("Exec Time: " + (data.slow_triggers[i].trigger_time ? (data.slow_triggers[i].trigger_time.toFixed(4) + " sec") : "")));
			}
		}
		
		// Add slow widgets.
		if (data.slow_widgets && data.slow_widgets.length) {
			page_body.append($('<h4></h4>').text('Slow Widgets (' + data.slow_widgets.length + ')'));
			for (i in data.slow_widgets) {
				entry = $('<div class="debug_entry"></div>').appendTo(page_body);
				num = parseInt(i) + 1; if (num < 10) num = "0" + num;
				entry.text(num + ". " + data.slow_widgets[i].widget_name);
				description = $('<ul class="debug_backtrace"></ul>').appendTo(entry);
				description.append($('<li></li>').text("Exec Time: " + (data.slow_widgets[i].widget_time ? (data.slow_widgets[i].widget_time.toFixed(4) + " sec") : "")));
			}
		}
		
		// Add slow remote requests.
		if (data.slow_remote_requests && data.slow_remote_requests.length) {
			page_body.append($('<h4></h4>').text('Slow Remote Requests (' + data.slow_remote_requests.length + ')'));
			for (i in data.slow_remote_requests) {
				entry = $('<div class="debug_entry"></div>').appendTo(page_body);
				num = parseInt(i) + 1; if (num < 10) num = "0" + num;
				entry.text(num + ". " + data.slow_remote_requests[i].url);
				description = $('<ul class="debug_backtrace"></ul>').appendTo(entry);
				if (data.slow_remote_requests[i].file && data.slow_remote_requests[i].line) {
					description.append($('<li></li>').text("Caller: " + data.slow_remote_requests[i].file + ":" + data.slow_remote_requests[i].line).append("<br>(" + data.slow_remote_requests[i].method + ")"));
					description.append($('<li></li>').text("Elapsed Time: " + (data.slow_remote_requests[i].elapsed_time ? (data.slow_remote_requests[i].elapsed_time.toFixed(4) + " sec") : "")));
				}
				description.append($('<li></li>').text("Status Code: " + data.slow_remote_requests[i].status));
			}
		}
		
		// If there are errors, turn the button text red.
		if (data.errors && data.errors.length) {
			button_link.addClass("has_errors");
		}
	};
	
	// Add debug data from the current request.
	if (window.rhymix_debug_content) {
		window.rhymix_debug_content.page_title = 'MAIN PAGE';
		rhymix_debug_add_data(window.rhymix_debug_content, true);
	}
	
	// Add debug data from pending AJAX requests.
	if (window.rhymix_debug_pending_data) {
		while (window.rhymix_debug_pending_data.length) {
			rhymix_debug_add_data(window.rhymix_debug_pending_data.shift());
		}
	}
});
