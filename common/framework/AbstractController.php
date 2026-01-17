<?php

namespace Rhymix\Framework;

use Rhymix\Modules\Module\Models\ModuleInfo as ModuleInfoModel;
use Rhymix\Modules\Module\Models\ModuleConfig as ModuleConfigModel;
use Rhymix\Modules\Module\Models\Permission as PermissionModel;
use AddonController;
use BaseObject;
use Context;
use FileHandler;
use LayoutAdminModel;
use LayoutModel;
use Mobile;
use MessageView;
use ModuleModel;

/**
 * This class is extended by the controller classes of all modules.
 *
 * It is designed to be a drop-in replacement for XE's ModuleObject class.
 * For backward compatibility, it extends BaseObject as well.
 * The ModuleObject class is provided as an alias to this class.
 */
abstract class AbstractController extends BaseObject
{
	/**
	 * The name of the current module.
	 */
	public string $module = '';

	/**
	 * The module's installation path, relative to Rhymix basedir.
	 */
	public string $module_path = '';

	/**
	 * The current module instance's module_srl, if any.
	 */
	public ?int $module_srl = null;

	/**
	 * The current module instance's prefix (mid), if any.
	 */
	public string $mid = '';

	/**
	 * The requested action.
	 */
	public string $act = '';

	/**
	 * Information about the current module (instance or not).
	 */
	public ?ModuleInfoModel $module_info = null;

	/**
	 * The current module's global configuration.
	 */
	public ?object $module_config = null;

	/**
	 * The current module's XML parsed tree, containing information such as permissions.
	 */
	public ?object $xml_info = null;

	/**
	 * The current's module's skin configuration (if any).
	 */
	public ?object $skin_vars = null;

	/**
	 * Reference to the current visitor's information.
	 */
	public Helpers\SessionHelper $user;

	/**
	 * Reference to the effective Permission object.
	 */
	public ?PermissionModel $grant = null;

	/**
	 * Reference to the current request.
	 */
	public Request $request;

	/**
	 * Reference to the current module's response.
	 */
	public ?object $response = null;

	/**
	 * The template directory name.
	 */
	public ?string $template_path = null;

	/**
	 * The template filename.
	 */
	public ?string $template_file = null;

	/**
	 * The layout directory name.
	 */
	public ?string $layout_path = null;

	/**
	 * The layout filename.
	 */
	public ?string $layout_file = null;

	/**
	 * The edited layout (legacy faceOff) filename.
	 */
	public ?string $edited_layout_file = null;

	/*
	 * These attributes are kept for backward compatibility.
	 */
	public $ajaxRequestMethod = ['XMLRPC', 'JSON'];
	public $origin_module_info = null;
	public $stop_proc = false;

	/*
	 * Singleton instances are cached here.
	 */
	protected static array $_instances = [];

	/**
	 * Constructor.
	 *
	 * The constructor is kept public for backward compatibility,
	 * but it should not be called directly.
	 * Use getInstance() to obtain a singleton instead.
	 *
	 * @param int $error
	 * @param string $message
	 * @return void
	 */
	public function __construct($error = 0, $message = 'success')
	{
		parent::__construct($error, $message);
	}

	/**
	 * Get a singleton instance of a module controller.
	 *
	 * @param ?string $module_hint (optional)
	 * @return static
	 */
	public static function getInstance(?string $module_hint = null)
	{
		// If an instance already exists, return it.
		$class_name = static::class;
		if (isset(self::$_instances[$class_name]))
		{
			return self::$_instances[$class_name];
		}

		// Get some information about the module to which this controller belongs.
		if ($module_hint)
		{
			$module_path = \RX_BASEDIR . 'modules/' . $module_hint . '/';
			$module = $module_hint;
		}
		else
		{
			$class_filename = (new \ReflectionClass($class_name))->getFileName();
			preg_match('!^(.+[/\\\\]modules[/\\\\]([^/\\\\]+)[/\\\\])!', $class_filename, $matches);
			$module_path = $matches[1] ?? null;
			$module = $matches[2] ?? null;
		}

		// Create a new instance.
		$obj = new $class_name;

		// Populate default properties.
		if ($module_path)
		{
			$obj->setModulePath($module_path);
		}
		if ($module)
		{
			$obj->setModule($module);
		}
		$user_info = Context::get('logged_info');
		if (!($user_info instanceof Helpers\SessionHelper))
		{
			$user_info = Session::getMemberInfo();
		}
		$obj->user = $user_info;
		$obj->request = \Context::getCurrentRequest();

		// Return the instance.
		return self::$_instances[$class_name] = $obj;
	}

	/**
	 * Set the name of the current module.
	 *
	 * @param string $module
	 * @return $this
	 */
	public function setModule(string $module): self
	{
		$this->module = $module;
		return $this;
	}

	/**
	 * Set the installation path of the current module.
	 *
	 * @param string $path
	 * @return $this
	 */
	public function setModulePath(string $path): self
	{
		$this->module_path = ends_with($path, '/') ? $path : ($path . '/');
		return $this;
	}

	/**
	 * Set the requested action name.
	 *
	 * @param string $act
	 * @return $this
	 */
	public function setAct(string $act): self
	{
		$this->act = $act;
		return $this;
	}

	/**
	 * Set module information.
	 *
	 * A constroller instance is only usable after this method is called.
	 * Normally, it is called by ModuleHandler automatically.
	 *
	 * @param ModuleInfoModel $module_info
	 * @param object $xml_info
	 * @return $this
	 */
	public function setModuleInfo(ModuleInfoModel $module_info, object $xml_info): self
	{
		// Set default attributes.
		$this->mid = strval($module_info->mid);
		$this->module_srl = $module_info->module_srl ?? null;
		$this->module_info = $module_info;
		$this->origin_module_info = $module_info;
		$this->module_config = ModuleConfigModel::getModuleConfig($this->module);
		$this->xml_info = $xml_info;
		$this->skin_vars = $module_info->skin_vars ?? null;

		// Check permission.
		$grant = $this->_checkPermission();
		if ($grant)
		{
			$this->grant = $grant;
			Context::set('grant', $grant);
		}
		else
		{
			$this->stop('msg_not_permitted');
			return $this;
		}

		// Set admin layout if the act contains 'Admin'.
		if (preg_match('/^disp[A-Z][a-z0-9\_]+Admin/', $this->act))
		{
			if (config('view.manager_layout') === 'admin')
			{
				$this->setLayoutPath('modules/admin/tpl');
				$this->setLayoutFile('layout');
			}
			else
			{
				// We do this to load admin CSS and JS files, such as admin.bootstrap.css.
				// This might be better handled elsewhere in the future.
				$oTemplate = new Template('modules/admin/tpl', '_admin_common.html');
				$oTemplate->compile();
			}
		}

		// Execute the init() method, if it exists, for backward compatibility.
		if (method_exists($this, 'init'))
		{
			try
			{
				$this->init();
			}
			catch (Exception $e)
			{
				$this->stop($e->getMessage(), -2);
				$this->add('rx_error_location', $e->getUserFileAndLine());
			}
		}

		return $this;
	}

	/**
	 * Execute the requested action.
	 *
	 * @return bool
	 */
	public function proc(): bool
	{
		// Stop if stop_proc has been set.
		if ($this->stop_proc)
		{
			return false;
		}

		// Check mobile status.
		$is_mobile = Mobile::isFromMobilePhone();

		// Dispatch event.
		$event_output = Event::trigger('moduleObject.proc', 'before', $this);
		if (!$event_output->toBool())
		{
			$this->setError($event_output->getError());
			$this->setMessage($event_output->getMessage());
			return false;
		}

		// Addon execution point: 'before_module_proc'.
		$called_position = 'before_module_proc';
		$addon_file = \AddonController::getInstance()->getCacheFilePath($is_mobile ? 'mobile' : 'pc');
		if (FileHandler::exists($addon_file))
		{
			include $addon_file;
		}

		// Check mobile status again, in case an event handler or addon changed it.
		$is_mobile = Mobile::isFromMobilePhone();

		// Perform action if it exists
		if (isset($this->xml_info->action->{$this->act}) && method_exists($this, $this->act))
		{
			// Set layout and template paths if module configuration specifies them.
			if (isset($this->module_info->skin) && $this->module_info->module === $this->module && strpos($this->act, 'Admin') === false)
			{
				$use_default_skin = $this->module_info->{$is_mobile ? 'is_mskin_fix' : 'is_skin_fix'} === 'N';
				if (!$this->getTemplatePath() || $use_default_skin)
				{
					$this->setLayoutAndTemplatePaths($is_mobile ? 'M' : 'P', $this->module_info);
				}
				ModuleModel::syncSkinInfoToModuleInfo($this->module_info);
				Context::set('module_info', $this->module_info);
			}

			// Dispatch event before specific action.
			$event_name = sprintf('act:%s.%s', $this->module, $this->act);
			$event_output = Event::trigger($event_name, 'before', $this);
			if (!$event_output->toBool())
			{
				$this->setError($event_output->getError());
				$this->setMessage($event_output->getMessage());
				return false;
			}

			// Run!
			try
			{
				$output = $this->{$this->act}();
			}
			catch (Exception $e)
			{
				$output = new BaseObject(-2, $e->getMessage());
				$output->add('rx_error_location', $e->getUserFileAndLine());
			}

			// Dispatch event after specific action.
			Event::trigger($event_name, 'after', $output);
		}
		else
		{
			return false;
		}

		// Check return value of action.
		if ($output instanceof BaseObject)
		{
			$this->setError($output->getError());
			$this->setMessage($output->getMessage());

			// Copy error location if provided.
			if ($output->getError() && $output->get('rx_error_location'))
			{
				$this->add('rx_error_location', $output->get('rx_error_location'));
			}

			// Make a copy of original output, in case we need it later.
			$original_output = clone $output;
		}
		else
		{
			$original_output = null;
		}

		// Dispatch event.
		$event_output = Event::trigger('moduleObject.proc', 'after', $this);
		if (!$event_output->toBool())
		{
			$this->setError($event_output->getError());
			$this->setMessage($event_output->getMessage());

			// Copy error location if provided.
			if ($event_output->get('rx_error_location'))
			{
				$this->add('rx_error_location', $event_output->get('rx_error_location'));
			}
			return false;
		}

		// Addon execution point: 'after_module_proc'.
		$called_position = 'after_module_proc';
		$addon_file = \AddonController::getInstance()->getCacheFilePath($is_mobile ? 'mobile' : 'pc');
		if (FileHandler::exists($addon_file))
		{
			include $addon_file;
		}

		// If the original output was an error, we return an error regardless of what happened later.
		if ($original_output instanceof BaseObject && !$original_output->toBool())
		{
			return false;
		}

		// Otherwise, copy error information from the event or addon output.
		if ($output instanceof BaseObject && $output->getError())
		{
			$this->setError($output->getError());
			$this->setMessage($output->getMessage());
			if ($output->get('rx_error_location'))
			{
				$this->add('rx_error_location', $output->get('rx_error_location'));
			}
			return false;
		}

		// Execute API methods of the module (deprecated feature).
		if (isset($this->module_info->module_type) && in_array($this->module_info->module_type, ['view', 'mobile']))
		{
			if (in_array(Context::getResponseMethod(), ['XMLRPC', 'JSON']))
			{
				$oAPI = getAPI($this->module_info->module);
				if ($oAPI && method_exists($oAPI, $this->act))
				{
					$oAPI->{$this->act}($this);
				}
			}
		}

		return true;
	}

	/**
	 * Stop processing this module instance.
	 *
	 * @param string $message
	 * @param int $error_code
	 * @return $this
	 */
	public function stop(string $message, int $error_code = -1): self
	{
		if ($this->stop_proc)
		{
			return $this;
		}

		// Flag to stop further processing.
		$this->stop_proc = true;

		// Set the error code and message.
		$this->setError($error_code ?: -1);
		$this->setMessage($message);

		// Get backtrace for debugging.
		$backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 1);
		$caller = array_shift($backtrace);
		$location = $caller['file'] . ':' . $caller['line'];

		// Display an error message using the message module.
		$oMessageObject = MessageView::getInstance();
		$oMessageObject->setError(-1);
		$oMessageObject->setMessage($message);
		$oMessageObject->dispMessage('', $location);

		$this->setHttpStatusCode($oMessageObject->getHttpStatusCode());
		$this->setTemplatePath($oMessageObject->getTemplatePath());
		$this->setTemplateFile($oMessageObject->getTemplateFile());
		return $this;
	}

	/**
	 * Set the redirect URL, with optional output.
	 *
	 * If an array is given as $url, it is converted to a URL string.
	 * This automatic conversion behavior is considered legacy,
	 * and using a string is strongly recommended.
	 *
	 * @param string|array $url
	 * @param ?object $output
	 * @return ?object
	 */
	public function setRedirectUrl($url = \RX_BASEURL, $output = null): ?object
	{
		$this->add('redirect_url', is_array($url) ? getNotEncodedUrl($url) : $url);

		if ($output !== NULL && is_object($output))
		{
			return $output;
		}
		else
		{
			return $this;
		}
	}

	/**
	 * Get the redirect URL.
	 *
	 * @return ?string
	 */
	public function getRedirectUrl(): ?string
	{
		return $this->get('redirect_url');
	}

	/**
	 * Set the template directory path.
	 *
	 * @param ?string $path
	 * @return $this
	 */
	public function setTemplatePath(?string $path): self
	{
		if (!$path)
		{
			return $this;
		}
		if (!preg_match('!^(?:\\.?/|[A-Z]:[\\\\/]|\\\\\\\\)!i', $path))
		{
			$path = './' . $path;
		}
		if (!ends_with($path, '/'))
		{
			$path .= '/';
		}
		$this->template_path = $path;
		return $this;
	}

	/**
	 * Get the template directory path.
	 *
	 * @return ?string
	 */
	public function getTemplatePath(): ?string
	{
		return $this->template_path;
	}

	/**
	 * Set the template filename.
	 *
	 * @param ?string $filename
	 * @return $this
	 */
	public function setTemplateFile(?string $filename): self
	{
		if ($filename !== null)
		{
			$this->template_file = $filename;
		}
		return $this;
	}

	/**
	 * Get the template filename.
	 *
	 * @return ?string
	 */
	public function getTemplateFile(): ?string
	{
		return $this->template_file;
	}

	/**
	 * Set the layout directory path.
	 *
	 * @param ?string $path
	 * @return $this
	 */
	public function setLayoutPath(?string $path): self
	{
		if (!$path)
		{
			return $this;
		}
		if (!preg_match('!^(?:\\.?/|[A-Z]:[\\\\/]|\\\\\\\\)!i', $path))
		{
			$path = './' . $path;
		}
		if (!ends_with($path, '/'))
		{
			$path .= '/';
		}
		$this->layout_path = $path;
		return $this;
	}

	/**
	 * Get the layout directory path.
	 *
	 * The return type of this method is intentionally left undefined
	 * because of conflict with LayoutModel::getLayoutPath().
	 *
	 * @return ?string
	 */
	public function getLayoutPath()
	{
		return $this->layout_path;
	}

	/**
	 * Set the layout filename.
	 *
	 * @param ?string $filename
	 * @return $this
	 */
	public function setLayoutFile(?string $filename): self
	{
		if ($filename !== null)
		{
			$this->layout_file = $filename;
		}
		return $this;
	}

	/**
	 * Get the layout filename.
	 *
	 * @return ?string
	 */
	public function getLayoutFile(): ?string
	{
		return $this->layout_file;
	}

	/**
	 * Set the edited layout filename.
	 *
	 * @param ?string $filename
	 * @return $this
	 */
	public function setEditedLayoutFile(?string $filename): self
	{
		if ($filename !== null)
		{
			$this->edited_layout_file = $filename;
		}
		return $this;
	}

	/**
	 * Get the edited layout filename.
	 *
	 * @return ?string
	 */
	public function getEditedLayoutFile(): ?string
	{
		return $this->edited_layout_file;
	}

	/**
	 * Automatically set layout and template paths based on module configuration.
	 *
	 * @param string $mode 'P' or 'M'
	 * @param object $config
	 * @return void
	 */
	public function setLayoutAndTemplatePaths(string $mode, object $config): void
	{
		// Set the layout path.
		if ($mode === 'P')
		{
			$layout_srl = $config->layout_srl ?? 0;
			if ($layout_srl == -1)
			{
				$layout_srl = LayoutAdminModel::getInstance()->getSiteDefaultLayout('P');
			}

			if ($layout_srl > 0)
			{
				$layout_info = LayoutModel::getInstance()->getLayout($layout_srl);
				if ($layout_info)
				{
					$this->setLayoutPath($layout_info->path);
					if ($config->layout_srl > 0)
					{
						$this->module_info->layout_srl = $layout_srl;
					}
				}
			}
		}
		else
		{
			$layout_srl = $config->mlayout_srl ?? 0;
			if ($layout_srl == -2)
			{
				$layout_srl = $config->layout_srl ?: -1;
				if ($layout_srl == -1)
				{
					$layout_srl = LayoutAdminModel::getInstance()->getSiteDefaultLayout('P');
				}
			}
			elseif ($layout_srl == -1)
			{
				$layout_srl = LayoutAdminModel::getInstance()->getSiteDefaultLayout('M');
			}

			if ($layout_srl > 0)
			{
				$layout_info = LayoutModel::getInstance()->getLayout($layout_srl);
				if ($layout_info)
				{
					$this->setLayoutPath($layout_info->path);
					if ($config->mlayout_srl > 0)
					{
						$this->module_info->mlayout_srl = $layout_srl;
					}
				}
			}
		}

		// Set the skin path.
		if ($mode === 'P')
		{
			$skin = ($config->skin ?? '') ?: 'default';
			if ($skin === '/USE_DEFAULT/')
			{
				$skin = ModuleConfigModel::getModuleDefaultSkin($this->module, 'P') ?: 'default';
			}
			$template_path = sprintf('%sskins/%s', $this->module_path, $skin);
			if (!Storage::exists($template_path))
			{
				$template_path = sprintf('%sskins/%s', $this->module_path, 'default');
			}
		}
		else
		{
			$mskin = ($config->mskin ?? '') ?: 'default';
			if ($mskin === '/USE_DEFAULT/')
			{
				$mskin = ModuleConfigModel::getModuleDefaultSkin($this->module, 'M') ?: 'default';
			}

			if ($mskin === '/USE_RESPONSIVE/')
			{
				$skin = ($config->skin ?? '') ?: 'default';
				if ($skin === '/USE_DEFAULT/')
				{
					$skin = ModuleConfigModel::getModuleDefaultSkin($this->module, 'P') ?: 'default';
				}
				$template_path = sprintf('%sskins/%s', $this->module_path, $skin);
				if (!Storage::exists($template_path))
				{
					$template_path = sprintf('%sskins/%s', $this->module_path, 'default');
				}
			}
			else
			{
				$template_path = sprintf('%sm.skins/%s', $this->module_path, $mskin);
				if (!Storage::exists($template_path))
				{
					$template_path = sprintf("%sm.skins/%s/", $this->module_path, 'default');
				}
			}
		}
		$this->setTemplatePath($template_path);
	}

	/**
	 * Copy the response of another ModuleObject into this instance.
	 *
	 * @param self $instance
	 * @return void
	 */
	public function copyResponseFrom(self $instance)
	{
		// Copy error and status information.
		$this->error = $instance->getError();
		$this->message = $instance->getMessage();
		$this->httpStatusCode = $instance->getHttpStatusCode();

		// Copy template settings.
		$this->setTemplatePath($instance->getTemplatePath());
		$this->setTemplateFile($instance->getTemplateFile());
		$this->setLayoutPath($instance->getLayoutPath());
		$this->setLayoutFile($instance->getLayoutFile());
		$this->setEditedLayoutFile($instance->getEditedLayoutFile());

		// Copy all other variables: redirect URL, message type, etc.
		foreach ($instance->getVariables() as $key => $val)
		{
			$this->variables[$key] = $val;
		}
	}

	/**
	 * Set the template path for refresh.html
	 *
	 * @deprecated
	 * @return $this
	 */
	public function setRefreshPage()
	{
		$this->setTemplatePath('./common/tpl');
		$this->setTemplateFile('refresh');
		return $this;
	}

	/**
	 * Check permission for the combination of module, action and user.
	 *
	 * @return ?PermissionModel
	 */
	protected function _checkPermission(): ?PermissionModel
	{
		// The following section only applies to non-admin users.
		if (!$this->user->isAdmin())
		{
			// Get permission data for the current action.
			$permission = $permission = $this->xml_info->action->{$this->act}->permission ?? null;

			// If check_type/check_var is defined, we may want to check permissions
			// based on the target module(s) instead of the current module.
			// This is a legacy feature with questionable security implications,
			// so it is recommended to avoid using it in new modules.
			if ($permission && !empty($permission->check_var))
			{
				// check_type: document, comment, file, or module
				$check_type = strval($permission->check_type ?: 'module');

				// check_var: name of the Context variable that contains the target srl(s),
				// separated by comma or '|@|'.
				$check_var = Context::get($permission->check_var);
				if (is_scalar($check_var))
				{
					$target_srls = trim($check_var);
					if (empty($target_srls))
					{
						return null;
					}

					if (preg_match('/,|\|@\|/', $target_srls, $delimiter) && $delimiter[0])
					{
						$target_srls = array_map('intval', explode($delimiter[0], $target_srls));
					}
					else
					{
						$target_srls = [intval($target_srls)];
					}
				}
				elseif (is_array($check_var) && count($check_var))
				{
					$target_srls = array_map(function($var) {
						return intval(trim($var));
					}, $check_var);
				}
				else
				{
					return null;
				}

				// Check permissions based on target module(s) and current user.
				foreach ($target_srls as $target_srl)
				{
					$grant = PermissionModel::findByTargetType($check_type, $target_srl, $this->user);
					if (!$grant)
					{
						return null;
					}

					if (!$this->_applyPermission($grant, $failed_requirement))
					{
						$this->stop($this->_generatePermissionError($failed_requirement));
						return null;
					}
				}
			}
		}

		// Check permission based on the current module and current user.
		if (!isset($check_grant))
		{
			$grant = PermissionModel::get($this->module_info, $this->user, $this->xml_info);
		}

		if (!$this->_applyPermission($grant, $failed_requirement))
		{
			$this->stop($this->_generatePermissionError($failed_requirement));
			return null;
		}

		// If member action, grant access for login, signup, and member pages.
		if (preg_match('/^(disp|proc)(Member|Communication)[A-Z][a-zA-Z]+$/', $this->act))
		{
			$grant->access = true;
		}

		return $grant;
	}

	/**
	 * Apply the given set of permissions to the current user.
	 *
	 * This method returns true if permission is granted, and false otherwise.
	 * If permission is denied, the failed requirement(s) are returned by reference.
	 *
	 * @param PermissionModel $grant
	 * @param string|array &$failed_requirement
	 * @return bool
	 */
	protected function _applyPermission(PermissionModel $grant, &$failed_requirement = ''): bool
	{
		// If root is granted, pass.
		if ($grant->root)
		{
			return true;
		}

		// If module instance, check access permission.
		if ($this->module_srl && !$grant->access)
		{
			$failed_requirement = 'access';
			return false;
		}

		// Get the target permission (required permission) for the current action.
		$target = $this->xml_info->action->{$this->act}->permission->target ?: null;
		if (empty($target) && stripos($this->act, 'admin') !== false)
		{
			$target = 'root';
		}

		// If there are no targets defined, everyone is allowed.
		if (empty($target) || $target === 'guest' || $target === 'everyone')
		{
			return true;
		}

		// If target is 'member', the user must be logged in.
		if ($target === 'member')
		{
			if ($this->user->member_srl)
			{
				return true;
			}
			else
			{
				$failed_requirement = 'member';
				return false;
			}
		}

		// If target is 'not_member', the user must be logged out, except for managers.
		if ($target === 'not_member' || $target === 'not-member')
		{
			if (!$this->user->member_srl || $grant->manager)
			{
				return true;
			}
			else
			{
				$failed_requirement = 'not_member';
				return false;
			}
		}

		// If target is 'root', the user always fails, because root was already checked.
		if ($target == 'root')
		{
			$failed_requirement = 'root';
			return false;
		}

		// If target is 'manager' or 'manager:<scope>', check manager grants.
		// $type[2] is the optional scope after 'manager:'.
		// $type[3] is the module prefix in '*-managers'.
		if (preg_match('/^(manager(?::(.+))?|([a-z0-9\_]+)-managers)$/', $target, $type))
		{
			if ($grant->manager)
			{
				if (empty($type[2]))
				{
					return true;
				}
				elseif ($grant->can($type[2]))
				{
					return true;
				}
			}

			// If target is '*-managers', the user must be a manager of some other module instance.
			if (Context::get('is_logged') && isset($type[3]))
			{
				// Search all module instances.
				if ($type[3] == 'all' && ModuleModel::findManagerPrivilege($this->user) !== false)
				{
					return true;
				}
				// Search module instances of the same module.
				elseif ($type[3] == 'same' && ModuleModel::findManagerPrivilege($this->user, $this->module) !== false)
				{
					return true;
				}
				// Search module instances of a specific module.
				elseif (ModuleModel::findManagerPrivilege($this->user, $type[3]) !== false)
				{
					return true;
				}
			}

			$failed_requirement = 'manager';
			return false;
		}

		// In all other cases, check the name of the permission.
		// If multiple names are given, all of them must pass.
		elseif ($custom_targets = array_map('trim', explode(',', $target)))
		{
			foreach ($custom_targets as $name)
			{
				// Undefined permission.
				if (!isset($grant->{$name}))
				{
					return false;
				}
				// Defined but failed permission.
				if (!$grant->{$name})
				{
					$failed_requirement = $grant->whocan($name);
					return false;
				}
			}
			return true;
		}

		// In all other cases, deny permission.
		return false;
	}

	/**
	 * Generate an error message for failed permission check.
	 *
	 * @param string|array $failed_requirement
	 * @return string
	 */
	protected function _generatePermissionError($failed_requirement): string
	{
		if ($failed_requirement === 'member' || !$this->user->isMember())
		{
			return 'msg_not_logged';
		}
		elseif ($failed_requirement === 'not_member')
		{
			return 'msg_required_not_logged';
		}
		elseif ($failed_requirement === 'manager' || $failed_requirement === 'root')
		{
			return 'msg_administrator_only';
		}
		elseif (is_array($failed_requirement) && count($failed_requirement))
		{
			if (class_exists('PointModel'))
			{
				$min_level = \PointModel::getMinimumLevelForGroup($failed_requirement);
				if ($min_level)
				{
					return sprintf(lang('member.msg_required_minimum_level'), $min_level);
				}
			}
			return 'member.msg_required_specific_group';
		}
		else
		{
			return 'msg_not_permitted_act';
		}
	}
}
