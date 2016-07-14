<?php
if(!class_exists('Osat', false))
{
	require_once(realpath(dirname(__FILE__) . '/../Osat/Osat.php'));
}
class OsatFeedback extends Osat {

	static protected $description = 'Feedback form';
	static protected $name = 'OSAT Feedback';
	static protected $label = 'osatfeedback';

    protected $menuLabel = "Feedback";
    protected $localeSettings = [
		'translate' => [
			'type' => 'text',
			'title' => 'Feedback',
			'help' => '"String to translate","Translation of the String" [, (optional) "Plural translation"'
		]
	];

	public function __construct(PluginManager $manager, $id)
    {
		parent::__construct($manager, $id);
        $this->_registerEvents();
	}

    protected function _registerEvents()
    {
		$this->subscribe('beforeAdminMenuRender');
        $this->subscribe('osatAddLocales');
		$this->subscribe('beforeControllerAction');
    }

	public function beforeControllerAction()
	{
		if(!empty($_SERVER['HTTP_OSATFEEDBACK_AJAX']))
		{
			if($result = $this->getFeedbackForm())
			{
				$myEvent = new PluginEvent('beforeEmManagerHelperProcessString');
				$myEvent->set('stringToParse', $result);
				App()->getPluginManager()->dispatchEvent($myEvent);
				$result = $myEvent->get('stringToParse');

				if(!empty($result))
				{
					http_response_code(200);
					echo $result;
					exit();
				}
			}
			http_response_code(404);
			exit();
		}
	}

    public function osatAddLocales()
    {
        $event = $this->event;
        $stringToParse = $event->get('stringToParse');
        $stringToParse = $this->insertFeedbackForm($stringToParse);
		$stringToParse = $this->addTranslations($stringToParse);
        $this->event->set('stringToParse', $stringToParse);

        return $this->event;
    }

    protected function getFeedbackFormData($key = null)
    {
        // this could be dynamic later
        $data = [
            'fields' => [
                'rating' => [
                    'type' => 'checkbox',
                    'multiple' => 0,
                    'options' => [
                        $this->getTranslator()->translate('Very helpful'),
                        $this->getTranslator()->translate('Nice tool'),
                        $this->getTranslator()->translate('Ok'),
                        $this->getTranslator()->translate('Could be better'),
                        $this->getTranslator()->translate('Won\'t recommend it')
                    ],
                    'title' => 'Rating',
                    'label' => 'Please rate our Europeanisation-Assessment-Tool:',
                    'required' => '1',
                    'value' => ''
                ],
                'feedback' => [
                    'type' => 'textarea',
                    'placeholder' => 'My feedback',
                    'title' => 'Feedback',
                    'label' => 'Please give us your feedback.',
                    'value' => ''
                ],
                'testimonial' => [
                    'type' => 'checkbox',
                    'multiple' => 1,
                    'title' => 'Testimonial',
                    'options' => [
                        $this->getTranslator()->translate('My Feedback can be used as a testimonial')
                    ],
                    'value' => ''
                ]
            ],

            'email_admin' => [
                'send' => 1,
                'to' => 'post@thomasgoellner.de',
                'reply-to' => '{email}',
                'subject' => 'A feedback form has been submitted',
                'message' => 'A user has used the feedback form and submitted a feedback:
Firstname : {firstname}
Lastname : {lastname}

Organisation: {attribute_5}
Organisation URL: {attribute_6}

Area of work: {attribute_1}
Size of organisation: {attribute_2}
Country: {attribute_2}

Rating: {rating}
Feedback: {feedback}
Testimonial: {testimonial}

Feedback form submitted on {senddate} from {remoteip} using the page {pageurl}'
            ]
        ];

        if(!empty($key) && is_string($key))
        {
            if(isset($data[$key]))
            {
                return $data[$key];
            }
            return null;
        }

        return $data;
    }

    public function getFeedbackForm($surveyId = null, $sToken = null, $sLanguage = null)
    {
		$surveyId = empty($surveyId) ? Yii::app()->request->getParam('sid') : $surveyId;
		$sToken = empty($sToken) ? (!empty($token) ? $token : Yii::app()->request->getParam('token', null)) : $sToken;
		$sLanguage = empty($sLanguage) ? App()->language : $sLanguage;

		if(empty($sToken))
		{
			if(isset($_SESSION['survey_'.$surveyId]['token']))
			{
				// try to get token from Session
				$sToken = $_SESSION['survey_'.$surveyId]['token'];
			}
			else {
				// try to get token from global var
				global $token;
				$sToken = $token;
			}

			// still empty - try to fetch it from a OsatUser
			if(empty($sToken))
			{
				if(class_exists('OsatUser'))
				{
					if($user = OsatUser::getUserFromSession())
					{
						$sToken = $user->getToken();
					}
				}
			}
		}


		if(empty($surveyId) || empty($sToken))
        {
            // no surveyId and no token given so we cannot display any assessment - nothing to do,
            // let's quit!
            return null;
        }

        if(!($tokenInstance = Token::model($surveyId)->editable()->findByAttributes(array('token' => $sToken))))
        {
            // token not found, we cannot send the form
            return null;
        }

        $data = $tokenInstance->getAttributes();
        $data = array_replace($data, [
			'surveyId' => $surveyId,
			'sToken' => $sToken,
            'sLanguage' => $sLanguage,
            'errors' => [],
            'submitted' => Yii::app()->request->getParam('osatfeedback', []),
            'fields' => $this->getFeedbackFormData('fields'),
            'urlAction' => Yii::app()->getRequest()->getRequestUri(),
            'email_sent' => false
		]);

        $mailreplacements = array_replace($tokenInstance->getAttributes(), [
            'senddate' => date('Y-m-d H:i:s'),
            'remoteip' => $_SERVER['REMOTE_ADDR'],
            'pageurl' => Yii::app()->getRequest()->getRequestUri(),
			'surveyId' => $surveyId,
			'sToken' => $sToken,
            'sLanguage' => $sLanguage
        ]);

        if($data['fields'] = $this->getFeedbackFormData('fields'))
        {
            foreach($data['fields'] as $label => $options)
            {
                if(!empty($data['submitted']))
                {
                    // let's check if this field is submitted too
                    if(isset($data['submitted'][$label]))
                    {
                        if(!empty($data['submitted'][$label]))
                        {
                            if(!empty($options['options']))
                            {
                                $options['options'] = (array) $options['options'];
                                $options['value'] = (array) $data['submitted'][$label];
								foreach($options['value'] as &$v)
								{
									$v = stripslashes($v);
								}
                                if(count(array_diff($options['value'], $options['options'])))
                                {
                                    $data['errors'][] = $this->getTranslator()->translate('Please provide a valid option in %s.', $this->getTranslator()->translate(!empty($options['title']) ? $options['title'] : $options['label']));
                                }
                                else
                                {
                                    $mailreplacements[$label] = join(', ', $options['value']);
                                }
                            }
                            else
                            {
                                $options['value'] = $data['submitted'][$label];
                                $mailreplacements[$label] = $options['value'];
                            }
                        }
                        else if(!empty($options['required']))
                        {
                            $data['errors'][] = $this->getTranslator()->translate('The field %s is required', $this->getTranslator()->translate(!empty($options['title']) ? $options['title'] : $options['label']));
                        }
                    }
                    $data['fields'][$label] = $options;
                }
            }
            unset($label, $options);
        }

        if(!empty($data['submitted']) && empty($data['errors']))
        {
            if($email_admin = $this->getFeedbackFormData('email_admin'))
            {
                if(!empty($email_admin))
                {
                    if($this->sendFeedbackEmail($email_admin, $mailreplacements))
                    {
                        $data['email_sent'] = true;
                    }
                    else
                    {
                        $data['errors'][] = $this->getTranslator()->translate('Thank you for your feedback - but we could not deliver it. Please try again or contact us.');
                    }
                }
            }
        }

        // and render the form!
        return Yii::app()->getController()->renderFile(dirname(__FILE__) . '/view/feedback_form.php', $data, true);
    }

    protected function insertFeedbackForm($string)
    {

		if(preg_match('/\{FEEDBACKFORM\}/',$string))
        {
            if($replace = $this->getFeedbackForm())
            {
                $string = str_replace('{FEEDBACKFORM}', $replace, $string);
            }
        }

        return $string;
    }

    protected function sendFeedbackEmail(array $preset = [], array $data = [])
    {
        if(!($aSurveyInfo = getSurveyInfo($data['surveyId'], $data['sLanguage'])))
        {
            return false;
        }

        if(empty($preset['subject']) || empty($preset['message']) || empty($preset['to']))
        {
            return false;
        }

        $aMail['subject'] = $preset['subject'];
        $aMail['message'] = $preset['message'];

        foreach($data as $label => $value)
        {
            $aMail['subject'] = preg_replace('/\{' . $label . '\}/i', trim(preg_replace("/(\n|\t|\r)/", '', $value)), $aMail['subject']);
            $aMail['message'] = preg_replace('/\{' . $label . '\}/i', trim($value), $aMail['message']);
        }
        $aMail['subject'] = preg_replace('/\{[^\}]+\}/i', '', $aMail['subject']);
        $aMail['message'] = preg_replace('/\{[^\}]+\}/i', '', $aMail['message']);

        if(empty($aMail['subject']) || empty($aMail['message']))
        {
            return false;
        }

        $sFrom = "{$aSurveyInfo['adminname']} <{$aSurveyInfo['adminemail']}>";
        $sBounce = getBounceEmail($data['surveyId']);
        $sTo = $aSurveyInfo['adminemail'];
        $sitename =  Yii::app()->getConfig('sitename');

        return SendEmailMessage($aMail['message'], $aMail['subject'], $sTo, $sFrom, $sitename, false, $sBounce, null);
    }

}
