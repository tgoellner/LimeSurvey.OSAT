<?php

class OsatStats extends Osat {

	static protected $description = 'Enhanded statistic views';
	static protected $name = 'Osat Stats';
	static protected $label = 'osatstats';

    protected $localeSettings = [
		'translate' => [
			'type' => 'text',
			'title' => 'Translations',
			'help' => '"String to translate","Translation of the String" [, (optional) "Plural translation"'
		]
	];

	public function __construct(PluginManager $manager, $id)
    {
		parent::__construct($manager, $id);
# print_r($_SESSION); die();
        $this->_registerEvents();
	}

    protected function _registerEvents()
    {
		$this->subscribe('beforeAdminMenuRender');
        $this->subscribe('osatAddLocales');
		# $this->subscribe('beforeControllerAction');
        $this->subscribe('beforeSurveyPageOsatLate');
    }

	public function beforeControllerAction()
	{
        #$event = $this->event;
        die(".");
        #$event->set('run', 'Hello my dear.');
        # $GLOBALS['OSATSTATS'] = true;
	}

    public function beforeSurveyPageOsatLate()
    {
		$event = $this->event;
		$surveyId = $event->get('surveyId');

        global $tokenexist, $token, $previewmode, $thissurvey;

        $sToken = isset($token) ? $token : (empty($sToken) ? Yii::app()->request->getParam('token') : $sToken);
        $sLanguage = empty($sLanguage) ? App()->language : $sLanguage;

        if(empty($surveyId) || empty($sToken))
        {
            // OSAT User class exists?
            if(class_exists('OsatUser'))
            {
                if($user = OsatUser::getUserFromSession())
                {
                    if($user->isLoggedIn())
                    {
                        $surveyId = $user->getSurveyId();
                        $sToken = $user->getToken();
                    }
                }
            }
        }

        if(empty($surveyId) || empty($sToken) || empty($thissurvey))
        {
            return;
        }

        $display_assessmentspage = false;

        if (tableExists("{{tokens_".$surveyId."}}"))
        {
            if ($thissurvey['alloweditaftercompletion'] == 'Y' )
            {
                $tokenInstance = Token::model($surveyId)->editable()->findByAttributes(array('token' => $sToken));
            }
            else
            {
                $tokenInstance = Token::model($surveyId)->usable()->incomplete()->findByAttributes(array('token' => $sToken));
            }

            if (!isset($tokenInstance))
            {
                $oToken = Token::model($surveyId)->findByAttributes(array('token' => $sToken));
                if($oToken)
                {
                    $now = dateShift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", Yii::app()->getConfig("timeadjust"));
                    if($oToken->completed != 'N' && !empty($oToken->completed))// This can not happen (TokenInstance must fix this)
                    {
                        $display_assessmentspage = true;
                    }
                }
            }
        }

        if(!$display_assessmentspage)
        {
            return;
        }

        $_POST['osatbodycss'] = 'stats-complete';

        $data = [];
        if($data['languagechanger'] = makeLanguageChangerSurvey($sLanguage))
        {
            $data['languagechanger'] = '<form action="' . Yii::app()->createUrl("/survey/index/sid/{$surveyId}") . '">' . str_replace('name="langchanger"', 'name="lang"', $data['languagechanger']) . '</form>';
        }

        $sTemplatePath = getTemplatePath($thissurvey['template']);

        App()->getClientScript()->registerScriptFile(Yii::app()->getConfig('generalscripts')."../plugins/osatstats/assets/js/chartist/chartist.js");
        App()->getClientScript()->registerCssFile(Yii::app()->getConfig('generalscripts')."../plugins/osatstats/assets/js/chartist/chartist.css");
        App()->getClientScript()->registerScriptFile(Yii::app()->getConfig('generalscripts')."../plugins/osatstats/assets/js/osatstats.js");

        ob_start(function($buffer, $phase) {
            App()->getClientScript()->render($buffer);
            App()->getClientScript()->reset();
            return $buffer;
        });
        ob_implicit_flush(false);
        sendCacheHeaders();
        doHeader();

        // Get the register.pstpl file content, but replace default by own string
        $output = file_get_contents($sTemplatePath.'/views/assessment.pstpl');

        $data['thissurvey'] = $thissurvey;
        echo templatereplace(file_get_contents($sTemplatePath.'/views/startpage.pstpl'),array(), $data);
        echo templatereplace($output);
        echo templatereplace(file_get_contents($sTemplatePath.'/views/endpage.pstpl'),array(), $data);
        doFooter();
        ob_flush();
        App()->end();
    }



    public function osatAddLocales()
    {
        $event = $this->event;
        $stringToParse = $event->get('stringToParse');
        $stringToParse = $this->insertAssessment($stringToParse);
		$stringToParse = $this->addTranslations($stringToParse);
        $this->event->set('stringToParse', $stringToParse);

        return $this->event;
    }

    public function getAssessment($surveyId = null, $sToken = null, $sLanguage = null)
    {
		$surveyId = empty($surveyId) ? Yii::app()->request->getParam('sid') : $surveyId;
    	$sToken = empty($sToken) ? Yii::app()->request->getParam('token') : $sToken;
    	$sLanguage = empty($sLanguage) ? App()->language : $sLanguage;

        if(empty($surveyId) || empty($sToken))
        {
            // OSAT User class exists?
            if(class_exists('OsatUser'))
            {
                if($user = OsatUser::getUserFromSession())
                {
                    $surveyId = $user->getSurveyId();
                    $sToken = $user->getToken();
                }
            }
        }


        if(empty($surveyId) || empty($sToken))
        {
            // no surveyId and no token given so we cannot display any assessment - nothing to do,
            // let's quit!
            return null;
        }

        if($assessment = new OsatAssessment(['surveyId' => $surveyId, 'sToken' => $sToken]))
        {
            $data = [
                'assessment' => $assessment
            ];

            return Yii::app()->getController()->renderFile(dirname(__FILE__) . '/view/assessment.php', $data, true);
        }
        return '';
    }

    protected function insertAssessment($string)
    {
        if(preg_match('/\{ASSESSMENTS\}/',$string))
        {
            if($assessment = $this->getAssessment())
            {
                $string = str_replace('{ASSESSMENTS}', $assessment, $string);
            }
        }

        return $string;
    }
}
