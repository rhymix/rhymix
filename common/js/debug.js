
/**
 * Client-side script for manipulating the debug panel on Rhymix.
 * 
 * @file debug.js
 * @author Kijin Sung <kijin@kijinsung.com>
 */
jQuery(function() {
	
	// Find debug panel elements.
	var panel = $("#rhymix_debug_panel");
	var button = $("#rhymix_debug_button");
	
	// Initialize the debug button.
	$('<a href="#"></a>').text("DEBUG").appendTo(button).click(function(event) {
		event.preventDefault();
		panel.css({ width: 0 }).show().animate({ width: 640 }, 200);
		button.hide();
	});
	
	// Initialize the debug panel.
	var header = $('<div class="debug_header"></div>').appendTo(panel);
	header.append('<h2>RHYMIX DEBUG</h2>');
	header.append($('<a class="debug_maximize" href="#">+</a>').click(function(event) {
		panel.animate({ width: "95%" }, 300);
	}));
	header.append($('<a class="debug_close" href="#">&times;</a>').click(function(event) {
		event.preventDefault();
		panel.animate({ width: 0 }, 200, function() {
			panel.hide();
			button.show();
		});
	}));
	
	// Define a function for adding debug data to the panel.
	window.rhymix_debug_add_data = function(data) {
		
		// Create the page.
		var page = $('<div class="debug_page"></div>').appendTo(panel);
		var page_body = $('<div class="debug_page_body"></div>').appendTo(page);
		
		// Create the page header.
		var page_header = $('<div class="debug_page_header"></div>').prependTo(page);
		page_header.append($('<h3></h3>').text(data.page_title).attr("title", data.url));
		page_header.append($('<a class="debug_page_collapse" href="#"></a>').text("▲").click(function(event) {
			event.preventDefault();
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
		page_body.append($('<div class="debug_entry no_indentation"></div>').text(
			'Request: ' + data.request.method + ' (' + data.request.size + ' bytes)' + "\n" +
			'Response: ' + data.response.method + ' (' + data.response.size + ' bytes)' + "\n" +
			'Time: ' + data.timing.total));
			
		// Add debug entries.
		if (data.entries && data.entries.length) {
			page_body.append($('<h4></h4>').text('Debug Entries (' + data.entries.length + ')'));
			for (var i in data.entries) {
				var entry = $('<div class="debug_entry"></div>').appendTo(page_body);
				var num = parseInt(i) + 1; if (num < 10) num = "0" + num;
				var backtrace = "";
				for (var j in data.entries[i].backtrace) {
					backtrace += "\n- " + data.entries[i].backtrace[j].file + ":" + data.entries[i].backtrace[j].line;
				}
				entry.text(num + ". " + data.entries[i].message + backtrace);
			}
		}
		
		// Add errors.
		if (data.errors && data.errors.length) {
			page_body.append($('<h4></h4>').text('Errors (' + data.errors.length + ')'));
			for (var i in data.errors) {
				var entry = $('<div class="debug_entry"></div>').appendTo(page_body);
				var num = parseInt(i) + 1; if (num < 10) num = "0" + num;
				var backtrace = "";
				for (var j in data.errors[i].backtrace) {
					backtrace += "\n- " + data.errors[i].backtrace[j].file + ":" + data.errors[i].backtrace[j].line;
				}
				entry.text(num + ". " + data.errors[i].type + ": " + data.errors[i].message + backtrace);
			}
		}
		
		// Add queries.
		if (data.queries && data.queries.length) {
			page_body.append($('<h4></h4>').text('Queries (' + data.queries.length + ')'));
			for (var i in data.queries) {
				var entry = $('<div class="debug_entry collapse_spaces"></div>').appendTo(page_body);
				var num = parseInt(i) + 1; if (num < 10) num = "0" + num;
				var description = "";
				description += "\nCaller: " + data.queries[i].file + ":" + data.queries[i].line + " (" + data.queries[i].method + ")";
				description += "\nConnection: " + data.queries[i].query_connection;
				description += "\nQuery Time: " + data.queries[i].query_time.toFixed(4) + " sec";
				description += "\nResult: " + ((data.queries[i].message === "success") ? "success" : ("error " + data.queries[i].error_code + " " + data.queries[i].message));
				entry.text(num + ". " + data.queries[i].query_string + description);
			}
		}
	};
	
	// Add debug data from the current page.
	if (rhymix_debug_content) {
		rhymix_debug_content.page_title = 'MAIN PAGE';
		rhymix_debug_add_data(rhymix_debug_content);
	}
	
	// Add debug data from pending AJAX requests.
	if (rhymix_debug_pending_data) {
		while (rhymix_debug_pending_data.length) {
			rhymix_debug_add_data(rhymix_debug_pending_data.shift());
		}
	}
});
