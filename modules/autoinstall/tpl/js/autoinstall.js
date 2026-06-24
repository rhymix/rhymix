(function($) {
	$(function() {

		// Prevent click on disabled buttons
		$(document).on('click', '.x_btn-disabled', function(e) {
			e.preventDefault();
		});

		// Install & update buttons
		$('.autoinstall_install_btn, .autoinstall_update_btn').on('click', async function(e) {
			e.preventDefault();
			const btn = $(this);
			if (btn.hasClass('x_btn-disabled')) {
				return;
			}
			btn.addClass('x_btn-disabled').removeClass('x_btn-primary');
			btn.data('originalText', btn.text());

			// Step 1: download
			btn.text(btn.data('downloading'));
			const res1 = await Rhymix.ajax('autoinstall.procAutoinstallAdminDownloadPackage', {
				'package_srl': parseInt(btn.data('packageSrl'), 10),
				'mode': btn.hasClass('autoinstall_install_btn') ? 'install' : 'update'
			}).catch(function(err) {
				NativeAlert(err.message);
				btn.removeClass('x_btn-disabled').addClass('x_btn-primary');
				btn.text(btn.data('originalText'));
			});
			if (!res1 || res1.message !== 'success') {
				return;
			}

			// Step 2: install
			btn.text(btn.data('installing'));
			const res2 = await Rhymix.ajax('autoinstall.procAutoinstallAdminInstallPackage', {
				'package_srl': parseInt(btn.data('packageSrl'), 10),
				'mode': btn.hasClass('autoinstall_install_btn') ? 'install' : 'update'
			}).catch(function(err) {
				NativeAlert(err.message);
				btn.removeClass('x_btn-disabled').addClass('x_btn-primary');
				btn.text(btn.data('originalText'));
			});
			if (!res2 || res2.message !== 'success') {
				return;
			}

			// Step 3: post-install cleanup
			btn.text(btn.data('cleanup'));
			Rhymix.ajax('autoinstall.procAutoinstallAdminPostInstallPackage', {
				'package_srl': parseInt(btn.data('packageSrl'), 10),
				'mode': btn.hasClass('autoinstall_install_btn') ? 'install' : 'update'
			}).then(function(res3) {
				NativeAlert(btn.data('complete'));
				location.reload();
			}).catch(function(err) {
				NativeAlert(err.message);
				btn.removeClass('x_btn-disabled').addClass('x_btn-primary');
				btn.text(btn.data('originalText'));
			});
		});

		// Uninstall button
		$('.autoinstall_uninstall_btn').on('click', function(e) {
			e.preventDefault();
			const btn = $(this);
			if (!confirm(btn.data('confirm'))) {
				return;
			}
			Rhymix.ajax('autoinstall.procAutoinstallAdminUninstallPackage', {
				'package_srl': parseInt(btn.data('packageSrl'), 10)
			}).then(function(data) {
				NativeAlert(data.message);
				location.reload();
			}).catch(function(err) {
				NativeAlert(err.message);
			});
		});
	});
})(jQuery);
