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
        		$this->currentStepInfo = LimeExpressionManager::GetStepIndexInfo($step);
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

        if(empty($osatlogin))
        {
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
            $this->questionNo = 0;
            if($stepIndex = $this->getCurrentStepInfo())
        	{
        		$availableQuestions = LimeExpressionManager::GetStepIndexInfo();

        		foreach($availableQuestions as $i => $question)
        		{
        			if($question['qcode'] == $this->questionCode())
        			{
        				$this->questionNo = $i+1;
        			}
        		}
        	}
        }
        return !empty($this->questionNo) ? $this->questionNo : '';
    }

    public function _DOESNOTWORK_questionNo()
    {
#        print_r($_SESSION); die();
        if(!isset($this->questionNo))
        {
            $this->questionNo = 0;
            if($questions = $this->getAvailableQuestions())
            {
                if($stepIndex = $this->getCurrentStepInfo())
                {
                    foreach(array_keys($questions) as $i => $qid)
                    {
                        if($stepIndex['qid'] == $qid)
                        {
                            $this->questionNo = $i+1;
                            break;
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
    		return $stepIndex['qcode'];
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
}
