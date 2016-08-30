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
	protected $settings = [
		'options_default' => array(
            'type' => 'info',
            'label' => '',
			'content' => 'Plugin options'
        ),
		'survey_assessment_by_percentage' => array(
            'type' => 'checkbox',
            'label' => 'Calculate survey assessments by percentages',
            'default' => false
        ),
		'group_assessment_by_percentage' => array(
            'type' => 'checkbox',
            'label' => 'Calculate group assessments by percentages',
            'default' => false
        ),
	];
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
#
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

		if(!empty($_POST['action']) && $_POST['action'] == 'statspdf') {
			if($html = $_POST['html'])
			{
				$html = base64_decode($html);

				if(!empty($html)) {
					if($assessment = $this->getAssessment())
					{
						// create data
						if($data = $_POST['data'])
						{
							$data = base64_decode($data);
							$data = json_decode($data, true);

							if(!empty($data)) {
								$html = $this->replaceDiagram($html, $data);
							}
						}

						$options = [];
						if($tmp = $_POST['options'])
						{
							$tmp = base64_decode($tmp);
							$tmp = json_decode($tmp, true);

							if(!empty($tmp)) {
								$options = $tmp;
							}
						}

						$this->createStatsPdf($html, $options);
					}
				}
			}
		}
	}

	public function replaceDiagram($html, $bars, $width = 140, $height = 85, $prec = 2) {
		// let's split the HTML to extract the diagram

		if(($pos = strpos($html, '<div class="diagram"')) !== false)
		{
			$new_html = [];
			$new_html['top'] = substr($html, 0, $pos);
			$new_html['diagram'] = substr($html, strlen($new_html['top']), strpos($html, '<div class="wrapper"', strlen($new_html['top'])) - strlen($new_html['top']));
			$new_html['bottom'] = substr($html, strlen($new_html['top']) + strlen($new_html['diagram']));

			$svg = [
				'<?xml version="1.0" encoding="UTF-8" standalone="no"?>',
				'<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">',
				'<svg width="100%" height="100%" viewBox="0 0 ' . $width .' ' . $height .'" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;">'
			];

			$rect = '<rect x="{x}" y="{y}" width="{w}" height="{h}" style="fill:{f}"/>';

			$textheight = 5;
			$gap = 2;
			$lastgap = 5;
			$padding = 5;

			$_width = round(($width - (count($bars) - 2) * $gap - $lastgap - $padding * 2) / count($bars), $prec);
			$_height = $height - $textheight;

			// the grid lines
			$line = '<path d="M 0 {y} h ' . $width . '} {y}" fill="none" stroke-width="0.5" style="stroke-dasharray:0.5,1,0,0;stroke:rgb(21, 65, 148);" />';
			foreach([0, round($_height / 2, 2), $_height] as $y) {
				$svg[] = str_replace('{y}', $y, $line);
			}

			$lastpos = $padding;

			foreach($bars as $i => $bar)
			{
				$aw = round($_width * 0.25, $prec);
				$tw = $_width - (empty($bar['average']) ? 0 : $aw);

				// first the total bar
				$replace = [
					'x' => $lastpos,
					'h' => round($bar['total']['height'] * $_height, $prec),
					'w' => $tw,
					'f' => $bar['total']['color']
				];
				$replace['y'] = round($_height - $replace['h'], $prec);

				$_rect = $rect;
				foreach($replace as $k=>$v)
				{
					$_rect = str_replace('{' . $k . '}', $v, $_rect);
				}
				$svg[] = $_rect;

				// add the label
				$svg[] = '<text x="' . round($lastpos + ($_width / 2), $prec) . '" y="' . round($_height + ($textheight*0.75), 2) . '" style="font-family:\'roboto\', sans-serif;font-size:' . ($textheight * 0.75) . ';fill:rgb(21, 65, 148);" text-anchor="middle">' . $bar['label'] . '</text>';

				// add x to lastpos
				$lastpos+=$replace['w'];

				// then the average bar
				if(!empty($bar['average']))
				{
					$replace = [
						'x' => $lastpos,
						'h' => round($bar['average']['height'] * $_height, $prec),
						'w' => $aw,
						'f' => $bar['average']['color']
					];
					$replace['y'] = round($_height - $replace['h'], $prec);

					$_rect = $rect;
					foreach($replace as $k=>$v)
					{
						$_rect = str_replace('{' . $k . '}', $v, $_rect);
					}
					$svg[] = $_rect;
					$lastpos+=$replace['w'];
				}

				if($i == count($bars) - 2)
				{
					$lastpos+= $lastgap;
				}
				else if($i < count($bars) - 2)
				{
					$lastpos+= $gap;
				}
			}

			$svg[] = '</svg>';

			$svg = join("\n", $svg);

			$new_html['diagram'] = '<div class="wrapper">
				<div class="diagram" style="width:' . $width . 'mm;height' . $height . 'mm;">' .
				'<img src="data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg) . '" style="width:' . $width . 'mm;height' . $height . 'mm;" />' .
				'</div></div>';

			$html = join("", $new_html);
		}

		return $html;
	}

	public function createStatsPdf($html, array $options = [])
	{
		// everything looks nice - create a PDF!

		$options = array_replace([
			'title' => 'Personal document',
			'author' => Yii::app()->getConfig('sitename'),
			'watermarktext' => 'Some watermarktext'
		], $options);

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
		$mpdf->SetWatermarkText($options['watermarktext']);
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
			'hasAverages' => false,
			'survey_assessment_by_percentage' => (bool) $this->getSettings('survey_assessment_by_percentage'),
			'group_assessment_by_percentage' => (bool) $this->getSettings('group_assessment_by_percentage')
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
