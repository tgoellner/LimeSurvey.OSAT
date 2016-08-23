<?php
if(!class_exists('Osat', false))
{
	require_once(realpath(dirname(__FILE__) . '/../Osat/Osat.php'));
}
class OsatStats extends Osat {

	static protected $description = 'Enhanded statistic views';
	static protected $name = 'OSAT Stats';
	static protected $label = 'osatstats';

	protected $menuLabel = "Stats";
    protected $localeSettings = [
		'translate' => [
			'type' => 'text',
			'title' => 'Stats',
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
		$this->subscribe('beforeControllerAction');
        $this->subscribe('beforeSurveyPageOsatLate');
    }

	public function beforeControllerAction()
	{
		if(!empty($_SERVER['HTTP_OSATSTATS_AJAX']))
		{
			if($assessment = $this->getAssessment())
			{
				$myEvent = new PluginEvent('beforeEmManagerHelperProcessString');
				$myEvent->set('stringToParse', $assessment);
				App()->getPluginManager()->dispatchEvent($myEvent);
				$assessment = $myEvent->get('stringToParse');

				if(!empty($assessment))
				{
					http_response_code(200);
					echo $assessment;
					exit();
				}
			}
			http_response_code(404);
			exit();
		}

		if(Yii::app()->request->getParam('action') == 'statspdf') {
			if($html = $_POST['html'])
			{
				$html = urldecode($html);

				if(!empty($html)) {
				    if(!mb_check_encoding ($html, 'UTF-8' )) {
					    $html = utf8_encode($html);
					}

					if(mb_check_encoding ($html, 'UTF-8' )) {

						if($assessment = $this->getAssessment())
						{
							$this->createStatsPdf($html);
						}
					}
				}
			}
		}
	}

	public function createStatsPdf($html)
	{
		// everything looks nice - create a PDF!

		$options = [
			'title' => 'Personal document',
			'author' => Yii::app()->getConfig('sitename')
		];

		preg_match('/<h1>([^<]+)<\/h1>/', $html, $h1);
		preg_match('/<h2>([^<]+)<\/h2>/', $html, $h2);

		if($h1[1] && $h2[1])
		{
			$options['title'] = $h1[1] . ' ' . $h2[1];
		}

		require_once __DIR__ . '/mpdf/mpdf/mpdf.php';

		$mpdf = new Mpdf();
		$mpdf->setTitle($options['title']);
		$mpdf->setAuthor($options['author']);

		$mpdf->SetDisplayMode('fullpage');

		// LOAD a stylesheet
		$stylesheet = file_get_contents(__DIR__ . '/mpdf/style.css');

		$html = '<htmlpagefooter name="myFooter" id="myFooter">' .
				'<table width="100%" class="footer-table">' .
					'<tr>' .
						'<td width="30%" style="text-align: left;vertical-align: top;">' . $options['title'] . '</td>' .
						'<td width="20%" style="text-align: right;vertical-align: top;">{PAGENO}</td>' .
					'</tr>' .
				'</table>' .
				'</htmlpagefooter>' . $html;

		$html.= $image;
		$mpdf->SetWatermarkText('Personal document');
		$mpdf->watermark_font = 'roboto';
		$mpdf->watermarkTextAlpha = 0.1;
		$mpdf->showWatermarkText = true;

		$mpdf->WriteHTML($stylesheet,1);

		$mpdf->WriteHTML($html);

		$filename = preg_replace('/[^0-9a-z\_\-]/i', '_', $options['title']);
		$filename = preg_replace('/_{2,}/', '_', $filename);
		$filename = strtolower($filename);

		$mpdf->Output($filename . '.pdf', 'I');

		exit;
	}

    public function beforeSurveyPageOsatLate()
    {
		global $tokenexist, $token, $previewmode, $thissurvey;

		$event = $this->event;
		$surveyId = $event->get('surveyId');
        $sToken = isset($token) ? $token : (empty($sToken) ? Yii::app()->request->getParam('token') : $sToken);
        $sLanguage = empty($sLanguage) ? App()->language : $sLanguage;

		if(class_exists('OsatUser'))
		{
			if($user = OsatUser::getUserFromSession())
			{
				if($user->isLoggedIn())
				{
					// overwrite survey and token details by user session
					$surveyId = $user->getSurveyId();
					$sToken = $user->getToken();
				}
				else
				{
					unset($user);
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

			if(!empty($user))
			{
				if($user->hasJustCompletedSurvey())
				{
					$_POST['osatbodycss'] = 'survey-complete';
				}
			}
			elseif(!empty($_SESSION['survey_'.$surveyId]['grouplist']))
			{
				if(!empty($_SESSION['survey_'.$surveyId]['relevanceStatus']))
				{
					if(!empty($_SESSION['survey_'.$surveyId]['totalquestions']))
					{
						if(count($_SESSION['survey_'.$surveyId]['relevanceStatus']) >= $_SESSION['survey_'.$surveyId]['totalquestions'])
						{
							$_POST['osatbodycss'] = 'survey-complete';
						}
					}
				}
			}

			return;
        }

		$_POST['osatbodycss'] = 'survey-complete';

        $data = [];
        if($data['languagechanger'] = makeLanguageChangerSurvey($sLanguage))
        {
            $data['languagechanger'] = '<form action="' . Yii::app()->createUrl("/survey/index/sid/{$surveyId}") . '">' . str_replace('name="langchanger"', 'name="lang"', $data['languagechanger']) . '</form>';
        }

        $sTemplatePath = getTemplatePath($thissurvey['template']);

        ob_start(function($buffer, $phase) {
            App()->getClientScript()->render($buffer);
            App()->getClientScript()->reset();
            return $buffer;
        });
        ob_implicit_flush(false);
        sendCacheHeaders();
        doHeader();

        // Get the register.pstpl file content, but replace default by own string
        $template = 'osatstats.pstpl';

		if(!empty($user))
		{
			if($user->hasJustCompletedSurvey())
			{
				$_POST['osatbodycss'] = (!empty($_POST['osatbodycss']) ? $_POST['osatbodycss'] : '') . ' survey-is-just-completed';
				$template = 'completed.pstpl';
			}
		}

        if(!file_exists($templateFile = getTemplatePath($thissurvey['template']).'/views/' . $template))
		{
			if(!file_exists($templateFile = getTemplatePath().'/views/' . $template))
			{
				$templateFile = realpath(dirname(__FILE__) . '/view/' . $template);
			}
		}
        $output = file_get_contents($templateFile);

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

		// OSAT User class exists?
		if(class_exists('OsatUser'))
		{
			if($user = OsatUser::getUserFromSession())
			{
				$surveyId = $user->getSurveyId();
				$sToken = $user->getToken();
			}
		}

        if(empty($surveyId) || empty($sToken))
        {
            // no surveyId and no token given so we cannot display any assessment - nothing to do,
            // let's quit!
            return null;
        }

		$data = [
			'surveyId' => $surveyId,
			'sToken' => $sToken,
			'hasAverages' => false
		];

		$filter = $this->getRequest('filter');
		if(!empty($filter))
		{
			$data['hasAverages'] = true;
			if(empty($filter['reset']))
			{
				$data['filter'] = $filter;
			}
		}

		if($assessment = new OsatAssessment($data))
        {
            $data['assessment'] = $assessment;
			$data['header'] = !empty($user) && !$user->hasJustCompletedSurvey() ? Yii::app()->getController()->renderFile(dirname(__FILE__) . '/view/assessment_header.php', $data, true) : '';


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
