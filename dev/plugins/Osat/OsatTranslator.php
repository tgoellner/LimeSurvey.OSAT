<?php

class OsatTranslator
{
    protected $folder = null;
    protected $language = null;

    protected $strings = [];
    protected $appendedStrings = [];
    protected $settings = '';

    public function __construct(array $attributes = [])
    {
        if(isset($attributes['lang']))
        {
            $this->setLanguage($attributes['lang']); # sets the folder
        }

        if(isset($attributes['settings']))
        {
            $this->setSettings($attributes['settings']); # sets the folder
        }

        if(isset($attributes['folder']))
        {
            $this->setFolder($attributes['folder']); # sets the folder
        }

        $this->reload(); # loads the strings
    }

    public function setSettings($settings)
    {
        if(!empty($settings))
        {
            $default = null;
            $locale = null;

            foreach($settings as $key => $options)
            {
                if(!preg_match('/_translate_/', $key))
                {
                    continue;
                }
                $content = is_string($options) ? $options : (isset($options['current']) ? $options['current'] : '');

                if(!empty($content))
                {
                    if(preg_match('/_' . static::getDefaultLanguage() . '$/i', $key))
                    {
                        $default = $content;
                        if(static::getDefaultLanguage() == $this->getLanguage())
                        {
                            // default is the same as the current language - skip!
                            break;
                        }
                    }
                    elseif(preg_match('/_' . $this->getLanguage() . '$/i', $key))
                    {
                        $locale = $content;
                        if(!$default !== null)
                        {
                            // default is also defined, nothing more to do
                            break;
                        }
                    }
                }
            }
            $this->settings = $default . "\n" . $locale;

        }

        return $this->settings;
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function setFolder($folder)
    {
        if(empty($folder))
        {
            $folder = dirname(__FILE__);
        }

        $folder = (string) $folder;
        $checkfolder = realpath($folder);
        if(empty($checkfolder))
        {
            $checkfolder = realpath(dirname(__FILE__) . '/../' . $folder);
            if(empty($checkfolder))
            {
                throw new Exception(sprintf('Folder %s is not found.', $folder));
            }
            else
            {
                $folder = $checkfolder;
            }
        }
        else
        {
            $folder = $checkfolder;
        }

        if(file_exists($dir = $folder . '/locale'))
        {
            if(is_dir($dir))
            {
                $this->folder = $dir;
                return $this->folder;
            }
        }

        throw new Exception(sprintf('Folder %s is not found or is not a valid directory', $dir));
    }

    public function getFolder()
    {
        return $this->folder;
    }

    public function setLanguage($language)
    {
        $this->language = !empty($language) ? $language : static::getCurrentLanguage();
        return $this->language;
    }

    public function getLanguage()
    {
        if(empty($this->language))
        {
            $this->setLanguage(static::getCurrentLanguage());
        }
        return $this->language;
    }

    public function reload($globals_too = false)
    {
        global $_OSAT;
        $key = md5($this->getFolder());
        $lang = $this->getLanguage();

        if((bool) $globals_too)
        {
            if(isset($_OSAT[$key][$lang]))
            {
                unset($_OSAT[$key][$lang]);
            }
        }

        if(!isset($_OSAT[$key][$lang]))
        {
            $messages = [];

            // load the CSV file of the default language (as fallback)
            if($loaded = $this->loadCsv(static::getDefaultLanguage()))
            {
                $messages = array_replace($messages, $this->parseCsv($loaded));
            }

            if($lang != static::getDefaultLanguage())
            {
                // load the CSV file of the defined language (overwrites the default strings if already existing)
                if($loaded = $this->loadCsv())
                {
                    $messages = array_replace($messages, $this->parseCsv($loaded));
                }
            }

            // read the settingsfrom DB
            if($loaded = $this->getSettings())
            {

                // add it to the messages (those defined in CSV might be overwritten)
                $messages = array_replace($messages, $this->parseCsv($loaded));
            }

            // set the global value
            $_OSAT[$key][$lang] = $messages;
        }

        $this->strings = $_OSAT[$key][$lang];
    }

    public function appendTranslationStrings(array $strings)
    {
        if(!empty($strings))
        {
            $this->appendedStrings = array_replace($this->appendedStrings, $strings);
        }
    }

    public function getTranslationStrings()
    {
        return array_replace($this->strings, $this->appendedStrings);
    }

    public function getTranslationString($string, $isPlural = false)
    {
        $messages = $this->getTranslationStrings();

        $key = $string; // addslashes($string);
        if(isset($messages[$key]))
        {
            $string = (array) $messages[$key];

            if((bool) $isPlural && is_array($string) && isset($string[1]))
            {
                $string = $string[1];
            }
            else
            {
                $string = $string[0];
            }
        }
        else
        {
            // use gT() if no field was found...
            $string = gT($string, 'unescaped');
        }

        return $string;
    }

    protected function loadCsv($language = null)
    {
        if(empty($language))
        {
            $language = $this->getLanguage();
        }

        if(file_exists($file = $this->getFolder() . '/' . $language . '.csv'))
        {
            return file_get_contents($file);
        }
        else
        {
            return '';
        }
    }

    public function parseCsv($csvdata, $ignoreCase = false)
    {
        $return = array();

        $csvdata = explode("\n", $csvdata); // split the lines....
        foreach($csvdata as $data)
        {
            $data = strpos($data, "\t") ? str_getcsv($data, "\t", "") : str_getcsv($data);
            if(count($data)>1)
            {
                $key = array_shift($data);
                if((bool) $ignoreCase)
                {
                    $key = strtolower($key);
                }

                $key = trim($key);
                if(!empty($key))
                {
                    $return[$key] = $data;
                    foreach($return[$key] as $i => $r)
                    {
                        $return[$key][$i] = stripslashes($r);
                    }
                }

                unset($key, $i, $r);
            }
        }
        unset($csvdata, $data);

        return $return;
    }

    public function translate()
    {
        $arguments = func_get_args();
        if(!count($arguments))
        {
            return '';
        }
        $string = array_shift($arguments);

        if(empty($string) || !is_string($string))
        {
            return '';
        }

        $isPlural = !(isset($arguments[0]) && (float) $arguments[0] == 1);
        $string = $this->getTranslationString($string, $isPlural);

        if($trans = @vsprintf($string, $arguments))
        {
            $string = $trans;
        }
        else if($trans = @sprintf($string))
        {
            $string = $trans;
        }

        return $string;
    }

    public function hasTranslationString($string)
    {
        if(empty($string) || !is_string($string))
        {
            return false;
        }
        return $string != $this->getTranslationString($string);
    }

    public static function getCurrentLanguage()
    {
        return Yii::app()->getLanguage();
    }

    public static function getDefaultLanguage()
    {
        return Yii::app()->getConfig("defaultlang");
    }

    public static function getAvailableLanguages()
    {
        $languages = explode(' ', trim(Yii::app()->getConfig('restrictToLanguages')));

        if(count($languages)>1)
        {
            // make sure we have the default language at first
            $default = Yii::app()->getConfig("defaultlang");
            $languages = array_merge(array($default), $languages);
            $languages = array_unique($languages);
            unset($default);
        }

        $return = [];
        if(!empty($languages))
        {
            Yii::app()->loadHelper('surveytranslator');
            $allLanguages = getLanguageData(false, Yii::app()->session['adminlang']);
            foreach($languages as $lang)
            {
                $return[$lang] = $allLanguages[$lang]['description'];
            }
        }

        unset($languages, $default, $allLanguages, $lang);

        return $return;
    }
}
