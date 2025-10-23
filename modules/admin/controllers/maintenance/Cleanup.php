<?php

namespace Rhymix\Modules\Admin\Controllers\Maintenance;

use Context;
use ModuleController;
use ModuleModel;
use Rhymix\Framework\Exceptions\InvalidRequest;
use Rhymix\Framework\Exceptions\TargetNotFound;
use Rhymix\Framework\Security;
use Rhymix\Framework\Storage;
use Rhymix\Modules\Admin\Controllers\Base;

class Cleanup extends Base
{
	/**
	 * Cleanup list screen.
	 */
	public function dispAdminCleanupList()
	{
		// Get the list of files to clean up.
		$cleanup_list = $this->checkFiles();
		Context::set('cleanup_list', $cleanup_list);

		// Get current configuration.
		$config = ModuleModel::getModuleConfig('admin') ?: new \stdClass;
		if (!isset($config->cleanup_exceptions))
		{
			$config->cleanup_exceptions = [];
		}
		Context::set('exceptions', $config->cleanup_exceptions);

		// Check previous errors.
		Context::set('cleanup_errors', $_SESSION['admin_cleanup_errors'] ?? []);
		unset($_SESSION['admin_cleanup_errors']);

		// Set the template file.
		$this->setTemplateFile('cleanup');
	}

	/**
	 * Add a file or directory to the cleanup exception list.
	 */
	public function procAdminAddCleanupException()
	{
		// Check the path.
		$path = Context::get('path');
		if (!$path || !array_key_exists($path, self::CLEANUP_LIST))
		{
			throw new InvalidRequest;
		}

		// Get current configuration.
		$config = ModuleModel::getModuleConfig('admin') ?: new \stdClass;
		if (!isset($config->cleanup_exceptions))
		{
			$config->cleanup_exceptions = [];
		}

		// Add the path to the exception list.
		$config->cleanup_exceptions[$path] = date('Ymd');
		ModuleController::getInstance()->insertModuleConfig('admin', $config);
	}

	/**
	 * Reset the exception list.
	 */
	public function procAdminResetCleanupException()
	{
		$config = ModuleModel::getModuleConfig('admin') ?: new \stdClass;
		$config->cleanup_exceptions = [];
		ModuleController::getInstance()->insertModuleConfig('admin', $config);
	}

	/**
	 * Cleanup action.
	 */
	public function procAdminCleanupFiles()
	{
		// Cleanup!
		$result = self::deleteFiles();
		if (!count($result))
		{
			$this->setMessage('success_deleted');
		}

		// If there were errors, set information in session.
		else
		{
			$this->setError(-1);
			$this->setMessage('msg_cleanup_manually');
			$_SESSION['admin_cleanup_errors'] = $result;
		}

		// Redirect to the list screen.
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminCleanupList'));
	}

	/**
	 * Check for files to clean up.
	 *
	 * @return array
	 */
	public function checkFiles(): array
	{
		// Get current configuration.
		$config = ModuleModel::getModuleConfig('admin') ?: new \stdClass;
		if (!isset($config->cleanup_exceptions))
		{
			$config->cleanup_exceptions = [];
		}

		$result = [];
		foreach (self::CLEANUP_LIST as $name => $reason)
		{
			// Skip if registered as an exception.
			if (isset($config->cleanup_exceptions[$name]))
			{
				continue;
			}

			// Skip if file/directory distinction doesn't match.
			if (str_ends_with($name, '/') && !Storage::isDirectory(\RX_BASEDIR . rtrim($name, '/')))
			{
				continue;
			}
			if (!str_ends_with($name, '/') && !Storage::isFile(\RX_BASEDIR . $name))
			{
				continue;
			}

			// Check for case difference and moved target.
			if ($reason === 'case')
			{
				if ($this->checkCaseSensitiveFilesystem())
				{
					$result[$name] = $reason;
				}
			}
			elseif (preg_match('/^moved:(.+)$/', $reason, $matches))
			{
				if (Storage::exists(\RX_BASEDIR . $matches[1]))
				{
					$result[$name] = $reason;
				}
			}
			else
			{
				$result[$name] = $reason;
			}
		}

		ksort($result);
		return $result;
	}

	/**
	 * Check if the filesystem is case-sensitive.
	 *
	 * This method generally returns true on Linux, and false on Windows and Mac OS,
	 * but the result may differ if tested on an unusual filesystem.
	 *
	 * @return bool
	 */
	public function checkCaseSensitiveFilesystem(): bool
	{
		// Don't check more than once on the same platform.
		static $cache = null;
		if ($cache !== null)
		{
			return $cache;
		}

		// Return default values for most common operating systems.
		if (\RX_WINDOWS)
		{
			return $cache = false;
		}

		// Create two files that differ only in case, and check if they overwrite each other.
		$file1 = \RX_BASEDIR . 'files/cache/caseTest.php';
		$file2 = \RX_BASEDIR . 'files/cache/caseTEST.php';
		Storage::write($file1, '#1:' . Security::getRandom(36) . \PHP_EOL);
		Storage::write($file2, '#2:' . Security::getRandom(36) . \PHP_EOL);
		$cache = (Storage::read($file1) !== Storage::read($file2));

		// Clean up test files and return the result.
		Storage::delete($file1);
		Storage::delete($file2);
		return $cache;
	}

	/**
	 * Delete files.
	 *
	 * If a name is given, only that file or directory will be deleted.
	 * Otherwise, all files in the cleanup list will be deleted.
	 *
	 * This method returns the list of files that could not be deleted,
	 * with reasons for each file.
	 *
	 * @param ?string $name
	 * @return array
	 */
	public function deleteFiles($name = null): array
	{
		// Compile the list of files to delete.
		$list = [];
		if ($name !== null)
		{
			if (array_key_exists($name, self::CLEANUP_LIST))
			{
				$list[$name] = 'deleted';
			}
			else
			{
				throw new InvalidRequest;
			}
		}
		else
		{
			$list = $this->checkFiles();
		}

		if (!count($list))
		{
			throw new TargetNotFound('msg_cleanup_list_empty');
		}

		// Delete each file or directory.
		$result = [];
		foreach ($list as $name => $reason)
		{
			$normalized_path = \RX_BASEDIR . rtrim($name, '/');
			if (str_ends_with($name, '/'))
			{
				$success = Storage::deleteDirectory($normalized_path, true);
			}
			else
			{
				$success = Storage::delete($normalized_path);
			}

			if (!$success && Storage::exists($normalized_path))
			{
				if (!Storage::isWritable($normalized_path) || !Storage::isWritable(dirname($normalized_path)))
				{
					$result[$name] = 'PERMISSION';
				}
				elseif (Storage::isSymlink($normalized_path))
				{
					$result[$name] = 'SYMLINK';
				}
				else
				{
					$result[$name] = 'UNKNOWN';
				}
			}
		}

		return $result;
	}

	/**
	 * List of files and directories to clean up.
	 */
	public const CLEANUP_LIST = [

		// Unnecessary files in the root directory
		'composer.json' => 'moved:common/composer.json',
		'composer.lock' => 'moved:common/composer.lock',
		'Gruntfile.js' => 'deleted:xe',
		'gulpfile.babel.js' => 'deleted:xe',
		'package.json' => 'deleted:xe',
		'.babelrc' => 'deleted:xe',
		'.jshintignore' => 'deleted:xe',
		'.jshintrc' => 'deleted:xe',
		'.travis.yml' => 'deleted:xe',

		// Deleted files and directories
		'addons/blogapi/' => 'deleted:xe',
		'addons/captcha/' => 'deleted:xe',
		'addons/captcha_member/' => 'deleted:xe',
		'addons/member_communication/' => 'deleted:xe',
		'addons/mobile/' => 'deleted:xe',
		'addons/openid_delegation_id/' => 'deleted:xe',
		'classes/cache/CacheApc.class.php' => 'deleted:xe',
		'classes/cache/CacheFile.class.php' => 'deleted:xe',
		'classes/cache/CacheMemcache.class.php' => 'deleted:xe',
		'classes/cache/CacheWincache.class.php' => 'deleted:xe',
		'classes/db/DBCubrid.class.php' => 'deleted:xe',
		'classes/db/DBFirebird.class.php' => 'deleted:xe',
		'classes/db/DBMssql.class.php' => 'deleted:xe',
		'classes/db/DBMysql.class.php' => 'deleted:xe',
		'classes/db/DBMysql_innodb.class.php' => 'deleted:xe',
		'classes/db/DBMysqli.class.php' => 'deleted:xe',
		'classes/db/DBMysqli_innodb.class.php' => 'deleted:xe',
		'classes/db/DBPostgresql.class.php' => 'deleted:xe',
		'classes/db/DBSqlite2.class.php' => 'deleted:xe',
		'classes/db/DBSqlite3_pdo.class.php' => 'deleted:xe',
		'classes/db/queryparts/' => 'deleted:xe',
		'classes/object/BaseObject.class.php' => 'deleted:xe',
		'classes/security/conf/' => 'deleted:xe',
		'classes/security/htmlpurifier/' => 'deleted:xe',
		'classes/security/phphtmlparser/' => 'deleted:xe',
		'classes/xml/XmlQueryParser.class.php' => 'deleted:xe',
		'classes/xml/xmlquery/' => 'deleted:xe',
		'common/css/mobile.css' => 'deleted',
		'common/css/mobile.min.css' => 'deleted',
		'common/css/rhymix.less' => 'deleted',
		'common/css/xe.css' => 'deleted:xe',
		'common/css/xe.min.css' => 'deleted:xe',
		'common/framework/drivers/cache/wincache.php' => 'deleted',
		'common/framework/drivers/cache/xcache.php' => 'deleted',
		'common/img/flvplayer.swf' => 'deleted:xe',
		'common/js/URI.js' => 'moved:common/js/plugins/uri/URI.min.js',
		'common/js/blankshield.min.js' => 'moved:common/js/plugins/blankshield/blankshield.min.js',
		'common/js/html5.js' => 'deleted',
		'common/js/jquery-1.12.4.min.js' => 'deleted',
		'common/js/jquery-1.12.4.js' => 'deleted',
		'common/js/jquery-1.x.js' => 'deleted',
		'common/js/jquery-1.x.min.js' => 'deleted',
		'common/js/jquery.js' => 'deleted',
		'common/js/jquery.min.js' => 'deleted',
		'common/js/respond.min.js' => 'deleted',
		'common/js/x.min.js' => 'deleted',
		'common/js/xe.js' => 'deleted:xe',
		'common/js/xe.min.js' => 'deleted:xe',
		'common/js/plugins/jquery.migrate/jquery-migrate-1.4.1.js' => 'deleted',
		'common/js/plugins/spectrum/bower.json' => 'deleted',
		'common/js/plugins/spectrum/Gruntfile.js' => 'deleted',
		'common/js/plugins/spectrum/index.html' => 'deleted',
		'common/js/plugins/spectrum/package.json' => 'deleted',
		'common/js/plugins/spectrum/build/' => 'deleted',
		'common/js/plugins/spectrum/docs/' => 'deleted',
		'common/js/plugins/spectrum/example/' => 'deleted',
		'common/js/plugins/spectrum/test/' => 'deleted',
		'common/js/plugins/uploader/' => 'deleted:xe',
		'common/lang/lang.info' => 'deleted:xe',
		'common/libraries/bmp.php' => 'deleted',
		'common/manual/server_config/rhymix-nginx-help.md' => 'deleted',
		'common/tpl/mobile_layout.html' => 'deleted:xe',
		'common/tpl/redirect.html' => 'deleted:xe',
		'common/tpl/sitelock.html' => 'deleted:xe',
		'config/func.inc.php' => 'deleted:xe',
		'config/package.inc.php' => 'deleted:xe',
		'doxygen/' => 'deleted:xe',
		'layouts/xedition/css/layout.min.css' => 'deleted',
		'layouts/xedition/css/webfont.min.css' => 'deleted',
		'layouts/xedition/css/welcome.min.css' => 'deleted',
		'layouts/xedition/css/widget.login.min.css' => 'deleted',
		'layouts/xedition/css/xeicon.min.css' => 'deleted',
		'layouts/xedition/js/layout.min.js' => 'deleted',
		'layouts/xedition/js/welcome.min.js' => 'deleted',
		'libs/' => 'deleted:xe',
		'modules/admin/ruleset/toggleFavorite.xml' => 'deleted',
		'modules/admin/tpl/config_ftp.html' => 'deleted',
		'modules/admin/tpl/css/admin.min.css' => 'deleted',
		'modules/admin/tpl/js/config.min.js' => 'deleted',
		'modules/admin/tpl/js/admin.min.js' => 'deleted',
		'modules/admin/tpl/js/menu_setup.min.js' => 'deleted',
		'modules/admin/tpl/img/bgDragable.gif' => 'deleted',
		'modules/admin/tpl/img/faviconSample.png' => 'deleted',
		'modules/admin/tpl/img/mobiconSample.png' => 'deleted',
		'modules/admin/tpl/check_env.html' => 'deleted',
		'modules/admin/tpl/config_general.html' => 'deleted',
		'modules/admin/tpl/css/admin.bootstrap.min.css' => 'deleted',
		'modules/admin/tpl/css/admin_en.css' => 'deleted:xe',
		'modules/admin/tpl/css/admin_jp.css' => 'deleted:xe',
		'modules/admin/tpl/css/admin_ko.css' => 'deleted:xe',
		'modules/autoinstall/ruleset/' => 'deleted:xe',
		'modules/autoinstall/tpl/filter/uninstall_package.xml' => 'deleted:xe',
		'modules/board/board.wap.php' => 'deleted:xe',
		'modules/counter/queries/deleteSiteCounter.xml' => 'deleted:xe',
		'modules/counter/queries/deleteSiteCounterLog.xml' => 'deleted:xe',
		'modules/counter/queries/getSiteCounterStatus.xml' => 'deleted:xe',
		'modules/counter/queries/getSiteCounterStatusDays.xml' => 'deleted:xe',
		'modules/counter/queries/getSiteStartLogDate.xml' => 'deleted:xe',
		'modules/counter/queries/getSiteTodayStatus.xml' => 'deleted:xe',
		'modules/counter/queries/insertSiteTodayStatus.xml' => 'deleted:xe',
		'modules/counter/queries/updateSiteCounterPageview.xml' => 'deleted:xe',
		'modules/counter/queries/updateSiteCounterUnique.xml' => 'deleted:xe',
		'modules/counter/queries/updateSiteTotalCounterUnique.xml' => 'deleted:xe',
		'modules/editor/components/emoticon/tpl/popup.less' => 'deleted',
		'modules/editor/components/emoticon/tpl/popup.css' => 'deleted',
		'modules/editor/components/image_gallery/tpl/gallery.min.js' => 'deleted',
		'modules/editor/components/image_gallery/tpl/list_gallery.min.js' => 'deleted',
		'modules/editor/components/image_gallery/tpl/popup.min.css' => 'deleted',
		'modules/editor/components/image_gallery/tpl/popup.min.js' => 'deleted',
		'modules/editor/components/image_gallery/tpl/slide_gallery.min.css' => 'deleted',
		'modules/editor/components/image_gallery/tpl/slide_gallery.min.js' => 'deleted',
		'modules/editor/skins/ckeditor/js/default.js' => 'deleted',
		'modules/editor/skins/ckeditor/js/default.min.js' => 'deleted',
		'modules/editor/skins/ckeditor/js/xe_interface.js' => 'deleted',
		'modules/editor/skins/ckeditor/js/xe_interface.min.js' => 'deleted',
		'modules/editor/skins/ckeditor/js/xe_textarea.min.js' => 'deleted',
		'modules/editor/skins/simpleeditor/css/simpleeditor.less' => 'deleted',
		'modules/editor/skins/xpresseditor/' => 'deleted:xe',
		'modules/editor/styles/' => 'deleted:xe',
		'modules/editor/tpl/js/editor.js' => 'deleted:xe',
		'modules/editor/tpl/js/editor.min.js' => 'deleted:xe',
		'modules/editor/tpl/js/swfupload.js' => 'deleted:xe',
		'modules/editor/tpl/js/swfupload.min.js' => 'deleted:xe',
		'modules/editor/tpl/js/uploader.js' => 'deleted:xe',
		'modules/editor/tpl/js/uploader.min.js' => 'deleted:xe',
		'modules/editor/tpl/preview.html' => 'deleted',
		'modules/file/ruleset/imageResize.xml' => 'deleted',
		'modules/importer/tpl/js/importer_admin.min.js' => 'deleted',
		'modules/install/ruleset/config.xml' => 'deleted:xe',
		'modules/install/ruleset/cubrid.xml' => 'deleted:xe',
		'modules/install/ruleset/firebird.xml' => 'deleted:xe',
		'modules/install/ruleset/installFtpInfo.xml' => 'deleted:xe',
		'modules/install/ruleset/mssql.xml' => 'deleted:xe',
		'modules/install/ruleset/mysql.xml' => 'deleted:xe',
		'modules/install/ruleset/postgresql.xml' => 'deleted:xe',
		'modules/install/ruleset/sqlite.xml' => 'deleted:xe',
		'modules/install/script/welcome_content/welcome_content_de.html' => 'deleted',
		'modules/install/script/welcome_content/welcome_content_en.html' => 'deleted',
		'modules/install/script/welcome_content/welcome_content_es.html' => 'deleted',
		'modules/install/script/welcome_content/welcome_content_fr.html' => 'deleted',
		'modules/install/script/welcome_content/welcome_content_jp.html' => 'deleted',
		'modules/install/script/welcome_content/welcome_content_ko.html' => 'deleted',
		'modules/install/script/welcome_content/welcome_content_mn.html' => 'deleted',
		'modules/install/script/welcome_content/welcome_content_ru.html' => 'deleted',
		'modules/install/script/welcome_content/welcome_content_tr.html' => 'deleted',
		'modules/install/script/welcome_content/welcome_content_vi.html' => 'deleted',
		'modules/install/script/welcome_content/welcome_content_zh-CN.html' => 'deleted',
		'modules/install/script/welcome_content/welcome_content_zh-TW.html' => 'deleted',
		'modules/install/tpl/admin_form.html' => 'deleted:xe',
		'modules/install/tpl/after_upload_config_image.html' => 'deleted:xe',
		'modules/install/tpl/config_form.html' => 'deleted:xe',
		'modules/install/tpl/form.cubrid.html' => 'deleted:xe',
		'modules/install/tpl/form.mssql.html' => 'deleted:xe',
		'modules/install/tpl/form.mysql.html' => 'deleted:xe',
		'modules/install/tpl/form.mysql_innodb.html' => 'deleted:xe',
		'modules/install/tpl/form.mysqli.html' => 'deleted:xe',
		'modules/install/tpl/form.mysqli_innodb.html' => 'deleted:xe',
		'modules/install/tpl/ftp.html' => 'deleted:xe',
		'modules/install/tpl/select_db.html' => 'deleted:xe',
		'modules/install/tpl/js/install_admin.js' => 'deleted:xe',
		'modules/integration_search/skins/default/trackback.html' => 'deleted',
		'modules/member/skins/default/filter/find_member_account_by_question.xml' => 'deleted:xe',
		'modules/module/schemas/site_admin.xml' => 'deleted',
		'modules/module/tpl/css/module_admin.less' => 'deleted',
		'modules/page/page.wap.php' => 'deleted:xe',
		'modules/page/tpl/css/mpage.css' => 'deleted',
		'modules/poll/skins/default/css/poll.min.css' => 'deleted',
		'modules/poll/skins/simple/css/poll.min.css' => 'deleted',
		'modules/poll/tpl/css/poll.min.css' => 'deleted',
		'modules/poll/tpl/js/poll.min.js' => 'deleted',
		'modules/poll/tpl/js/poll_admin.min.js' => 'deleted',
		'modules/rss/tpl/atom10.html' => 'deleted',
		'modules/rss/tpl/display.html' => 'deleted',
		'modules/rss/tpl/index.html' => 'deleted',
		'modules/rss/tpl/rss10.html' => 'deleted',
		'modules/rss/tpl/rss20.html' => 'deleted',
		'modules/rss/tpl/top_refresh.html' => 'deleted',
		'modules/rss/tpl/xe_rss.html' => 'deleted',
		'modules/spamfilter/spamfilter.lib.php' => 'deleted',
		'modules/spamfilter/ruleset/' => 'deleted',
		'phpDoc/' => 'deleted:xe',
		'tests/unit/classes/template/' => 'deleted',
		'tests/unit/classes/TemplateHandlerTest.php' => 'deleted',
		'tools/dbxml_validator/' => 'deleted:xe',
		'tools/korea_ip_ranges/' => 'deleted',
		'tools/phpDoc/' => 'deleted',
		'tools/README.md' => 'deleted:xe',

		// Deleted lang.xml
		'common/lang/lang.xml' => 'deleted:xmllang',
		'layouts/xedition/lang/lang.xml' => 'deleted:xmllang',
		'modules/addon/lang/lang.xml' => 'deleted:xmllang',
		'modules/admin/lang/lang.xml' => 'deleted:xmllang',
		'modules/advanced_mailer/lang/lang.xml' => 'deleted:xmllang',
		'modules/autoinstall/lang/lang.xml' => 'deleted:xmllang',
		'modules/board/lang/lang.xml' => 'deleted:xmllang',
		'modules/comment/lang/lang.xml' => 'deleted:xmllang',
		'modules/communication/lang/lang.xml' => 'deleted:xmllang',
		'modules/counter/lang/lang.xml' => 'deleted:xmllang',
		'modules/document/lang/lang.xml' => 'deleted:xmllang',
		'modules/editor/lang/lang.xml' => 'deleted:xmllang',
		'modules/editor/skins/ckeditor/lang/lang.xml' => 'deleted:xmllang',
		'modules/file/lang/lang.xml' => 'deleted:xmllang',
		'modules/importer/lang/lang.xml' => 'deleted:xmllang',
		'modules/install/lang/lang.xml' => 'deleted:xmllang',
		'modules/integration_search/lang/lang.xml' => 'deleted:xmllang',
		'modules/krzip/lang/lang.xml' => 'deleted:xmllang',
		'modules/layout/lang/lang.xml' => 'deleted:xmllang',
		'modules/member/lang/lang.xml' => 'deleted:xmllang',
		'modules/menu/lang/lang.xml' => 'deleted:xmllang',
		'modules/message/lang/lang.xml' => 'deleted:xmllang',
		'modules/module/lang/lang.xml' => 'deleted:xmllang',
		'modules/ncenterlite/lang/lang.xml' => 'deleted:xmllang',
		'modules/page/lang/lang.xml' => 'deleted:xmllang',
		'modules/point/lang/lang.xml' => 'deleted:xmllang',
		'modules/poll/lang/lang.xml' => 'deleted:xmllang',
		'modules/rss/lang/lang.xml' => 'deleted:xmllang',
		'modules/session/lang/lang.xml' => 'deleted:xmllang',
		'modules/spamfilter/lang/lang.xml' => 'deleted:xmllang',
		'modules/syndication/lang/lang.xml' => 'deleted:xmllang',
		'modules/trash/lang/lang.xml' => 'deleted:xmllang',
		'modules/widget/lang/lang.xml' => 'deleted:xmllang',

		// Lowercase version of case-sensitive filenames
		'common/framework/cache.php' => 'case',
		'common/framework/calendar.php' => 'case',
		'common/framework/config.php' => 'case',
		'common/framework/db.php' => 'case',
		'common/framework/datetime.php' => 'case',
		'common/framework/debug.php' => 'case',
		'common/framework/exception.php' => 'case',
		'common/framework/formatter.php' => 'case',
		'common/framework/image.php' => 'case',
		'common/framework/korea.php' => 'case',
		'common/framework/lang.php' => 'case',
		'common/framework/mime.php' => 'case',
		'common/framework/mail.php' => 'case',
		'common/framework/pagination.php' => 'case',
		'common/framework/password.php' => 'case',
		'common/framework/push.php' => 'case',
		'common/framework/router.php' => 'case',
		'common/framework/sms.php' => 'case',
		'common/framework/security.php' => 'case',
		'common/framework/session.php' => 'case',
		'common/framework/storage.php' => 'case',
		'common/framework/timer.php' => 'case',
		'common/framework/ua.php' => 'case',
		'common/framework/url.php' => 'case',
		'common/framework/drivers/cacheinterface.php' => 'case',
		'common/framework/drivers/mailinterface.php' => 'case',
		'common/framework/drivers/pushinterface.php' => 'case',
		'common/framework/drivers/smsinterface.php' => 'case',
		'common/framework/exceptions/dberror.php' => 'case',
		'common/framework/exceptions/featuredisabled.php' => 'case',
		'common/framework/exceptions/invalidrequest.php' => 'case',
		'common/framework/exceptions/mustlogin.php' => 'case',
		'common/framework/exceptions/notpermitted.php' => 'case',
		'common/framework/exceptions/queryerror.php' => 'case',
		'common/framework/exceptions/securityviolation.php' => 'case',
		'common/framework/exceptions/targetnotfound.php' => 'case',
		'common/framework/filters/filecontentfilter.php' => 'case',
		'common/framework/filters/filenamefilter.php' => 'case',
		'common/framework/filters/htmlfilter.php' => 'case',
		'common/framework/filters/ipfilter.php' => 'case',
		'common/framework/filters/mediafilter.php' => 'case',
		'common/framework/helpers/confighelper.php' => 'case',
		'common/framework/helpers/dbhelper.php' => 'case',
		'common/framework/helpers/dbresulthelper.php' => 'case',
		'common/framework/helpers/dbstmthelper.php' => 'case',
		'common/framework/helpers/sessionhelper.php' => 'case',
		'common/framework/parsers/baseparser.php' => 'case',
		'common/framework/parsers/configparser.php' => 'case',
		'common/framework/parsers/dbqueryparser.php' => 'case',
		'common/framework/parsers/dbtableparser.php' => 'case',
		'common/framework/parsers/editorcomponentparser.php' => 'case',
		'common/framework/parsers/langparser.php' => 'case',
		'common/framework/parsers/moduleactionparser.php' => 'case',
		'common/framework/parsers/moduleinfoparser.php' => 'case',
		'common/framework/parsers/xmlrpcparser.php' => 'case',
		'common/framework/parsers/dbquery/columnread.php' => 'case',
		'common/framework/parsers/dbquery/columnwrite.php' => 'case',
		'common/framework/parsers/dbquery/condition.php' => 'case',
		'common/framework/parsers/dbquery/conditiongroup.php' => 'case',
		'common/framework/parsers/dbquery/emptystring.php' => 'case',
		'common/framework/parsers/dbquery/groupby.php' => 'case',
		'common/framework/parsers/dbquery/indexhint.php' => 'case',
		'common/framework/parsers/dbquery/navigation.php' => 'case',
		'common/framework/parsers/dbquery/nullvalue.php' => 'case',
		'common/framework/parsers/dbquery/orderby.php' => 'case',
		'common/framework/parsers/dbquery/query.php' => 'case',
		'common/framework/parsers/dbquery/table.php' => 'case',
		'common/framework/parsers/dbquery/variablebase.php' => 'case',
		'common/framework/parsers/dbtable/column.php' => 'case',
		'common/framework/parsers/dbtable/constraint.php' => 'case',
		'common/framework/parsers/dbtable/index.php' => 'case',
		'common/framework/parsers/dbtable/table.php' => 'case',
		'modules/member/controllers/device.php' => 'case',

		// Vendor directory
		'vendor/' => 'moved:common/vendor/',
	];
}
