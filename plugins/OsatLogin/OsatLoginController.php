<?php

class OsatLoginController extends LSYii_Controller {

    protected $function;

    protected $surveyId;
    protected $sLanguage;
    protected $sToken;

    protected $active = false;


    protected $user;
    protected $translator;

    public function __construct(array $attributes = [])
    {
        if(Yii::app()->request->getParam('action', null) == 'register')
        {
            $function = isset($attributes['function']) ? $attributes['function'] : Yii::app()->request->getParam('function', null);
            if($this->hasAction($function))
            {
                $attributes['function'] = $function;
                $this->init($attributes);
            }
            unset($function);
        }
    }

    public function init(array $attributes = [])
    {
        $this->surveyId = empty($attributes['surveyId']) ? Yii::app()->request->getParam('sid') : $attributes['surveyId'];
        $this->sToken = empty($attributes['sToken']) ? (isset($_SESSION['survey_'.$this->surveyId]['token']) ? $_SESSION['survey_'.$this->surveyId]['token'] : null) : $attributes['sToken'];
        $this->sLanguage = empty($attributes['sLanguage']) ? App()->language : $attributes['sLanguage'];
        $this->function = !empty($attributes['function']) ? $attributes['function'] : null;

        $this->user = !empty($attributes['user']) && $attributes['user'] instanceof OsatUser ? $attributes['user'] : null;
        $this->translator = !empty($attributes['translator']) && $attributes['translator'] instanceof OsatTranslator ? $attributes['translator'] : null;

        $this->active = true;

        return true;
    }

    public function isActive()
    {
        return $this->active === true;
    }

    public function doAction()
    {
        $function = $this->function . 'Action';
        return call_user_func_array(array($this, $function), func_get_args());
    }

    public function getUrl($function, array $attributes = [])
    {
        if($this->hasAction($function))
        {
            $attributes = array_replace(
                [
                    'lang' => $this->sLanguage
                ],
                $attributes,
                ['function' => $function]
            );

            return $this->createUrl($this->surveyId, $attributes);
        }
    }

    protected function camelCase($string)
    {
        $string = preg_replace('/[^a-z0-9\_]/i','',$string);
        $string = strtolower($string);
        $string = preg_replace_callback('/_([a-z])/', function($c) { return strtoupper($c[1]); }, $string);

        return $string;
    }

    public function hasAction($function)
    {
        $function = $this->camelCase($function);
        return method_exists($this, $function . 'Action');
    }

    public function loginAction()
    {
        if(!$this->active)
        {
            return null;
        }
    }

    public function logoutAction()
    {
        if(!$this->active)
        {
            return null;
        }
    }

    public function registerAction()
    {
        if(!$this->active)
        {
            return null;
        }
    }

    public function attributesAction()
    {
        if(!$this->active)
        {
            return null;
        }

        return 'Hello';
    }

    public function extraAttributesAction()
    {
        if(!$this->active)
        {
            return null;
        }
    }

    public function forgotPasswordAction()
    {
        if(!$this->active)
        {
            return null;
        }
    }

    public function resetPasswordAction()
    {
        if(!$this->active)
        {
            return null;
        }
    }
}
