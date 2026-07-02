/**
 * Initialize global variables and body attributes for Rhymix.
 *
 * This script parses the JSON data to set global variables without violating CSP.
 * It must be loaded immediately after the opening <body> tag, but not before.
 */
(function() {

	// Set global variables.
	const el = document.getElementById('rhymix_global_vars');
	if (el) {
		let vars = JSON.parse(el.textContent);
		for (let key in vars) {
			if (vars.hasOwnProperty(key)) {
				window[key] = vars[key];
			}
		}
	}

	// Set aliases for backward compatibility.
	if (window.xe && window.current_lang) {
		xe.current_lang = window.current_lang;
	}

	// Set the body color scheme.
	if (typeof window.detectColorScheme === 'function') {
		window.detectColorScheme();
	}

})();
