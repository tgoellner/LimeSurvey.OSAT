<?php
if(!class_exists('Osat', false))
{
	require_once(realpath(dirname(__FILE__) . '/../Osat/Osat.php'));
}

class OsatGlossary extends Osat {

	static protected $description = 'Adds a glossary';
	static protected $name = 'OSAT Glossary';
	static protected $label = 'osatglossary';

	protected $menuLabel = "Glossary";
	protected $localeSettings = [
		'translate' => [
			'type' => 'text',
			'title' => 'Glossary definitions',
			'help' => '"Term","Definition of the term"'
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
        $this->subscribe('afterEmManagerHelperProcessString');
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

	public function insertGlossaryTerms($string)
	{

		$escapewrap = '<!-- glossary %s //-->';

		// escape all already inserted glossary terms
		$string = preg_replace('/\<span class="osat--glossary"([^\>]+)>(.*)\<\/span\>/', sprintf($escapewrap, base64_encode("<span class=\"osat--glossary\"$1>$2</span>")), $string);


		// find all texts between tags...
		preg_match_all("/(<[^><]+>)([^<]+)?(<\/[^>]+>)?/", $string, $tags);
		if(!empty($tags[2]))
		{
			$translator = $this->getTranslator(true);
			$settings = [];
			if($translator->getLanguage() != $translator->getDefaultLanguage())
			{
				$settings[] = $this->getSettings(static::$label . '_translate_' . $translator->getDefaultLanguage());
			}
			$settings[] = $this->getSettings(static::$label . '_translate_' . $translator->getLanguage());

			$translations = $translator->parseCsv(join("\n", $settings), true);

			foreach($tags[2] as $i => $src)
			{
				$dst = trim($src);
				if(empty($dst))
				{
					continue;
				}
				$dst_new = $dst;
				foreach($translations as $find => $replace)
				{
					$find = trim($find);

					$replace = '<span class="osat--glossary" aria-label="' . addslashes(htmlspecialchars(reset($replace))) . '" data-balloon-length="large" data-balloon="' . addslashes(htmlspecialchars(reset($replace))) . '"><abbr>%s</abbr></span>';
					$preg = "/([^a-zA-Z\pL]|^)(?!\<abbr\>)(" . preg_quote ($find) . ")(?!\<\/abbr\>)([^a-zA-Z\pL]|$)/i";

					preg_match_all($preg, $dst_new, $matches);
					$replacements = [];

					if(!empty($matches[2]))
					{
						foreach($matches[2] as $mi => $ms)
						{
							$replacements[$ms] = sprintf($escapewrap, base64_encode(sprintf($replace, $matches[2][$mi])));
						}
					}

					if(!empty($replacements))
					{
						foreach($replacements as $ms => $md)
						{
							$dst_new = str_replace($ms, $md, $dst_new);
						}
					}
				}

				if($dst != $dst_new)
				{
					$dst = $dst_new;
					$dst = str_replace($src, $dst, $tags[0][$i]);
					$src = $tags[0][$i];

					$string = str_replace($src, $dst, $string);
				}
			}
		}

		// unescape all inserted glossary terms
		$preg = preg_quote($escapewrap);
		$preg = str_replace('%s', '([^ ]+)', $preg);
		# print_r("~$preg~"); die();
		preg_match_all("~$preg~", $string, $matches);
		if(!empty($matches[0]))
		{
			foreach($matches[0] as $i => $match)
			{
				$string = str_replace($match, base64_decode($matches[1][$i]), $string);
			}
		}
		unset($preg, $matches, $escapewrap);

		return $string;
	}

    public function afterEmManagerHelperProcessString()
    {
        $event = $this->event;
        $stringToParse = $event->get('stringToParse');
        $stringToParse = $this->insertGlossaryTerms($stringToParse);
        $this->event->set('stringToParse', $stringToParse);

        return $this->event;
    }
}
