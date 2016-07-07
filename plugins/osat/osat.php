<?php
foreach(['OsatTranslator', 'OsatExpressions'] as $class)
{
	if(!class_exists($class, false))
	{
		require_once(realpath(dirname(__FILE__) . '/' . $class . '.php'));
	}
}

class Osat extends \ls\pluginmanager\PluginBase
{
    protected $storage = 'DbStorage';

	static protected $description = 'LimeSurvey alterations for OSAT use';
	static protected $name = 'OSAT';
	static protected $label = 'osat';

	protected $menuLabel = "Translations";
	protected $settings = [];
	protected $pluginSettings = null;
    protected $localeSettings = [
		'translate' => [
			'type' => 'text',
			'title' => 'Translations',
			'help' => '"String to translate","Translation of the String" [, (optional) "Plural translation"'
		]
    ];

	protected $translator = null;
	protected $parentTranslator = null;

	public function __construct(PluginManager $manager, $id)
	{
/*
		$s = get_called_class();
		if($debug = @debug_backtrace())
		{
			$s.= !empty($debug[0]['file']) ? ', FILE: ' . preg_replace('/\/(.*)$/', "$1", $debug[0]['file']) : '';
			$s.= !empty($debug[0]['line']) ? ', LINE: ' . $debug[0]['line'] : '';
		}
		echo "\n--- " . STR_PAD($s, 60, '-', STR_PAD_BOTH) . " \n";
*/
		parent::__construct($manager, $id);

        $this->_registerEvents();

		# print_r($_SESSION); die();
	}

    protected function _registerEvents()
    {
		/**
		 * Here you should handle subscribing to the events your plugin will handle
		 */
		$this->subscribe('afterPluginLoad');
		$this->subscribe('newSurveySettings');
		$this->subscribe('beforeAdminMenuRender');
		$this->subscribe('beforeQuestionRender');
        $this->subscribe('beforeEmManagerHelperProcessString');


		$this->subscribe('beforeControllerAction');
        $this->subscribe('beforeSurveyPage');
	}

	public function beforeControllerAction()
	{
		$event = $this->event;
		if($event->get('controller') == 'surveys' && $event->get('action') == 'publicList')
		{
			// is there only one survey? let's redirect to this one!
			$surveys = Survey::model()->active()->open()->with('languagesettings')->findAll();
			if(count($surveys == 1))
			{
				$surveyId = $surveys[0]->getAttribute('sid');
				$sReloadUrl = Yii::app()->createUrl("/survey/index/sid/{$surveyId}");

				$this->redirectTo($sReloadUrl);
				exit;
			}
		}
	}

	protected function canRedirect($sReloadUrl)
	{
		$currentUrl = trim(preg_replace('/([^\?]+)?(\?.*)?$/',"$1",$_SERVER['REQUEST_URI']), '/');
		$sReloadUrl = trim($sReloadUrl, '/');

		return $currentUrl != $sReloadUrl;
	}

	protected function redirectTo($sReloadUrl)
	{
		$controller = new RegisterController('index');
		if($this->canRedirect($sReloadUrl))
		{
			$controller->redirect($sReloadUrl);
		}
	}

	public function beforeSurveyPage()
	{
		$event = $this->event;
		$surveyId = $event->get('surveyId');
		$content = $event->get('content');

		$myEvent = new PluginEvent('beforeSurveyPageOsatEarly');
		$myEvent->set('surveyId', $surveyId);
		$myEvent->set('content', $content);
		App()->getPluginManager()->dispatchEvent($myEvent);

		$content = $myEvent->get('$content') . $content;

		$myEvent = new PluginEvent('beforeSurveyPageOsatLate');
		$myEvent->set('surveyId', $surveyId);
		$myEvent->set('content', $content);
		App()->getPluginManager()->dispatchEvent($myEvent);

		$content.= $myEvent->get('$content');

		$event->set('content', $content);
	}

	protected function getRequest($key = null)
	{
		$request = Yii::app()->request->getParam(static::$label);
		if(!empty($request))
		{
			if(!empty($key))
			{
				if(isset($request[$key]))
				{
					return $request[$key];
				}
				return null;
			}
			return $request;
		}
		return null;
	}

	public function getTranslator($pluginonly = false)
	{
		if($this->translator == null)
		{
			$this->translator = new OsatTranslator([
				'folder' => dirname(__FILE__) . '/../' . get_called_class(),
				'settings' => $this->prepareLocaleSettings()
			]);
		}
		return $this->translator;
	}

	public function afterPluginLoad()
	{
		$isAdmin = preg_match('/index\.php\/admin/', Yii::app()->request->getRequestUri());
		if($isAdmin)
		{
			$oAdminTheme = AdminTheme::getInstance();
			$oAdminTheme->registerScriptFile( 'ADMIN_SCRIPT_PATH', '../../plugins/Osat/assets/js/scripts.js');
			$oAdminTheme->registerCssFile( 'ADMIN', '../../../plugins/Osat/assets/css/styles.css');
		}
	}

	public function beforeAdminMenuRender()
	{
		if(Permission::model()->hasGlobalPermission('settings','update') && $this->pluginManager->isPluginActive(static::$label))
		{
			$event = $this->event;
			$menu = $this->addMenuItemToOsatAdminMenu($event, [
				'isDivider' => false,
				'isSmallText' => false,
				'label' => $this->getTranslator()->translate($this->menuLabel),
				'href' => Yii::app()->createUrl('/admin/pluginmanager/sa/configure', array('id' => $this->getId())),
				'iconClass' => ''
			]);
		}
	}

	protected function addMenuItemToOsatAdminMenu(&$event, array $options = [])
    {
		if($osatMenu = $this->getOsatAdminMenu($event))
		{
			$menuItem = new \ls\menu\MenuItem($options);

			if(!empty($osatMenu['menuItems']))
			{
				foreach($osatMenu['menuItems'] as $testItem)
				{
					if($testItem instanceof \ls\menu\MenuItem)
					{
						if($testItem->getHref() == $menuItem->getHref())
						{
							// already set up, so do nothing!
							return $event;
						}
					}
				}
			}

			// menuItem is not set up already, let's add it!
			$osatMenu['menuItems'][] = $menuItem;

			// and replace the current osatmenu with the new one!
			$created = false;
			$menus = $event->get('extraMenus', array());
			if(!empty($menus))
	        {
	            foreach($menus as $i => $menu)
	            {
	                if($menu instanceof \ls\menu\Menu)
	                {
						if($menu->getHref() == $osatMenu['href'])
						{
							$menus[$i] = new \ls\menu\Menu($osatMenu);
							$created = true;
							break;
						}
	                }
	            }
	        }
			if(!$created)
			{
				$menus[] = new \ls\menu\Menu($osatMenu);
			}

			$event->set('extraMenus', $menus);
		}

		return $event;
    }

	protected function getOsatAdminMenu(&$event)
    {
		if(is_object($event))
		{
			$menus = $event->get('extraMenus', array());
			$osatMenu = [
				'href' => '#osatadminmenu',
				'label' => $this->getTranslator()->translate('Osat'),
				'menuItems' => [],
				'isDropDown' => true
			];

			if(!empty($menus))
	        {
	            foreach($menus as $i => $menu)
	            {
	                if($menu instanceof \ls\menu\Menu)
	                {
						if($menu->getHref() == $osatMenu['href'])
						{
							$osatMenu['menuItems'] = $menu->getMenuItems();
							break;
						}
	                }
	            }
	        }

			return $osatMenu;
		}

		return null;
    }

	public function beforeQuestionRender()
	{
		$event = $this->event;

		$answers = $event->get('answers');

		$myEvent = new PluginEvent('beforeEmManagerHelperProcessString');
		$myEvent->set('stringToParse', $answers);
		App()->getPluginManager()->dispatchEvent($myEvent);
		$answers = $myEvent->get('stringToParse');

		$event->set('answers', $answers);
	}

	public function beforeEmManagerHelperProcessString()
    {
        $event = $this->event;
        $stringToParse = $event->get('stringToParse');

        $stringToParse = $this->addLocales($stringToParse);

		$this->event->set('stringToParse', $stringToParse);

        return $this->event;
    }

    protected function addLocales($stringToParse)
    {
        $stringToParse = $this->addCustomReplacements($stringToParse);

		$myEvent = new PluginEvent('osatAddLocales');
		$myEvent->set('stringToParse', $stringToParse);
		App()->getPluginManager()->dispatchEvent($myEvent);
		$stringToParse = $myEvent->get('stringToParse');

        $stringToParse = $this->addTranslations($stringToParse, true);

        return $stringToParse;
    }

	public function addTranslations($string, $cleanup = false)
	{
        $debug = false;
        if($cleanup === 'debug')
        {
            $cleanup = false;
            # $debug = true;
        }
		preg_match_all('~\{{2}\s*(.*?)\s*\}{2}~', $string, $matches);

		if(strpos($string, 'Sponsored by'))
		{
			$debug = true;
		}

		if(!empty($matches[0]))
		{
			foreach($matches[0] as $i => $source)
			{
				$destination = trim($matches[1][$i]);
				$args = [];
				foreach(explode('|', $destination) as $arg)
				{
					$arg = trim($arg);
					if(!empty($arg))
					{
						$args[] = $arg;
					}
				}
				unset($arg);

				$htmlesc = true;
				if(!empty($args))
				{
					if(count($args) > 1)
					{
						$lastarg = array_pop($args);
						if($lastarg === 'raw')
						{
							$htmlesc = false;
						}
						else
						{
							$args[] = $lastarg;
						}
					}

                    if((bool) $cleanup || $this->getTranslator()->hasTranslationString($args[0]))
                    {
                        $destination = call_user_func_array(array($this->getTranslator(), 'translate'), $args);

                        if($htmlesc)
    					{
    						$destination = htmlspecialchars($destination);
    					}

						$string = str_replace($source, $destination, $string);
                    }
				}

				if((bool) $cleanup)
				{
					$string = str_replace($source, $destination, $string);
				}
			}
		}
		return $string;
	}

	public function getExpressions()
	{
		if(!isset($this->expressions))
		{
			$this->expressions = new OsatExpressions();
		}

		return $this->expressions;
	}

	public function addCustomReplacements($string)
	{
		if(!($expressions = $this->getExpressions()))
		{
			return $string;
		}

		preg_match_all('~\{\s*(.*?)\s*\}~', $string, $matches);

		if(!empty($matches[0]))
		{
			foreach($matches[0] as $i => $source)
			{
				$destination = trim($matches[1][$i]);
				$args = [];
				foreach(explode('|', $destination) as $arg)
				{
					$arg = trim($arg);
					if(!empty($arg))
					{
						$args[] = $arg;
					}
				}
				unset($arg);

				$htmlesc = true;
				if(!empty($args))
				{
					$cmd = array_shift($args);
					$cmd = preg_replace('/[^a-z0-9\_]/i','',$cmd);
					$cmd = strtolower($cmd);
					$cmd = preg_replace_callback('/_([a-z])/', function($c) { return strtoupper($c[1]); }, $cmd);

					if(method_exists($expressions, $cmd) && is_callable(array($expressions, $cmd)))
					{
						if(count($args))
						{
							$lastarg = array_pop($args);
							if($lastarg === 'raw')
							{
								$htmlesc = false;
							}
							else
							{
								$args[] = $lastarg;
							}
						}

						$destination = call_user_func_array(array($expressions, $cmd), $args);
						$destination = $this->formatOutput($destination, $args);

						if($htmlesc && ($destination == strip_tags($destination))) {
							// only escape chars when $htmlesc is TRUE and if the string is not already HTML
							$destination = htmlspecialchars($destination);
						}

						$string = str_replace($source, $destination, $string);
					}
				}
			}
		}
		return $string;
	}

	public function formatOutput($value, $format = null)
	{
		if(!empty($format))
		{
			$format = (array) $format;
			foreach($format as $args)
			{
				$args = explode(',', $args);
				$cmd = array_shift($args);

				if(function_exists($cmd))
				{
					$test = 'STR_PAD_LEFT';

					foreach($args as &$a)
					{
						if(defined($a))
						{
							$a = constant($a);
						}
					}

					$value = call_user_func_array($cmd, array_merge((array) $value, $args));
				}
			}
		}

		return $value;
	}


	protected function isLocaleSetting($settingsName)
	{
		foreach($this->localeSettings as $key => $options)
		{
			$prefix = static::$label . '_' . $key;
			foreach($this->getTranslator()->getAvailableLanguages() as $code => $name)
			{
				$name = $prefix . '_' . $code;
				if($name === $settingsName)
				{
					return true;
				}
				unset($name);
			}
			unset($prefix, $code, $name);
		}
		unset($key, $options);

		return false;
	}

	protected function prepareLocaleSettings()
	{
		$settings = array();

		if($languages = OsatTranslator::getAvailableLanguages())
		{
			foreach($this->localeSettings as $key => $options)
			{
				$prefix = static::$label . '_' . $key;

				// the header
				$settings[$prefix . '_info'] = array(
					'type' => 'info',
					'content' => !empty($options['title']) ? $options['title'] : static::$name,
					'label' => ''
				);

				$count = 0;
				foreach($languages as $code => $name)
				{
					$settings[$prefix . '_' . $code] = [
						'label' => $name . (empty($count) ? ' (default)' : ''),
						'type' => !empty($options['type']) ? $options['type'] : 'text',
						'current' => $this->get($prefix . '_' . $code, null, null, null ),
						'help' => !empty($options['help']) ? $options['help'] : null
					];
					$count++;
				}
				unset($count, $code, $name, $prefix);
			}
			unset($key, $options);
		}
		unset($languages);

		return $settings;
	}

	protected function preparePluginSettings()
	{
        if($this->pluginSettings === null)
        {
            // basic settings
			$this->pluginSettings = array_replace($this->settings, $this->prepareLocaleSettings());
		}

		return $this->pluginSettings;
	}

	public function getPluginSettings($getValues = true)
	{
		$settings = $this->preparePluginSettings();

		foreach ($settings as $name => &$setting)
		{
			if(!empty($setting['content']))
			{
				$setting['content'] = $this->getTranslator()->translate($setting['content']);
			}

			if($this->isLocaleSetting($name))
			{
				if(!empty($setting['label']))
				{
					$setting['label'] = preg_replace('/ \(default\)$/', '', $setting['label']) .
										(preg_match('/ \(default\)$/', $setting['label']) ? ' (' . $this->getTranslator()->translate('default language') . ')' : '');
				}
			}

			if(!empty($setting['help']))
			{
				$setting['help'] = $this->getTranslator()->translate($setting['help']);
			}

			if ($getValues)
			{
				$setting['current'] = $this->get($name, null, null, isset($setting['default']) ? $setting['default'] : null );
			}

			if ($setting['type'] == 'logo')
			{
				$setting['path'] = $this->publish($setting['path']);
			}
		}

		return $settings;
	}

	public function getSettings($key = null)
	{
		$settings = $this->preparePluginSettings();
		foreach($settings as $name => &$setting)
		{
			$setting = $this->get($name, null, null, isset($setting['default']) ? $setting['default'] : null );
		}

		if(!empty($key) && is_string($key))
		{
			$try = [
				$key,
				$key . '_' . $this->getTranslator()->getCurrentLanguage(),
				strtolower(get_called_class()) . '_' . $key,
				strtolower(get_called_class()) . '_' . $key . '_' . $this->getTranslator()->getCurrentLanguage()
			];

			while($key = array_shift($try))
			{
				if(isset($settings[$key]))
				{
					return $settings[$key];
				}
			}
			return null;
		}
		else {
			return $settings;
		}
	}

	public function newSurveySettings()
	{
		$event = $this->event;
		foreach ($event->get('settings') as $name => $value)
		{
			$this->set($name, $value, 'Survey', $event->get('survey'));
		}
	}
}
