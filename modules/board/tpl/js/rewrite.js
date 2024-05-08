'use strict';

/**
 * Remove unnecessary parameters from short URL.
 *
 * - category
 * - comment_srl
 * - page number
 */
(function() {
	var newpath = '';
	var match = location.pathname.match(/\/([a-zA-Z0-9_]+)\/([0-9]+)\/(comment|page)\/([0-9]+)$/);
	if (match && match[1] === window.current_mid) {
		newpath = location.pathname.replace(/\/(comment|page)\/([0-9]+)$/, '');
	}
	if (location.pathname.match(/\/([0-9]+)$/) && location.search.match(/^\?category=[0-9]+$/)) {
		newpath = newpath ? newpath : location.pathname;
	}
	if (newpath && location.hash && location.hash != '#') {
		newpath += location.hash;
	}
	if (newpath && history.replaceState) {
		history.replaceState({
			rx_replaced: true,
			prev: location.pathname,
		}, '', newpath);
	}
})();
