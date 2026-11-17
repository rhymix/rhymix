(function($) {

	// Simple function to decode version strings into comparable numeric values
	const version_decode = function(str) {
		var version_number = 0;
		var extra_strings = '';
		var parts = str.split(/[\.-]/);
		$.each(parts, function(i, part) {
			if (part.match(/^[0-9]+$/)) {
				version_number += parseInt(part, 10) * Math.pow(1000, 3 - i);
			} else {
				extra_strings = extra_strings + part;
			}
		});
		return String(version_number) + (extra_strings === '' ? 'z' : extra_strings);
	};

	$(function() {

		// Core update check
		const core_update = $('.core_update');
		if (core_update.length) {
			$.ajax({
				url: 'https://api.rhymix.org/version.json',
				dataType: 'json',
				cache: true,
				success: function(data) {
					const current_version = version_decode(core_update.data('currentVersion'));
					const latest_version = version_decode(data.latest);
					if (latest_version > current_version) {
						let version_replace = core_update.find('p').first();
						version_replace.html(version_replace.html().replace('$VERSION', data.latest));
						core_update.show();
					}
				}
			});
		}

		// Autoinstall package update check in the background
		if ($('.autoinstall_needUpdatePackageList').length) {
			setTimeout(function() {
				Rhymix.ajax('autoinstall.procAutoinstallAdminUpdatePackageList').then(function(){}).catch(function(){});
			}, 500);
		}

		// Install module
		$('.doInstallModule').on('click', function() {
			Rhymix.ajax('install.procInstallAdminInstall', {
				module_name: $(this).data('module')
			}).then(function(data) {
				NativeAlert(data.message);
				location.reload();
			});
		});

		// Update module
		$('.doUpdateModule').on('click', function() {
			Rhymix.ajax('install.procInstallAdminUpdate', {
				module_name: $(this).data('module')
			}).then(function(data) {
				NativeAlert(data.message)
				location.reload();
			});
		});

	});

})(jQuery);
