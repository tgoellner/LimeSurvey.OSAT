<?php

class OsatUser
{
    const salt = 'jabiduttiperslikkenberg';

    protected $expires = 30;

    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'language',
        'blacklisted'
    ];

    protected $validate = [
        'firstname' => 'required',
        'lastname' => 'required',
        'email' => 'required|email|unique'
    ];

    protected $original;
    protected $participant_id;
    protected $token;
    protected $attributes;
    protected $password;
    protected $surveyId;
    protected $exists;
    protected $translator;
    protected $errors;
    protected $notices;

    protected $status = [];

    public function __construct(array $attributes = [])
    {
        if(!empty($attributes['email']))
        {
            $surveyId = !empty($attributes['surveyId']) ? $attributes['surveyId'] : static::getCurrentSurveyId();

            $query = "SELECT * FROM {{tokens_$surveyId}} WHERE email = '" . $attributes['email'] . "'";
            $rows = Yii::app()->db->createCommand($query)->query()->readAll();
            if(count($rows) == 1)
            {
                // initialize the existing user
                $this->_init($rows[0]);

                // fill the given attributes
                $this->fill($attributes);

                // quit
                return $this;
            }
        }

        $this->_init($attributes);
    }

    protected function _init(array $attributes = [])
    {
        $this->original = null;
        $this->participant_id = null;
        $this->token = null;
        $this->attributes = [];
        $this->password = null;
        $this->surveyId = null;
        $this->exists = null;
        $this->translator = null;
        $this->errors = [];
        $this->notices = [];

        $attributes = $this->setTranslator($attributes);
        $attributes = $this->setToken($attributes);
        $attributes = $this->setParticipantId($attributes);
        $attributes = $this->setSurveyId($attributes);
        $attributes = $this->fill($attributes);
        $attributes = $this->initAttributes($attributes);
        $this->setOriginal();
    }

    protected function setTranslator(array $attributes = [])
    {
        if(isset($attributes['translator']) && $attributes['translator'] instanceof OsatTranslator)
        {
            $this->translator = $attributes['translator'];
        }
        unset($attributes['translator']);

        return $attributes;
    }

	public function getTranslator($pluginonly = false)
	{
		if($this->translator == null)
		{
			$this->setTranslator(['translator' => new OsatTranslator([
				'folder' => dirname(__FILE__) . '/../OsatLogin'
			])]);
		}
		return $this->translator;
	}

    protected function setToken(array $attributes = [])
    {
        if(isset($attributes['token']))
        {
            $this->token = $attributes['token'];
            unset($attributes['token']);
        }

        return $attributes;
    }

    public function getToken()
    {
        if(!empty($this->token))
        {
            return $this->token;
        }
        return null;
    }

    protected function setParticipantId(array $attributes = [])
    {
        if(isset($attributes['participant_id']))
        {
            $this->participant_id = $attributes['participant_id'];
            unset($attributes['participant_id']);
        }

        return $attributes;
    }

    public function getParticipantId()
    {
        if(!empty($this->participant_id))
        {
            return $this->participant_id;
        }
        return null;
    }

    protected function setOriginal()
    {
        $this->original = [];
        foreach($this->fillable as $k)
        {
            $this->original[$k] = isset($this->$k) ? $this->$k : null;
        }
    }

    protected function setSurveyId(array $attributes = [])
    {
        if(!isset($attributes['surveyId']))
        {
            $attributes['surveyId'] = static::getCurrentSurveyId();
        }
        $this->surveyId = $attributes['surveyId'];
        unset($attributes['surveyId']);

        return $attributes;
    }

    public function getSurveyId()
    {
        return $this->surveyId;
    }

    protected function initAttributes(array $attributes = [])
    {
        $pAttributes = [];
        if($surveyId = $this->surveyId)
        {
            if($aSurveyInfo = getSurveyInfo($surveyId, App()->language))
            {
                if(!empty($aSurveyInfo['attributedescriptions']))
                {
                    foreach($aSurveyInfo['attributedescriptions'] as $tLabel => $tOptions)
                    {
                        $tOptions['visible'] = $tOptions['show_register'] == 'Y'; unset($tOptions['show_register']);
                        $tOptions['attribute_id'] = $tOptions['cpdbmap']; unset($tOptions['cpdbmap']);
                        $tOptions['attribute_type'] = 'TB';
                        $tOptions['token_attribute_label'] = $tLabel;
                        $tOptions['options'] = '';
                        if($t = strtolower(preg_replace('/[^A-Z0-9\_]/i','', $tOptions['description'])))
                        {
                            $tOptions['label'] = $t;
                        }
                        else
                        {
                            $tOptions['label'] = $tLabel;
                        }

                        if(!empty($tOptions['attribute_id']))
                        {
                            // map to an existing attribute
                            if($gAttribute =  ParticipantAttributeName::model()->getAttribute($tOptions['attribute_id']))
                            {
                                unset($gAttribute['visible']);
                                if(empty($tOptions['description']))
                                {
                                    $tOptions['description'] = $gAttribute['defaultname'];
                                }
                                if($t = strtolower(preg_replace('/[^A-Z0-9\_]/i','', $gAttribute['defaultname'])))
                                {
                                    $tOptions['label'] = $t;
                                }
                                unset($gAttribute['defaultname']);
                                $tOptions = array_replace($tOptions, $gAttribute);

                                foreach(ParticipantAttributeName::model()->getAttributesValues($gAttribute['attribute_id']) as $value)
                                {
                                    $tOptions['options'][$value['value_id']] = $value['value'];
                                }
                                unset($value);
                            }
                            unset($gAttribute);
                        }

                        // generate captions
                        $tOptions['caption'] = !empty($aSurveyInfo['attributecaptions'][$tLabel]) ? $aSurveyInfo['attributecaptions'][$tLabel] : $this->getTranslator()->translate($tOptions['description']);

                        $this->attributes[$tLabel] = $tOptions;

                        $this->fillable[] = $tLabel;
                    }
                }
            }
        }

        if($participantId = $this->participant_id)
        {
            // let's get the stored attributes for this participant from the central database
            foreach($this->attributes as $label => $options)
            {
                if($values = ParticipantAttributeName::model()->getAttributeValue($participantId, $options['attribute_id']))
                {
                    $this->$label = $values['value'];
                }
            }
        }

        // and now let's fill the attributes too!
        return $this->fill($attributes);
    }

    protected function encrypt($string)
    {
        return md5($string);
    }

    protected function getPasswordAttribute()
    {
        // is there an password attribute set?
        foreach($this->attributes as $label => $options)
        {
            if($options['label'] == 'password')
            {
                return $label;
            }
        }

        return null;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getPassword()
    {
        if($label = $this->getPasswordAttribute())
        {
            if(!empty($this->$label))
            {
                return $this->$label;
            }
            return null;
        }

        // if not we use the token as Password
        if(!empty($this->token))
        {
            return $this->token;
        }

        return null;
    }

    public function setPassword($password)
    {
        if(!empty($password) && is_string($password))
        {
            foreach($this->attributes as $label => $options)
            {
                if($options['label'] == 'password')
                {
                    $this->$label = $this->encrypt($password);
                }
            }
        }

        return false;
    }

    public function get($key = null)
    {
        if(!empty($key))
        {
            if(is_string($key) && isset($this->$key))
            {
                return $this->$key;
            }

            return null;
        }
        $return = [];
        foreach($this->fillable as $key)
        {
            $return[$key] = $this->get($key);
        }
        return $return;
    }

    protected function getChangedValues()
    {
        $changed = [];

        foreach($this->original as $k => $v)
        {
            $test_value = isset($this->$k) ? $this->$k : null;
            if($test_value !== $v)
            {
                $changed[$k] = $this->$k;
            }
        }

        return !empty($changed) ? $changed : null;
    }

    public function hasChanged()
    {
        return count($this->getChangedValues()) > 0;
    }

    protected function createToken(array $attributes)
    {
        if(!$this->exists())
        {
            $aSurveyInfo = getSurveyInfo($this->surveyId);
            $sLanguage = Yii::app()->request->getParam('lang','');
            if($sLanguage == "")
            {
                $sLanguage = Survey::model()->findByPk($this->surveyId)->language;
            }

            if($oToken= Token::create($this->surveyId))
            {
                $oToken->firstname = $attributes['firstname'];
                $oToken->lastname = $attributes['lastname'];
                $oToken->email = $attributes['email'];
                $oToken->emailstatus = 'OK'; // should be changed when DoubleOptIn
                $oToken->language = $sLanguage;

                // get all attributes...
                $tAttributes = [];
                foreach($this->attributes as $k => $v)
                {
                    if(isset($this->$k))
                    {
                        $tAttributes[$k] = $this->$k;
                    }
                }

                $oToken->setAttributes($tAttributes);

                if ($aSurveyInfo['startdate'])
                {
                    $oToken->validfrom = $aSurveyInfo['startdate'];
                }
                if ($aSurveyInfo['expires'])
                {
                    $oToken->validuntil = $aSurveyInfo['expires'];
                }

                $oToken->save();

                $iTokenId = $oToken->tid;

                TokenDynamic::model($this->surveyId)->createToken($iTokenId);

                return $iTokenId;
            }
        }

        return null;
    }

    protected function sanitizeValues(array $attributes = [])
    {
        foreach($attributes as $k => &$v)
        {
            if($v !== null)
            {
                if(is_object($v) || is_array($v))
                {
                    $v = json_encode($v);
                }

                $v = (string) $v;
                $v = sanitize_xss_string($v);
            }
        }

        return $attributes;
    }

    protected function validateValues(&$attributes)
    {
        $attributes = $this->sanitizeValues($attributes);
        $password_attribute = $this->getPasswordAttribute();

        foreach($attributes as $field => &$test_value)
        {
            if(!empty($password_attribute) && $field == $password_attribute)
            {
                // check if password is not empty
                if(empty($test_value))
                {
                    $this->errors[] = $this->getTranslator()->translate('%s is required but empty', $this->getTranslator()->translate('Password'));
                }
            }
            else if($field == 'participant_id')
            {
                // check if participant id exists in participant table
                if(!empty($test_value))
                {
                    $surveyId = $this->surveyId;
                    $query = "SELECT * FROM {{tokens_$surveyId}} WHERE participant_id = '$test_value'";
                    $rows = Yii::app()->db->createCommand($query)->query()->readAll();
                    if(!count($rows))
                    {
                        $this->errors[] = $this->getTranslator()->translate('%s is not a valid participant id', $test_value);
                    }
                }
            }
            else if(!empty($this->validate[$field]))
            {
                $options = explode('|', $this->validate[$field]);
                foreach($options as $option)
                {
                    if($option = strtolower(preg_replace('/[^0-9a-z\:]/i','',$option)))
                    {
                        switch($option)
                        {
                            case 'required' :
                                if(empty($test_value))
                                {
                                    $this->errors[] = $this->getTranslator()->translate('%s is required but empty', $this->getTranslator()->translate(ucfirst($field)));
                                }
                                break;
                            case 'email' :
                                if(!filter_var($test_value, FILTER_VALIDATE_EMAIL))
                                {
                                    $this->errors[] = $this->getTranslator()->translate('%1$s in %2$s is not a valid email address', $test_value, $this->getTranslator()->translate(ucfirst($field)));
                                }
                                break;
                            case 'number' :
                                $test_value = intval($test_value);
                                if(is_nan($test_value))
                                {
                                    $this->errors[] = $this->getTranslator()->translate('%1$s in %2$s is not a number', $test_value, $this->getTranslator()->translate(ucfirst($field)));
                                }
                                break;
                            case 'float' :
                                $test_value = floatval($test_value);
                                if(is_nan($test_value))
                                {
                                    $this->errors[] = $this->getTranslator()->translate('%1$s in %2$s is not a decimal', $test_value, $field);
                                }
                                break;
                            case 'unique' :
                                $query = "SELECT * FROM {{tokens_" . $this->getSurveyId() . "}} WHERE `$field` = '" . $test_value . "' AND `token` <> '". $this->getToken() . "' LIMIT 1";
                                $rows = Yii::app()->db->createCommand($query)->query()->readAll();
                                if(count($rows) == 1)
                                {
                                    $this->errors[] = $this->getTranslator()->translate('%s is already taken by another user.', $test_value);
                                }
                                break;
                        }
                    }
                }
            }
        }

        return empty($this->errors);
    }

    public function save()
    {
        $this->errors = [];

        if($this->exists())
        {
            if($values = $this->getChangedValues())
            {
                if($this->validateValues($values))
                {
                    $surveyId = $this->surveyId;

                    $query = [];
                    foreach($values as $k => $v)
                    {
                        $query[] = "$k = '$v'";
                    }
                    $query = join(", ", $query);

                    $query = "UPDATE {{tokens_$surveyId}} SET " . $query . " WHERE token = '" . $this->getToken() ."'";
                    if(Yii::app()->db->createCommand($query)->execute())
                    {
                        $this->setOriginal();
                        return true;
                    }
                }
            }
        }
        else
        {
            $values = $this->get();
            if($this->validateValues($values))
            {
                // create token!
                if($iTokenId = $this->createToken($values))
                {
                    $surveyId = $this->surveyId;

                    $query = "SELECT * FROM {{tokens_$surveyId}} WHERE tid = '$iTokenId'";
                    $rows = Yii::app()->db->createCommand($query)->query()->readAll();
                    if(count($rows) == 1)
                    {
                        $this->_init($rows[0]);
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function checkPassword($password)
    {
        if($thisPassword = $this->getPassword())
        {
            if($password == $thisPassword)
            {
                // password matches but it is not encrypted yet! encrypt!
                $this->setPassword($password);
            }

            if($this->encrypt($password) == $thisPassword)
            {
                return true;
            }
        }

        return false;
    }

    public function isLoggedIn()
    {
        if($this->getUserSession())
        {
            return true;
        }
        return false;
    }

    public function login($password = null)
    {
        if($password !== null)
        {
            if(!$this->checkPassword($password))
            {
                $this->logout();
            }
            else
            {
                $this->getUserSession(true);
            }
        }

        if($this->getUserSession())
        {
            return true;
        }
    }

    public function logout()
    {
        return $this->deleteUserSession();
    }

    protected function getUserSession($create = false)
    {
        if($this->exists())
        {
            if(empty($_SESSION['osat']) && (bool) $create)
            {
                $_SESSION['osat'] = array();
            }
            if(empty($_SESSION['osat']['login']) && (bool) $create)
            {
                $_SESSION['osat']['login'] = array();
            }

            if((bool) $create)
            {
                $_SESSION['osat']['login'] = [
                    'token' => $this->getToken(),
                    'expires' => time() + ($this->expires * 60)
                ];
            }
            else
            {
                if(isset($_SESSION['osat']['login']['token']) && $_SESSION['osat']['login']['token'] == $this->getToken())
                {
                    if($_SESSION['osat']['login']['expires'] < time())
                    {
                        // session expired
                        $this->logout();
                    }
                    else
                    {
                        // refresh session
                        $_SESSION['osat']['login']['expires'] = time() + ($this->expires * 60);
                    }
                }
            }

            if(isset($_SESSION['osat']['login']['token']) && $_SESSION['osat']['login']['token'] == $this->getToken())
            {
                return true;
            }
        }

        return false;
    }

    protected function deleteUserSession()
    {
        if(isset($_SESSION['osat']['login']))
        {
            unset($_SESSION['osat']['login']);
        }

        return true;
    }

    public static function getUserFromSession($translator = null)
    {
        if(isset($_SESSION['osat']['login']['token']) && $_SESSION['osat']['login']['expires'] > time())
        {
            if($user = static::findByToken($_SESSION['osat']['login']['token'], null, $translator))
            {
                if($user->login())
                {
                    return $user;
                }
            }
        }
        unset($_SESSION['osat']['login']);

        return null;
    }

    public function setSessionVar($key, $value)
    {
        if($this->isLoggedIn() && !empty($key))
        {
            if($value === null)
            {
                unset($_SESSION['osat']['login'][$key]);
            }
            elseif(!in_array($key, ['token', 'expires']))
            {
                $_SESSION['osat']['login'][$key] = $value;
            }
            return true;
        }
        return false;
    }

    public function getSessionVar($key)
    {
        if($this->isLoggedIn() && !empty($key))
        {
            return isset($_SESSION['osat']['login'][$key]) ? $_SESSION['osat']['login'][$key] : null;
        }
        return false;
    }

    public function exists()
    {
        if($this->exists === null)
        {
            $this->exists = false;

            $surveyId = $this->surveyId;

            if($token = $this->getToken())
            {
                // let's check if the participant_id is found
                $query = "SELECT token FROM {{tokens_$surveyId}} WHERE token = '$token'";
            }
            else if($email = $this->email)
            {
                $query = "SELECT token FROM {{tokens_$surveyId}} WHERE email = '$email'";
            }

            $rows = Yii::app()->db->createCommand($query)->query()->readAll();
            if(count($rows) == 1)
            {
                $this->exists = true;
            }
        }

        return $this->exists;
    }

    protected static function getCurrentSurveyId()
    {
        return Yii::app()->request->getParam('sid');
    }

    protected static function _load($key, $value, $surveyId = null, $translator = null)
    {
        if(empty($surveyId))
        {
            $surveyId = static::getCurrentSurveyId();
        }

        if(!empty($surveyId))
        {
            $query = "SELECT * FROM {{tokens_$surveyId}} WHERE $key = '$value'";
            $rows = Yii::app()->db->createCommand($query)->query()->readAll();
            if(count($rows) == 1)
            {
                $rows[0]['surveyId'] = $surveyId;
                $rows[0]['translator'] = $translator;
                $user = new static($rows[0]);
                return $user;
            }
        }
        return null;
    }

    public static function findByParticipantId($participant_id, $surveyId = null, $translator = null)
    {
        return static::_load('participant_id', $participant_id, $surveyId, $translator);
    }

    public static function findByEmail($email, $surveyId = null, $translator = null)
    {
        return static::_load('email', $email, $surveyId, $translator);
    }

    public static function findByToken($token, $surveyId = null, $translator = null)
    {
        return static::_load('token', $token, $surveyId, $translator);
    }

    public static function find($tid, $surveyId = null, $translator = null)
    {
        return static::_load('tid', $tid, $surveyId, $translator);
    }

    public function fill(array $attributes = [])
    {
        if(!empty($attributes))
        {
            foreach($attributes as $key => $value)
            {
                if(in_array($key, $this->fillable))
                {
                    if($value === null)
                    {
                        unset($this->key);
                    }
                    else
                    {
                        $this->$key = $value;
                    }
                    unset($attributes[$key]);
                }
            }
        }

        return $attributes;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    protected function getAttributesByManatory($mandatory = true)
    {
        $attributes = [];
        $mandatory = (bool) $mandatory ? 'Y' : 'N';

        foreach($this->attributes as $label => $options)
        {
            if($this->getPasswordAttribute() != $label && $options['mandatory'] == $mandatory)
            {
                $options['value'] = isset($this->$label) ? $this->$label : '';
                $options['description'] = $this->getTranslator()->translate($options['description']);
                $attributes[$label] = $options;
            }
        }
        return $attributes;
    }

    public function getMandatoryAttributes()
    {
        return $this->getAttributesByManatory(true);
    }

    public function getOptionalAttributes()
    {
        return $this->getAttributesByManatory(false);
    }



    public function getMissingAttributes()
	{
		$values = [];
		foreach($this->getMandatoryAttributes() as $label => $options)
		{
			if(!empty($options['options']))
			{
				if(!isset($this->$label) || !in_array($this->$label, (array) $options['options']))
				{
					$values[$label] = $options;
				}
			}
			else if(empty($this->$label))
			{
				$values[$label] = $options;
			}
		}

		return $values;
	}

    public function hasMissingAttributes()
    {
        return count($this->getMissingAttributes()) > 0;
    }

	public function getMissingExtraAttributes()
	{
        $values = [];
		foreach($this->getOptionalAttributes() as $label => $options)
		{
			if(!empty($options['options']))
			{
				if(!in_array($this->$label, (array) $options['options']))
				{
					$values[$label] = $options;
				}
			}
			else if(empty($this->$label))
			{
				$values[$label] = $options;
			}
		}

		return $values;
	}

    public function hasMissingExtraAttributes()
    {
        return count($this->getMissingExtraAttributes()) > 0;
    }

    public function hasCompletedSurvey()
    {
        if(!isset($this->status['hasCompletedSurvey']))
        {
            $this->status['hasCompletedSurvey'] = false;

            if($tokenInstance = Token::model($this->surveyId)->editable()->findByAttributes(array('token' => $this->token)))
    		{
    			if($tokenInstance->completed != "N")
    			{
    				$this->status['hasCompletedSurvey'] = true;
                }
            }
        }
        return $this->status['hasCompletedSurvey'];
    }

    public function hasJustCompletedSurvey()
    {
        $t = $this->getSessionVar('hasJustCompletedSurvey');
        if($t!== null)
        {
            return $t;
        }

        if(!isset($this->status['hasJustCompletedSurvey']))
        {
            $this->status['hasJustCompletedSurvey'] = false;

            if(!empty($_SESSION['survey_'.$this->surveyId]['grouplist']))
			{
				if(!empty($_SESSION['survey_'.$this->surveyId]['relevanceStatus']))
				{
					if(!empty($_SESSION['survey_'.$this->surveyId]['totalquestions']))
					{
                        $this->setSessionVar('hasJustCompletedSurvey', true);
                        return true;
					}
				}
			}
        }
        return false;
    }

    public static function findByForgotPasswordSecret($secret, $surveyId = null, $translator = null)
    {
        if($user = new static())
        {
            $pAttr = $user->getPasswordAttribute() ? $user->getPasswordAttribute() : null;
            $query = "encrypt(md5(CONCAT(`email`, `token`" . (!empty($pAttr) ? ", `$pAttr`" : "") . ", CURRENT_DATE())), '" . static::salt . "')";
            return static::_load($query, $secret, $surveyId, $translator);
        }
        return null;
    }

    public static function getForgotPasswordSecret($email, $surveyId = null)
    {
        if(empty($surveyId))
        {
            $surveyId = static::getCurrentSurveyId();
        }

        if($user = new static())
        {
            $pAttr = $user->getPasswordAttribute() ? $user->getPasswordAttribute() : null;
            $query = "SELECT encrypt(md5(CONCAT(`email`, `token`" . (!empty($pAttr) ? ", `$pAttr`" : "") . ", CURRENT_DATE())), '" . static::salt . "') AS `secret` FROM {{tokens_$surveyId}} WHERE `email` = '$email' LIMIT 1";
            $rows = Yii::app()->db->createCommand($query)->query()->readAll();
            if(count($rows))
            {
                return $rows[0]['secret'];
            }
        }
        return null;
    }
}
