<?php

class OsatExpressions
{

    public function __construct()
    {
    }

    protected function getSurveySession($key = null)
    {
        if(!isset($this->surveySession))
        {
            $this->surveySession = [];

            if($surveyId = Yii::app()->request->getParam('sid'))
        	{
        		$LEMsessid = 'survey_' . $surveyId;

        		if(!empty($_SESSION[$LEMsessid]))
        		{
                    $this->surveySession = $_SESSION[$LEMsessid];
                }
            }
        }

		if(!empty($key))
		{
			if(isset($this->surveySession[$key]))
			{
				return $this->surveySession[$key];
			}
            return null;
		}

    	return $this->surveySession;
    }

    protected function getCurrentStepInfo()
    {
        if(!isset($this->currentStepInfo))
        {
            $this->currentStepInfo = false;

        	$step = $this->stepIndex() - 1;
        	if($step >= 0)
        	{
        		$this->currentStepInfo = @LimeExpressionManager::GetStepIndexInfo($step);
        	}
        }

        return $this->currentStepInfo;
    }

    protected function getSurveyInfo()
    {
        if(!isset($this->surveyInfo))
        {
            $this->surveyInfo = false;
            if($iSurveyId = $this->surveyId())
            {
                $this->surveyInfo = Survey::model()->find("sid=:sid",array(':sid'=>$iSurveyId));
            }
        }

        return $this->surveyInfo;
    }

    protected function backupGlobals()
    {
        $this->GLOBALS = json_encode($GLOBALS);
    }

    protected function resetGlobals()
    {
        $GLOBALS = json_decode($this->GLOBALS, true);
    }

    protected function getAvailableGroups($surveyId = null)
    {
        if(!isset($this->availableGroups))
        {
            $this->availableGroups = [];
            $this->availableQuestions = [];

            $surveyId = $this->surveyId();

        	if(($allGroups = $this->getSurveySession('grouplist')) && count($allQuestions = $this->getAllQuestions()))
        	{
                $this->backupGlobals(); // need to store the session before we call LimeExpressionSingleton

                $LEM = LimeExpressionManager::singleton();

        		foreach($allGroups as $group)
        		{
        			$gid = $group['gid'];
        			if(!is_nan($gseq = (int) LimeExpressionManager::GetGroupSeq($gid)) && $gseq >= 0)
        			{
        				if($groupInfo = $LEM->_ValidateGroup($gseq))
        				{
        					if((bool) $groupInfo['relevant'])
        					{
        						$this->availableGroups[$gid] = $group;

                                // now go through all questions...
                                foreach($allQuestions as $qid => $question)
                                {
                                    if($question['gid'] == $gid)
                                    {
                                        if(!is_nan($qseq = (int) LimeExpressionManager::GetQuestionSeq($qid)) && $qseq >= 0)
                                        {
                                            if($questionInfo = $LEM->_ValidateQuestion($qseq))
                                            {
                                                if((bool) $questionInfo['relevant'])
                            					{
                                                    $this->availableQuestions[$qid] = $question;
                                                }
                                            }
                                            unset($questionInfo);
                                        }
                                        unset($qseq);

                                        unset($allQuestions[$qid]);
                                    }
                                }
                                unset($qid, $question);
        					}
        				}
                        unset($groupInfo);
        			}
                    unset($gid, $gseq);
        		}
                unset($allGroups, $group);

                $this->resetGlobals(); # $_SESSION = $_BACKUP_SESSION; // reset session now!
        	}
        }
    	return $this->availableGroups;
    }

    protected function getAvailableQuestions()
    {
        if(!isset($this->availableQuestions))
        {
            $this->availableQuestions = [];
            $this->getAvailableGroups();
        }
        return $this->availableQuestions;
    }

    protected function getAllQuestions()
    {
        if(!isset($this->allQuestions))
        {
            $this->allQuestions = [];

            if($allQuestions = $this->getSurveySession('fieldnamesInfo'))
        	{
    			if($allQuestionInfo = $this->getSurveySession('fieldmap'))
    			{
    				$field_keys = array_keys($allQuestions);

    				foreach($field_keys as $field_key)
    				{
    					if(!empty($allQuestionInfo[$field_key]))
    					{
                            $this->allQuestions[$allQuestionInfo[$field_key]['qid']] = $allQuestionInfo[$field_key];
                        }
                    }
                }
            }
        }

        return $this->allQuestions;
    }

    public function surveyState()
    {
        if(!isset($this->surveyState))
        {
            $this->surveyState = '';

        	if($iSurveyId = $this->surveyId())
        	{
        		if($sToken = $this->getSurveySession('token'))// Test invalid token ?
        		{
        			if($this->stepIndex() <= 0)
        			{
        				return 'survey-start';
        			}
        		}
        		else
        		{
        			// Get the survey model
        			if($oSurvey = $this->getSurveyInfo())
        			{
        				if($oSurvey->active=="Y")
        				{
        					if($oSurvey->allowregister=="Y" && tableExists("tokens_{$iSurveyId}"))
        					{
        						return 'survey-register';
        					}
        					return 'survey-no-registrations';
        				}
        				return 'survey-not-active';
        			}
        			return 'survey-not-found';
        		}
        		return '';
        	}
        }

    	return $this->surveyState;
    }

    public function osatPageCss()
    {
    	$css = [];

    	if($t = $this->surveyState())
    	{
    		$css[] = preg_replace('/[^a-z0-9\_\-]/', '', $t);
    	}

        if($osatbodycss = Yii::app()->request->getParam('osatbodycss'))
        {
            $css[] = $osatbodycss;
        }

    	if($t = $this->stepIndex())
    	{
    		$css[] = 'step-' . preg_replace('/[^a-z0-9\_\-]/', '', $t);
    	}
    	if($t = $this->questionNo())
    	{
    		$css[] = 'question-no-' . preg_replace('/[^a-z0-9\_\-]/', '', $t);
    	}
    	if($t = $this->questionCode())
    	{
    		$css[] = 'question-code-' . preg_replace('/[^a-z0-9\_\-]/', '', $t);
    	}
    	if($t = $this->groupNo())
    	{
    		$css[] = 'group-no-' . preg_replace('/[^a-z0-9\_\-]/', '', $t);
    	}
    	if($t = $this->groupId())
    	{
    		$css[] = 'group-id-' . preg_replace('/[^a-z0-9\_\-]/', '', $t);
    	}

    	if($t = $this->surveyId())
    	{
    		$css[] = 'survey-' . preg_replace('/[^a-z0-9\_\-]/', '', $t);
    	}

    	// is a save page ?
    	if(App()->request->getPost('saveall'))
    	{
    		$css[] = 'survey-save';
    	}

    	$css = array_filter($css);
    	$css = array_unique($css);

    	return join(' ', $css);
    }

    public function stepIndex()
    {
    	return $this->getSurveySession('step');
    }

    public function surveyId()
    {
    	if($surveyId = Yii::app()->request->getParam('sid'))
    	{
    		return $surveyId;
    	}
    	return '';
    }

    public function groupId()
    {
    	if(($stepIndex = $this->getCurrentStepInfo()) !== null)
    	{
    		return $stepIndex['gid'];
    	}

    	return '';
    }

    public function groupNo()
    {
    	if(($grouplist = $this->getSurveySession('grouplist')) !== null && ($gid = $this->groupId()) !== null)
    	{
    		foreach($grouplist as $key => $group)
    		{
    			if($group['gid'] == $gid)
    			{
    				return $key+1;
    			}
    		}
    	}

    	return '';
    }

    public function questionNo()
    {
        if(!isset($this->questionNo))
        {
            if(class_exists('OsatUser'))
            {
                if($user = OsatUser::getUserFromSession())
                {
                    if(($step = $this->getSurveySession('step')) !== null)
                    {
                        if(($finfo = $this->getSurveySession('fieldnamesInfo')) !== null)
                        {
                            $finfo = array_keys($finfo);
                            if(isset($finfo[$step-1]))
                            {
                                $currentQuestion = $finfo[$step-1];

                                if(($questions = $user->getQuestions()) !== null)
                                {
                                    $questions = array_keys($questions);

                                    if(array_search($currentQuestion, $questions) !== false)
                                    {
                                        $this->questionNo = array_search($currentQuestion, $questions) + 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if(!isset($this->questionNo))
            {
                $this->questionNo = 0;

                if($stepIndex = $this->getCurrentStepInfo())
                {
                    $availableQuestions = LimeExpressionManager::GetStepIndexInfo();

                    foreach($availableQuestions as $i => $question)
                    {
                        if(isset($question['qcode']) && $question['qcode'] == $this->questionCode())
                        {
                            $this->questionNo = $i+1;
                        }
                    }
                }
            }
        }
        return !empty($this->questionNo) ? $this->questionNo : '';
    }

    public function questionCode()
    {
    	if($stepIndex = $this->getCurrentStepInfo())
    	{
    		return isset($stepIndex['qcode']) ? $stepIndex['qcode'] : null;
    	}

    	return '';
    }

    public function groupTotal()
    {
    	if(($grouplist = $this->getSurveySession('grouplist')) !== null)
    	{
    		return count($grouplist);
    	}

    	return '';
    }

    protected function processGroupDescription($gid = null, $what = 'description')
    {
        $return = [
            'description' => '',
            'outtro' => '',
            'intro' => ''
        ];

        $description = null;
        $group_name = null;

        $div = '---';

        if(empty($gid))
        {
            $gid = $this->groupId();
        }

        if(($grouplist = $this->getSurveySession('grouplist')) !== null)
    	{
            foreach($grouplist as $group)
            {
                if($group['gid'] == $gid)
                {
                    $description = isset($group['description']) ? $group['description'] : null;
                    $group_name = isset($group['group_name']) ? $group['group_name'] : null;
                }
            }
    	}

        if($description === null)
        {
            if($group = QuestionGroup::model()->findByAttributes(array('sid' => $this->surveyId(), 'gid' => $gid, 'language' => App()->language)))
            {
                $description = $group->description;
                $group_name = $group->group_name;
            }
        }

        // process the description: split into SUMMARY and FULL sections (if any marker is found)
        if (!empty($description))
        {
            if (($pos = strpos($description, $div)) !== false)
            {
                $return['description'] = $this->validHtml(substr($description, 0, $pos));
                $return['outro'] = $this->validHtml(substr($description, $pos + strlen($div)));

                if (($pos = strpos($return['outro'] , $div)) !== false)
                {
                    $return['intro'] = strip_tags($return['description']);
                    $return['description'] = $this->validHtml(substr($return['outro'], 0, $pos));
                    $return['outro'] = $this->validHtml(substr($return['outro'], $pos + strlen($div)));
                }
            }
        }
        unset($pos);

        if(empty($return['intro']) && !empty($group_name))
        {
            $return['intro'] = $group_name;
        }

        if(!empty($what))
        {
            return isset($return[$what]) ? $return[$what] : '';
        }

        return $return;
    }

    public function groupdescription($gid = null)
    {
        return $this->processGroupDescription($gid, 'description');
    }
    public function groupintro($gid = null)
    {
        return $this->processGroupDescription($gid, 'intro');
    }
    public function groupoutro($gid = null)
    {
        return $this->processGroupDescription($gid, 'outro');
    }

    private function validHtml($html)
    {
        $html = trim($html);

        // remove open tag at the start of the string
        if (preg_match('/<[^>\/]+>$/', $html)) {
            $html = trim(preg_replace('/<[^>\/]+>$/', '', $html));
        }
        // remove closing tag at the start of the string
        if (preg_match('/^<\/[^>]+>/', $html)) {
            $html = trim(preg_replace('/^<\/[^>]+>/', '', $html));
        }

        // close unclosed tag at the end of the string
        if (!preg_match('/<\/[^>]+>$/', $html)) {
            preg_match_all('/<([a-z0-9]+)([^>\/]+)?>/', $html, $matches);

            if (!empty($matches[0])) {
                $close_tag = null;
                for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                    // ignore all possible self closing tags
                    if (!in_array($matches[1][$i], array(
                        'area',
                        'base',
                        'br',
                        'col',
                        'command',
                        'embed',
                        'hr',
                        'img',
                        'input',
                        'keygen',
                        'link',
                        'meta',
                        'param',
                        'source',
                        'track',
                        'wbr'
                    ))
                    ) {
                        $close_tag = $matches[1][$i];
                        break;
                    }
                }
                unset($i);

                if (!empty($close_tag)) {
                    $html .= '</' . $close_tag . '>';
                }
                unset($close_tag);
            }
            unset($matches);
        }

        // open unopened tag at the start of the string
        if (!preg_match('/^<[^>\/]+>/', $html)) {
            preg_match_all('/<\/([a-z0-9]+)>/', $html, $matches);
            if (!empty($matches[1][0])) {
                $html = '<' . $matches[1][0] . '>' . $html;
            }
            unset($matches);
        }

        return $html;
    }
}
