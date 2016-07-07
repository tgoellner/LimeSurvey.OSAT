<?php
if(!class_exists('Osat', false))
{
	require_once(realpath(dirname(__FILE__) . '/../Osat/Osat.php'));
}
class OsatLogin extends Osat {

	# protected $storage = 'DbStorage';
	static protected $description = 'Enhanced login features';
	static protected $name = 'OSAT Login';
	static protected $label = 'osatlogin';

	protected $menuLabel = "Login";
	protected $settings = [];
    protected $localeSettings = [
		'translate' => [
			'type' => 'text',
			'title' => 'Translations',
			'help' => '"String to translate","Translation of the String" [, (optional) "Plural translation"'
		],
		'terms_of_service' => [
			'type' => 'html',
			'title' => 'Terms of service'
		],
		'privacy_policy' => [
			'type' => 'html',
			'title' => 'Privacy policy'
		],
		'legal_notice' => [
			'type' => 'html',
			'title' => 'Legal notice'
		],
		'required_attributes_text' => [
			'type' => 'html',
			'title' => 'Required attributes text'
		],
		'optional_attributes_text' => [
			'type' => 'html',
			'title' => 'Optional attributes text'
		]
    ];

	protected $passwordLength = 5;
	protected $password = [
		'length' => 5,
		'numbers' => true,
		'letters' => true,
		'capitals' => false,
		'special_characters' => false
	];
	protected $indexaction = 'register';

	public function __construct(PluginManager $manager, $id)
    {
		parent::__construct($manager, $id);

        $this->_registerEvents();
	}

    protected function _registerEvents()
    {
        /**
		 * Here you should handle subscribing to the events your plugin will handle
		 */
		$this->subscribe('beforeAdminMenuRender');
        # $this->subscribe('beforeRegisterForm');
        $this->subscribe('beforeSurveyPageOsatEarly');
        $this->subscribe('osatAddLocales');

		$this->subscribe('beforeStatsPage');


		// $this->subscribe('beforeControllerAction');

    }


	public function _DEACT_beforeControllerAction()
	{
		$event = $this->event;

		if($controller = new OsatLoginController([
			'translator' => $this->getTranslator()
		])) {
			if($controller->isActive())
			{
				if($return = $controller->doAction())
				{
					print_r($return);
					die("done");
				}
			}
			# print_r($controller);
			die("xxx");
		}
	}

	public function beforeStatsPage()
	{
		$event = $this->event;
		$redata = $event->get('redata');
		$redata['languagechanger'] = $this->makeLanguageChanger(App()->language);

		if($user = $this->getUserFromSession())
		{
			// get user's results
			$query = "SELECT * FROM {{survey_" . $user->getSurveyId() . "}} WHERE token = '" . $user->getToken() . '"';
		}


		$event->set('redata', $redata);
		return $event;

	}

	public function makeLanguageChanger($sSelectedLanguage)
	{
		if($surveyId = Yii::app()->request->getParam('sid', Yii::app()->request->getParam('surveyid', '')))
		{
			$aLanguages = Survey::model()->findByPk($surveyId)->getAllLanguages();
		}
		else {
			$aLanguages = getLanguageDataRestricted(true);// Order by native
			$aLanguages = array_keys($aLanguages);
		}

	    if(count($aLanguages)>1)
	    {
			$aAllLanguages=getLanguageData(true);
			$controller = Yii::app()->getController();

			$route = [];
			$varname = 'lang';

			foreach($_REQUEST as $k=>$v)
			{
				if($k == 'language')
				{
					$varname = $k;
				}

				if($k != $varname)
				{
					$route[] = $k.'/'.$v;
				}
			}
			$sTargetURL = $controller->createUrl('');
			if(!empty($route))
			{
				$sTargetURL.= '/' . join('/', $route);
			}
			unset($route, $k, $v);

			$sClass="languagechanger";
			$aLanguages=array_intersect_key($aAllLanguages,array_flip($aLanguages)); // Sort languages by their locale name
	        $sHTMLCode="";
	        $sSelected="";
	        $sSelected=App()->language;
	        $sClass .= ' form-control ';

			$aListLang = [];
			foreach ($aLanguages as $sLangCode => $aSurveyLang)
			{
				$aListLang[$sLangCode]=html_entity_decode($aSurveyLang['nativedescription'], ENT_COMPAT,'UTF-8');
			}

	        $languageChangerDatas = array(
	            'sSelected' => $sSelected ,
				'aListLang' => $aListLang,
	            'sClass'    => $sClass    ,
	            'sTargetURL'=> $sTargetURL,
	        );
	        $sHTMLCode = Yii::app()->getController()->renderPartial('/survey/system/LanguageChanger/LanguageChanger', $languageChangerDatas, true);

			if(!empty($sHTMLCode))
			{
				$sHTMLCode = '<form action="' . $sTargetURL . '">' . $sHTMLCode . '</form>';
			}

	        return $sHTMLCode;
	    }
	    else
	    {
	        return false;
	    }
	}

	public function getTranslator($pluginonly = false)
	{
		if($this->translator == null)
		{
            $this->translator = parent::getTranslator();
		}

		if(!((bool) $pluginonly))
		{
			if($this->parentTranslator == null)
			{
				$this->parentTranslator = false;

				// load basic OSAT translations too!
	            if($data = Plugin::model()->findByAttributes(array('name'=>'osat')))
		        {
		            if($plugin = App()->getPluginManager()->loadPlugin($data->name, $data->id))
					{
						$parentTranslator = $plugin->getTranslator();
	                    $parentTranslator->appendTranslationStrings($this->translator->getTranslationStrings());
	        			$this->parentTranslator = $parentTranslator;
	                    unset($parentTranslator);
					}
	                unset($plugin);
		        }
	            unset($data);
			}

			if(!empty($this->parentTranslator))
			{
				return $this->parentTranslator;
			}
		}

		return $this->translator;
	}

	protected function getSurveySession($key = null)
	{
		if($surveyId = Yii::app()->request->getParam('sid'))
		{
			$LEMsessid = 'survey_' . $surveyId;

			if(!empty($_SESSION[$LEMsessid]))
			{
				if(!empty($key))
				{
					if(isset($_SESSION[$LEMsessid][$key]))
					{
						return $_SESSION[$LEMsessid][$key];
					}
				}
				else
				{
					return $_SESSION[$LEMsessid];
				}
			}
		}

		return null;
	}

	protected function getUserByEmail($email)
	{
		return OsatUser::findByEmail($email, null, $this->getTranslator(true));
	}

	protected function getUserByToken($token)
	{
		return OsatUser::findByToken($token, null, $this->getTranslator(true));
	}

	protected function getUserFromSession()
	{
		return OsatUser::getUserFromSession($this->getTranslator(true));
	}

	protected function newUser(array $attributes = [])
	{
		$attributes['translator'] = $this->getTranslator(true);

		return new OsatUser($attributes);
	}

	protected function invalidPassword($password)
	{
		if(empty($password))
		{
			 return $this->getTranslator()->translate('The password must not be empty.');
		}

		$return = [];
		if(!empty($this->password['length']) && strlen($password) < $this->password['length'])
		{
			 $return[] = $this->getTranslator()->translate('The password has to be at least %d characters long.', $this->password['length']);
		}

		if( (empty($this->password['numbers']) || preg_match('/[0-9]/', $password)) &&
			(empty($this->password['letters']) || preg_match('/[a-z]/', $password)) &&
			(empty($this->password['capitals']) || preg_match('/[A-Z]/', $password)) &&
			(empty($this->password['special_characters']) || preg_match('/[^a-z0-9]/i', $password))
		)
		{
		}
		else
		{
			$characters = [];
			foreach($this->password as $k => $v)
			{
				if($k != 'length' && !empty($v))
				{
					$k = str_replace('_', ' ', $k);
					$characters[] = $this->getTranslator()->translate($k);
				}
			}

			if(!empty($characters))
			{
				$return[] = $this->getTranslator()->translate('It must contain %s.', join(", ", $characters));
			}
		}

		return !empty($return) ? join(" ", $return) : false;
	}

	protected function setToken($surveyId, $sToken)
	{
		$LEMsessid = 'survey_' . $surveyId;

		if(!empty($_SESSION[$LEMsessid]['token']) && $_SESSION[$LEMsessid]['token'] == $sToken)
		{
			return;
		}

		// let's restart with this new token!
		$controller = new RegisterController('index');

		$sReloadUrl = $controller->createUrl("/survey/index/sid/{$surveyId}",array('token'=>$sToken,'lang'=>App()->language));

		if($this->canRedirect($sReloadUrl))
		{
			killSurveySession($surveyId);
			$controller->redirect($sReloadUrl);
		}
	}

	protected function clearToken($surveyId, array $urlParam = [])
	{
		$LEMsessid = 'survey_' . $surveyId;

		if(!empty($_SESSION[$LEMsessid]['token']))
		{
			unset($_SESSION[$LEMsessid]['token']);
		}

		// let's restart with this new token!
		$controller = new RegisterController('index');

		$urlParam = array_replace(array('lang'=>App()->language), $urlParam);

		$sReloadUrl = $controller->createUrl("/survey/index/sid/{$surveyId}", $urlParam );

		if($this->canRedirect($sReloadUrl))
		{
			killSurveySession($surveyId);
			$controller->redirect($sReloadUrl);
		}
	}

	public function getUrl($type = null, $surveyId = null, array $params = [])
	{
		$surveyId = empty($surveyId) ? Yii::app()->request->getParam('sid') : $surveyId;
		$params['lang'] = empty($params['lang']) ? App()->language : $params['lang'];
		$params['action'] = 'register';

		switch($type)
		{
			case 'logout' :
				return Yii::app()->createUrl("/survey/index/sid/{$surveyId}", array_replace($params, ['function' => 'logout']));
				break;
			case 'register' :
				return Yii::app()->createUrl("/survey/index/sid/{$surveyId}", array_replace($params, ['function' => 'register']));
				break;
			case 'forgot_password' :
				return Yii::app()->createUrl("/survey/index/sid/{$surveyId}", array_replace($params, ['function' => 'forgot-password']));
				break;
			case 'reset_password' :
				return Yii::app()->createUrl("/survey/index/sid/{$surveyId}", array_replace($params, ['function' => 'reset-password']));
				break;
			case 'login' :
				return Yii::app()->createUrl("/survey/index/sid/{$surveyId}", array_replace($params, ['function' => 'login']));
				break;
			case 'attributes' :
				return Yii::app()->createUrl("/survey/index/sid/{$surveyId}", array_replace($params, ['function' => 'attributes']));
				break;
			case 'extraattributes' :
				return Yii::app()->createUrl("/survey/index/sid/{$surveyId}", array_replace($params, ['function' => 'extraattributes']));
				break;
			case null :
				return Yii::app()->createUrl("/survey/index/sid/{$surveyId}", $params);
				break;
			default :
				return '';
				break;
		}
	}

	public function getLoginLink($surveyId = null, $sLanguage = null)
	{
		if($user = $this->getUserFromSession())
		{
			return '';
		}

		$url = $this->getUrl('logout', $surveyId, ['lang' => $sLanguage]);
		$text = $this->getTranslator()->translate('Log in');
		$css = 'login';
		return '<a href="' . $url . '" class="osat-extended-login--' . $css . '" aria-label="' . htmlspecialchars($text) . '">' . htmlspecialchars($text) . '</a>';
	}

	public function getLogoutLink($surveyId = null, $sLanguage = null)
	{
		if($user = $this->getUserFromSession())
		{
			$url = $this->getUrl('logout', $surveyId, ['lang' => $sLanguage]);
			$text = $this->getTranslator()->translate('Log out');
			$css = 'logout';
			return '<a href="' . $url . '" class="osat-extended-login--' . $css . '" aria-label="' . htmlspecialchars($text) . '">' . htmlspecialchars($text) . '</a>';
		}

		return '';
	}

	public function getLoginLogoutLink($surveyId = null, $sLanguage = null)
	{
		return trim($this->getLogoutLink() . $this->getLoginLink());
	}

	protected function isCompleted(OsatUser $user)
	{
		if($user->hasJustCompletedSurvey() && strpos(Yii::app()->request->getParam('function'), 'attributes')===false)
		{
			return false;
		}

		if($user->hasCompletedSurvey())
		{
			if(count($attr = $user->getMissingExtraAttributes()))
			{
				// the registration is not completed yet, show attributes
				$this->createRegisterPage([
				   'missing_attributes' => $attr,
				   'optional_attributes' => true,
				   'function' => 'extraattributes',
				   'surveyId' => $user->get('surveyId'),
				   'sToken' => $user->get('token')
			   ]);
			   return false;
			}
			else
			{
				return true;
			}
		}
		return false;
	}

	public function beforeSurveyPageOsatEarly()
	{
		$event = $this->event;
		$surveyId = $event->get('surveyId');
		$return = false;

		// logout ?
		$function = Yii::app()->request->getParam('function');
		if($function === 'logout')
		{
			if($user = $this->getUserFromSession())
			{
				$user->logout();
			}
			$this->clearToken($surveyId, ['action' => 'register', 'function' => 'login']);
			return;
		}

		$sToken = Yii::app()->request->getParam('token');
		if(empty($sToken))
		{
			$sToken = $this->getSurveySession('token');
		}

		if(!empty($sToken))
		{
			if($user = $this->getUserByToken($sToken))
			{
				if(!$user->isLoggedIn())
				{
					$user->logout();
					$this->clearToken($surveyId, ['function'=>'login', 'action' => 'register', 'register_email' => $user->email]);
				}
			}
		}
		else
		{
			if($surveyId && ($user = $this->getUserFromSession()))
			{
				if($sToken = $user->getToken())
				{
					$resetToken = true;
				}
			}
		}

		if(!empty($user))
		{
			if($user->isLoggedIn())
			{
				 if(count($attr = $user->getMissingAttributes()) && $surveyId && !empty($sToken))
				 {
					 $this->createRegisterPage([
		 				'missing_attributes' => $attr,
		 				'function' => 'attributes',
		 				'surveyId' => $surveyId,
		 				'sToken' => $sToken
		 			]);
					$resetToken = true;
				 }
				 else
				 {

					 $this->isCompleted($user);
					 $resetToken = true;
				 }
			 }
		}


		if(!empty($resetToken))
		{
			$this->setToken($surveyId, $sToken);
			return;
		}


		if($surveyId && empty($sToken))
		{
			$this->createRegisterPage([
				'surveyId' => $surveyId
			]);
		}
	}

	protected function createRegisterPage(array $attributes = [])
	{
		$surveyId = empty($attributes['surveyId']) ? Yii::app()->request->getParam('sid') : $attributes['surveyId'];
		if(empty($surveyId))
		{
			return null;
		}

		// survey requested but no token found (e.g. not logged in)
		$survey = Survey::model()->find("sid=:sid",array(':sid'=>$surveyId));

		if($survey && $survey->active == "Y" && $survey->allowregister == "Y" && tableExists("tokens_{$surveyId}"))
		{
			$controller = new RegisterController('register');

			$sLanguage = empty($attributes['sLanguage']) ? Yii::app()->request->getParam('lang','') : $attributes['sLanguage'];
			if ($sLanguage== "" )
			{
				$sLanguage = Survey::model()->findByPk($surveyId)->language;
			}
			$aSurveyInfo=getSurveyInfo($surveyId,$sLanguage);
			$sAction= Yii::app()->request->getParam('action','view');

			// We can go
			$registerpage_vars = [];
			$registerform_vars = [
				'surveyId' => $surveyId,
				'sLanguage' => $sLanguage,

				'allow_password_reset' => true,
				'require_terms_of_service' => true,
				'register_termsaccepted' => false,
				'form_submitted' => false,

				'register_firstname' => '',
				'register_lastname' => '',
				'register_email' => '',
				'register_password' => '',
				'register_password_confirm' => '',
				'register_secret' => '',

				'missing_attributes' => [],

				'url_login' => $this->getUrl('login', $surveyId, ['lang' => $sLanguage]),
				'url_register' => $this->getUrl('register', $surveyId, ['lang' => $sLanguage]),
				'url_forgot_password' => $this->getUrl('forgot_password', $surveyId, ['lang' => $sLanguage]),
				'url_reset_password' => $this->getUrl('reset_password', $surveyId, ['lang' => $sLanguage]),
				'url_attributes' => $this->getUrl('url_attributes', $surveyId, ['lang' => $sLanguage]),

				'urlAction' => $this->getUrl(null, $surveyId, ['lang' => $sLanguage]),
				'bCaptcha' => function_exists("ImageCreate") && isCaptchaEnabled('registrationscreen', $aSurveyInfo['usecaptcha']),

				'errors' => []
			];
			$registerform_vars = array_replace($registerform_vars, $attributes);

			foreach($registerform_vars as $k => $v)
			{
				if(preg_match('/^register_/', $k))
				{
					$registerform_vars[$k] = sanitize_xss_string(Yii::app()->request->getParam($k, $v));
				}
			}

			foreach($_POST as $k => $v)
			{
				if(preg_match('/^register_/', $k))
				{
					$registerform_vars['form_submitted'] = true;
					$registerform_vars[$k] = sanitize_xss_string(Yii::app()->request->getParam($k, $v));
				}
			}

			$registerform_vars['function'] = empty($registerform_vars['function']) ? Yii::app()->request->getParam('function') : $registerform_vars['function'];
			if(!in_array($registerform_vars['function'], ['register', 'forgot-password', 'reset-password', 'logout', 'attributes', 'extraattributes', 'login']))
			{
				$registerform_vars['function'] = $this->indexaction;
/*
				if($urlToken = Yii::app()->request->getParam('token'))
				{
					if($user = OsatUser::findByToken($urlToken))
					{
						if($sessionUser = OsatUser::findByToken($urlToken))
					}
				}
*/

			}

			switch($registerform_vars['function'])
			{
				case 'register' :
					if($registerform_vars['form_submitted'])
					{
						if(filter_var($registerform_vars['register_email'], FILTER_VALIDATE_EMAIL))
						{
							// we have a valid email
							if($error = $this->invalidPassword($registerform_vars['register_password']))
							{
								// but the password is invalid
								$registerform_vars['errors'][] = $error;
							}
							else
							{
								// password is valid
								if($registerform_vars['register_password'] != $registerform_vars['register_password_confirm'])
								{
									// but confirmation does not match
									$registerform_vars['errors'][] = $this->getTranslator()->translate('The two passwords did not match');
								}
								else
								{
									if($registerform_vars['require_terms_of_service'] && !$registerform_vars['register_termsaccepted'])
									{
										// terms not accepted although it is required
										$registerform_vars['errors'][] = $this->getTranslator()->translate('You have to accept the terms of service and the privacy policy to create an account.');
									}
									else
									{
										// let's set up the valuas
										$values = [
											'surveyId' => $registerform_vars['surveyId']
										];

										foreach($registerform_vars as $k => $v)
										{
											if(preg_match('/^register_/', $k))
											{
												$values[preg_replace('/^register_/', '',$k)] = $v;
											}
										}

										if($user = $this->newUser($values))
										{
											// user created
											if($user->exists())
											{
												// user already exists - do nothing!
												$registerform_vars['errors'][] = $this->getTranslator()->translate(
													'The email %1$s is already registered - please <a href="%2$s">log in here</a>. If you forgot your password you can <a href="%3$s">reset it here.</a>',
													$registerform_vars['register_email'],
													$registerform_vars['url_login'],
													$registerform_vars['url_forgot_password']
												);
											}
											else
											{
												// set password
												$user->setPassword($registerform_vars['register_password']);

												// try to save the user
												if($user->save())
												{
													// user saved - now let's log in the user!
													// TODO: DoubleOptIn function!

													$user->login($registerform_vars['register_password']);

													if($sToken = $user->getToken())
													{
														if(count($attr = $user->getMissingAttributes()))
														{
															$registerform_vars['function'] = 'attributes';
															$registerform_vars['missing_attributes'] = $attr;
															$registerpage_vars['REGISTERFORM'] = $controller->renderFile(dirname(__FILE__) . '/view/register/attributesForm.php', $registerform_vars, true);
														}
														else {
															$this->setToken($surveyId, $sToken);
															return;
														}
													}
													else
													{
														$registerform_vars['errors'][] = $this->getTranslator()->translate('Sorry, you are not allowed to view this survey.');
													}
												}
												else
												{
													// save errors - store them in the error var
													$registerform_vars['errors'] = array_merge($registerform_vars['errors'], $user->getErrors());
												}
											}
										}
									}
								}
							}
						}
					}

					if(!empty($registerform_vars['errors']))
					{
						$registerform_vars['register_password'] = '';
						$registerform_vars['register_password_confirm'] = '';
						$registerform_vars['register_termsaccepted'] = false;
					}

					if(empty($registerpage_vars['REGISTERFORM']))
					{
						$registerpage_vars['REGISTERFORM'] = $controller->renderFile(dirname(__FILE__) . '/view/register/registerForm.php', $registerform_vars, true);
					}

					break;
				case 'forgot-password' :
					if($registerform_vars['form_submitted'])
					{
						if(filter_var($registerform_vars['register_email'], FILTER_VALIDATE_EMAIL))
						{
							// we have a valid email
							if($secret = OsatUser::getForgotPasswordSecret($registerform_vars['register_email']))
							{
								// and the email could be found so we have a secret that we can send out via email
								if($this->sendForgotPasswordEmail($registerform_vars['register_email'], $secret, $surveyId, $sLanguage))
								{
									// redirect and display notice!
									$url = $this->getUrl('forgot_password', $surveyId, ['lang' => $sLanguage, 'secretsent' => 1]);
									$this->redirectTo($url);
								}
								else
								{
									$registerform_vars['errors'][] = $this->getTranslator()->translate('Something went wrong trying to send you a reset link - please try again later or contact us if this error does not disappear.');
								}
							}
							else
							{
								$registerform_vars['errors'][] = $this->getTranslator()->translate('We could not find an account matching your email address - did you already sign up? <a href="%s">You can register here.</a>', $this->getUrl('register', $surveyId, ['lang' => $sLanguage]));
							}
						}
					}
					else if((bool) Yii::app()->request->getParam('secretsent', null))
					{
						$registerform_vars['notices'][] = $this->getTranslator()->translate('We have send you a link to reset your password - check your mailbox and follow the link. Note that this link will expire tomorrow midnight.');
					}
					else if((bool) Yii::app()->request->getParam('secretwrong', null))
					{
						$registerform_vars['errors'][] = $this->getTranslator()->translate('Sorry, the password reset link you provided has either expired or already been used. <a href="%s">You can restart the reset process here.</a>', $this->getUrl('forgot_password', $surveyId, ['lang' => $sLanguage]));
					}

					$registerpage_vars['REGISTERFORM'] = $controller->renderFile(dirname(__FILE__) . '/view/register/forgotPasswordForm.php', $registerform_vars, true);
					break;
				case 'reset-password' :
					if($registerform_vars['form_submitted'])
					{
						if(!empty($registerform_vars['register_secret']))
						{
							if($user = OsatUser::findByForgotPasswordSecret(base64_decode($registerform_vars['register_secret']), $surveyId, $this->getTranslator()))
							{
								// we have a valid email
								if($error = $this->invalidPassword($registerform_vars['register_password']))
								{
									// but the password is invalid
									$registerform_vars['errors'][] = $error;
								}
								else
								{
									// password is valid
									if($registerform_vars['register_password'] != $registerform_vars['register_password_confirm'])
									{
										// but confirmation does not match
										$registerform_vars['errors'][] = $this->getTranslator()->translate('The two passwords did not match');
									}
									else
									{
										// store the old pw
										$oldpw = $user->getPassword();

										// set password
										$user->setPassword($registerform_vars['register_password']);

										if($user->save() || ($oldpw == $user->getPassword()))
										{
											// user saved or old pw equals new one...
											// redirect to login page
											$url = $this->getUrl('login', $surveyId, ['lang' => $sLanguage, 'passwordreset' => 1]);
											$this->redirectTo($url);
										}
									}
								}
							}
							else
							{
								// redirect and display notice!
								$url = $this->getUrl('forgot_password', $surveyId, ['lang' => $sLanguage, 'secretwrong' => 1]);
								$this->redirectTo($url);
							}
						}
						else
						{
							$registerform_vars['errors'][] = $this->getTranslator()->translate('Looks as if you have used the reset password form without resetting your password first. <a href="%s">You can start the reset process here.</a>', $this->getUrl('forgot_password', $surveyId, ['lang' => $sLanguage]));
						}
					}
					elseif($secret = Yii::app()->request->getParam('secret', null))
					{
						if($user = OsatUser::findByForgotPasswordSecret(base64_decode($secret), $surveyId, $this->getTranslator()))
						{
							$registerform_vars['register_secret'] = $secret;
						}
						else
						{
							$registerform_vars['errors'][] = $this->getTranslator()->translate('Sorry, the password reset link you provided has either expired or already been used. <a href="%s">You can restart the reset process here.</a>', $this->getUrl('forgot_password', $surveyId, ['lang' => $sLanguage]));
						}
					}

					$registerpage_vars['REGISTERFORM'] = $controller->renderFile(dirname(__FILE__) . '/view/register/resetPasswordForm.php', $registerform_vars, true);

					break;
				case 'logout' :
					if($user = $this->getUserFromSession())
					{
					 	$user->logout();
					}
					$this->clearToken(null, ['action' => 'register', 'function' => 'login']);
					return;
					break;
				case 'attributes' :
				case 'extraattributes' :
					// prefill user values
					if(!$registerform_vars['form_submitted'])
					{
						if($user = $this->getUserFromSession())
						{
							$registerform_vars['register_firstname'] = $user->firstname;
							$registerform_vars['register_lastname'] = $user->lastname;
							$registerform_vars['register_email'] = $user->email;
							foreach($user->getAttributes() as $k => $v)
							{
								if($v != $user->getPassword())
								{
									$registerform_vars['register_' . $k] = isset($user->$k) ? $user->$k : '';
								}
							}
						}
					}

					if($registerform_vars['form_submitted'])
					{
						if($user = $this->getUserFromSession())
						{
							$values = [];
							if($registerform_vars['function'] == 'extraattributes')
							{
								$values = [
									'firstname' => $registerform_vars['register_firstname'],
									'lastname' => $registerform_vars['register_lastname'],
									'email' => $registerform_vars['register_email']
								];
							}

							foreach($registerform_vars as $k => $v)
							{
								if(preg_match('/^register_attribute_/', $k))
								{
									$values[preg_replace('/^register_/', '',$k)] = $v;
								}
							}

							$setPassword = null;
							if(!empty($registerform_vars['register_password']))
							{
								if($error = $this->invalidPassword($registerform_vars['register_password']))
								{
									// but the password is invalid
									$registerform_vars['errors'][] = $error;
								}
								else
								{
									// password is valid
									if($registerform_vars['register_password'] != $registerform_vars['register_password_confirm'])
									{
										// but confirmation does not match
										$registerform_vars['errors'][] = $this->getTranslator()->translate('The two passwords did not match');
									}
									else
									{
										$setPassword = $registerform_vars['register_password'];
									}
								}
							}


							if(!empty($values) && empty($registerform_vars['errors']))
							{
								$user->fill($values);

								if(!empty($setPassword))
								{
									$user->setPassword($setPassword);
								}

								// try to save the user
								if($user->save())
								{
									if(!$user->hasMissingExtraAttributes())
									{
										$user->setSessionVar('hasJustCompletedSurvey', null);
									}

									$this->setToken($surveyId, $sToken);

									return;
								}
								else
								{
									// save errors - store them in the error var
									$registerform_vars['errors'] = array_merge($registerform_vars['errors'], $user->getErrors());
								}
							}
						}
					}

					if($registerform_vars['function'] == 'extraattributes')
					{
						$tf = 'extraAttributesForm';
					}
					else
					{
						$tf = 'attributesForm';
					}

					$registerpage_vars['REGISTERFORM'] = $controller->renderFile(dirname(__FILE__) . '/view/register/' . $tf . '.php', $registerform_vars, true);
					break;
				case 'login' :
					if($registerform_vars['form_submitted'])
					{
						// try to find user email address in user database table
						if($user = $this->getUserByEmail($registerform_vars['register_email'], $surveyId))
						{
							if($user->login($registerform_vars['register_password']))
							{
								if($sToken = $user->getToken())
								{
									if(count($attr = $user->getMissingAttributes()))
									{
										$registerform_vars['function'] = 'attributes';
										$registerform_vars['missing_attributes'] = $attr;
										$registerpage_vars['REGISTERFORM'] = $controller->renderFile(dirname(__FILE__) . '/view/register/attributesForm.php', $registerform_vars, true);
									}
									elseif($this->continueSurvey($user))
									{
										if($this->isCompleted($user))
										{
											$this->setToken($surveyId, $sToken);
											return;
										}
										return;
									}
									else
									{
										$this->setToken($surveyId, $sToken);
										return;
									}
								}
								else
								{
									$registerform_vars['errors'][] = $this->getTranslator()->translate('Sorry, you are not allowed to view this survey.');
								}
							}
							else
							{
								$registerform_vars['errors'][] = $this->getTranslator()->translate('The given password is incorrect - did you forget your password? <a href="%s">You can reset it here.</a>', $registerform_vars['url_forgot_password']);
							}
						}
						else
						{
							$registerform_vars['errors'][] = $this->getTranslator()->translate('No user found with the given email address %1$s. Don\'t have an account yet? You can <a href="%2$s">create one here</a>.', $registerform_vars['register_email'], $registerform_vars['url_register']);
						}
					}
					else if(!empty($registerform_vars['register_email']))
					{
						$registerform_vars['errors'][] = $this->getTranslator()->translate('You need to login before you can access this survey.');
					}
					else if((bool) Yii::app()->request->getParam('passwordreset', null))
					{
						$registerform_vars['notices'][] = $this->getTranslator()->translate('Your password has been reset. Try to log in again. If you still have problems to login contact us!');
					}

					if(empty($registerpage_vars['REGISTERFORM']))
					{
						$registerpage_vars['REGISTERFORM'] = $controller->renderFile(dirname(__FILE__) . '/view/register/loginForm.php', $registerform_vars, true);
					}

					break;
			}

			if($registerpage_vars['languagechanger'] = makeLanguageChangerSurvey($sLanguage))
			{
				$registerpage_vars['languagechanger'] = '<form action="' . Yii::app()->createUrl("/survey/index/sid/{$surveyId}", ['action' => 'register', 'function' => $registerform_vars['function']]) . '">' . str_replace('name="langchanger"', 'name="lang"', $registerpage_vars['languagechanger']) . '</form>';
			}

			$_POST['osatbodycss'] = 'register-' . $registerform_vars['function'];

			$sTemplatePath = $aData['templatedir'] = getTemplatePath($aSurveyInfo['template']);
			ob_start(function($buffer, $phase) {
				App()->getClientScript()->render($buffer);
				App()->getClientScript()->reset();
				return $buffer;
			});
			ob_implicit_flush(false);
			sendCacheHeaders();
			doHeader();

			// Get the register.pstpl file content, but replace default by own string
			$output = file_get_contents($sTemplatePath.'/views/register.pstpl');
			$output = str_replace("{REGISTERFORM}", $registerpage_vars['REGISTERFORM'], $output);

			$registerpage_vars['thissurvey'] = $aSurveyInfo;
			echo templatereplace(file_get_contents($sTemplatePath.'/views/startpage.pstpl'),array(), $registerpage_vars);
			#echo templatereplace(file_get_contents($sTemplatePath.'/views/survey.pstpl'),array(), $aData);
			echo templatereplace($output);
			echo templatereplace(file_get_contents($sTemplatePath.'/views/endpage.pstpl'),array(), $registerpage_vars);
			doFooter();
			ob_flush();
			App()->end();
		}
	}


	public function osatAddLocales()
    {
        $event = $this->event;
        $stringToParse = $event->get('stringToParse');

		$stringToParse = $this->addReplacements($stringToParse);
		$stringToParse = $this->addTranslations($stringToParse);

		$this->event->set('stringToParse', $stringToParse);

        return $this->event;
    }

	public function addReplacements($string)
	{

		$settings = array('TERMS_OF_SERVICE', 'PRIVACY_POLICY', 'LEGAL_NOTICE', 'REQUIRED_ATTRIBUTES_TEXT', 'OPTIONAL_ATTRIBUTES_TEXT');

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

				$destination = array_shift($args);
				$dest_new = $destination;

				if(in_array($destination, $settings))
				{
					// receive settings text
					$text = $this->getSettings(strtolower($destination));
					if(empty($text) && ($this->getTranslator()->getCurrentLanguage() != $this->getTranslator()->getDefaultLanguage()))
					{
						$text = $this->getSettings(strtolower($destination) . '_' . $this->getTranslator()->getDefaultLanguage());
					}
					$dest_new = $text;
				}
				else if(preg_match('/_URL$/', $destination))
				{
					$type = strtolower(preg_replace('/_URL$/', '', $destination));
					$dest_new = $this->getUrl($type);
				}
				else if(preg_match('/_LINK$/', $destination))
				{
					$cmd = strtolower(preg_replace('/_LINK$/', '', $destination));
					$cmd = preg_replace('/[^a-z0-9\_]/i','',$cmd);
					$cmd = strtolower($cmd);
					$cmd = preg_replace_callback('/_([a-z])/', function($c) { return strtoupper($c[1]); }, $cmd);
					$cmd = 'get' . ucfirst($cmd) . 'Link';
					if(is_callable(array($this, $cmd)))
					{
						$dest_new = $this->$cmd();
					}
				}

				if($destination != $dest_new)
				{
					$destination = $dest_new;
					if(!empty($destination))
					{
						if(!empty($args[0]))
						{
							$destination = $args[0] . $destination;
						}
						if(!empty($args[1]))
						{
							$destination.= $args[1];
						}
					}
					$string = str_replace($source, $destination, $string);
				}
				unset($dest_new);
			}
		}
		return $string;
	}

	protected function continueSurvey(OsatUser $user)
	{
		$tokenId = $user->getToken();
		$surveyId = $user->getSurveyId();

		if(!empty($tokenId))
		{
			// read from survey db
			$oCriteria = new CDbCriteria;
	        $oCriteria->condition = "token=:token";
			$oCriteria->params = [
				'token' => $tokenId
			];

			$oResponses = SurveyDynamic::model($surveyId)->find($oCriteria);

			if (!$oResponses)
		    {
				return false;
		    }

#			LimeExpressionManager::SetDirtyFlag();
			buildsurveysession($surveyId);

			//A match has been found. Let's load the values!
	        //If this is from an email, build surveysession first
	        $_SESSION['survey_'.$surveyId]['LEMtokenResume'] = true;

	        // If survey come from reload (GET or POST); some value need to be found on saved_control, not on survey
	        $_SESSION['survey_'.$surveyId]['scid'] = null; // $oSavedSurvey->scid;
            $_SESSION['survey_'.$surveyId]['step'] = $oResponses->lastpage > 1 ? $oResponses->lastpage + 1 : 1;
            $_SESSION['survey_'.$surveyId]['srid'] = $oResponses->id;// Seems OK without
            $_SESSION['survey_'.$surveyId]['refurl'] = null; // $oSavedSurvey->refurl;

	        // Get if survey is been answered
	        $submitdate = $oResponses->submitdate;
	        $aRow = $oResponses->attributes;
	        foreach ($aRow as $column => $value)
	        {
	            if ($column == "token")
	            {
	                $clienttoken = $value;
	                $token = $value;
	            }
	            elseif ($column =='lastpage' && !isset($_SESSION['survey_'.$surveyId]['step']))
	            {
	                if(is_null($submitdate) || $submitdate=="N")
	                {
	                    $_SESSION['survey_'.$surveyId]['step'] = ($value>1? $value:1) ;
	                }
	                else
	                {
	                    $_SESSION['survey_'.$surveyId]['maxstep'] = ($value>1? $value:1) ;
	                }
	            }
	            elseif ($column == "datestamp")
	            {
	                $_SESSION['survey_'.$surveyId]['datestamp'] = $value;
	            }
	            if ($column == "startdate")
	            {
	                $_SESSION['survey_'.$surveyId]['startdate'] = $value;
	            }
	            else
	            {
	                //Only make session variables for those in insertarray[]
	                if (in_array($column, $_SESSION['survey_'.$surveyId]['insertarray']) && isset($_SESSION['survey_'.$surveyId]['fieldmap'][$column]))
	                {

	                    if (($_SESSION['survey_'.$surveyId]['fieldmap'][$column]['type'] == 'N' ||
	                    $_SESSION['survey_'.$surveyId]['fieldmap'][$column]['type'] == 'K' ||
	                    $_SESSION['survey_'.$surveyId]['fieldmap'][$column]['type'] == 'D') && $value == null)
	                    {   // For type N,K,D NULL in DB is to be considered as NoAnswer in any case.
	                        // We need to set the _SESSION[field] value to '' in order to evaluate conditions.
	                        // This is especially important for the deletenonvalue feature,
	                        // otherwise we would erase any answer with condition such as EQUALS-NO-ANSWER on such
	                        // question types (NKD)
	                        $_SESSION['survey_'.$surveyId][$column] = '';
	                    }
	                    else
	                    {
	                        $_SESSION['survey_'.$surveyId][$column] = $value;
	                    }
	                    if(isset($token) && !empty($token))
	                    {
	                        $_SESSION['survey_'.$surveyId][$column]=$value;
	                    }
	                }  // if (in_array(
	            }  // else
	        } // foreach

			return true;
		}

		return false;
	}

	protected function sendForgotPasswordEmail($email, $secret, $surveyId, $sLanguage)
	{
        if(!($aSurveyInfo = getSurveyInfo($surveyId, $sLanguage)) || empty($email) || empty($secret))
        {
            return false;
        }

		$aMail['subject'] = $this->getTranslator()->translate('Your password request for »%s«', $aSurveyInfo['surveyls_title']);
        $aMail['message'] = $this->getTranslator()->translate('We received a request for resetting your password - to proceed you have to follow the link:') . "\n\n" .
							App()->createAbsoluteUrl(
								"/survey/index/sid/{$surveyId}", [
									'action' => 'register',
									'function' => 'reset-password',
									'lang' => $sLanguage,
									'secret' => base64_encode($secret)
								],
								(!empty($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http')
							) . "\n\n" .
							$this->getTranslator()->translate('Note that this link will expire tomorrow midnight.') . "\n\n" .
							$this->getTranslator()->translate('If you don\'t want to reset your password you can ignore this email.');

        $sFrom = "{$aSurveyInfo['adminname']} <{$aSurveyInfo['adminemail']}>";
        $sBounce = getBounceEmail($surveyId);
        $sTo = $aSurveyInfo['adminemail'];
        $sitename =  Yii::app()->getConfig('sitename');

        return SendEmailMessage($aMail['message'], $aMail['subject'], $sTo, $sFrom, $sitename, false, $sBounce, null);
    }
}
