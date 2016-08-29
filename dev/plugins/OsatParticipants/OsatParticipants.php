<?php
if(!class_exists('Osat', false))
{
	require_once(realpath(dirname(__FILE__) . '/../Osat/Osat.php'));
}
class OsatParticipants extends Osat {

	# protected $storage = 'DbStorage';
	static protected $description = 'Administration of survey participants';
	static protected $name = 'OSAT Participants';
	static protected $label = 'osatparticipants';

	protected $menuLabel = "Participants";
	protected $settings = [
		'options_default' => array(
            'type' => 'info',
            'label' => '',
			'content' => 'Login options'
        ),
		'remind_after' => array(
            'type'=>'int',
            'label'=>'Remind after n days',
            'help'=>'Automatically send reminder emails after n days. Set 0 if you do not want to send reminder emails automatically.',
            'default'=>'0',
        ),
		'delete_after' => array(
            'type'=>'int',
            'label'=>'Delete incomplete survey after n days',
            'help'=>'Automatically remove incompleted surveys and its users after n days. Set 0 if you do not want to remove incomplete surveys automatically. If you setup an email text for a warning email, users will be warned one week before deletion date.',
            'default'=>'0',
        ),
		'warn_after' => array(
            'type'=>'int',
            'label'=>'Warn n days before deletion',
            'help'=>'When incomplete surveys are deleted, warn peeople n days before deletion. Set 0 if you do not want to send warning emails automatically. Make sure to provide an email text, otherwise the REMINDER email text will be used.',
            'default'=>'0',
        ),
		'show_logs' => array(
            'type'=>'info',
            'label'=>'',
            'help' => 'You can <a href="../../../../../../plugins/OsatParticipants/logs/email.log" target="_blank">view the logfile</a> or start the automatic jobs manually <a href="?osatparticipantsaction=cron" target="_blank">here</a>',
            'default'=>'0',
        )
	];
    protected $localeSettings = [
		'warn_email' => [
			'type' => 'text',
			'title' => 'Deletion email',
			'help' => 'If you do not provide a text for this email and automatically remove incomplete surveys, users will receive a normal reminder email.<br />
			You can provide a subject line by deviding the email with a --- :<br />
			<pre>This is my email subject
---
And here goes the email body.
			</pre>
			It is also possible to use any placeholder that is available in the normal reminder emails, plus the placeholder <span style="font-family:monospace;">{DELETION_DATE}</span> which will be replaced by the date the participant will be removed from the survey.',
			'default' => 'Reminder to participate in a survey
---
Dear {FIRSTNAME},

Some time ago you attended out survey. We note that you have not yet completed the survey, and wish to remind you that the survey is still available should you wish to take part.

Keep in mind that we are about to remove you from our survey if you do not complete the survey until {DELETION_DATE}.

The survey is titled:
"{SURVEYNAME}"

"{SURVEYDESCRIPTION}"

To participate, please click on the link below.

Sincerely,

{ADMINNAME} ({ADMINEMAIL})

----------------------------------------------
Click here to do the survey:
{SURVEYURL}

If you do not want to participate in this survey and don\'t want to receive any more invitations please click the following link:
{OPTOUTURL}'
		]
    ];

	protected $is_cron = false;

	public function __construct(PluginManager $manager, $id)
    {
		parent::__construct($manager, $id);

        $this->_registerEvents();
	}

    protected function _registerEvents()
    {
        /**
		 * Here you should handle subscribing to the events your plugin will handle
		 */
		$this->subscribe('beforeAdminMenuRender');
        # $this->subscribe('beforeRegisterForm');
        #$this->subscribe('beforeSurveyPageOsatEarly');
        #$this->subscribe('osatAddLocales');

		#$this->subscribe('beforeStatsPage');


		// $this->subscribe('beforeControllerAction');
		$this->subscribe('beforeControllerAction');

    }

	public function beforeControllerAction()
	{
		$surveyId = Yii::app()->request->getParam('sid', Yii::app()->request->getParam('surveyid', null));
		$action = Yii::app()->request->getParam('osatparticipantsaction', null);

		if(!empty($action))
		{
			$action = 'do' . ucfirst(strtolower($action));
			if(is_callable(array($this,$action)))
			{
				$this->$action($surveyId);
			}
		}
	}

	protected function hasPermission($surveyId)
	{
		if($this->isCron())
		{
			return true;
		}

		if(!empty($surveyId) && ($controller = $this->getController()))
		{
			if($controller->getId() == 'admin')
			{
				if($user = Yii::app()->user)
				{
					if($user->getName() == self::$label)
					{
						if(Survey::Model()->findByPk($surveyId))
						{
							return true;
						}
						return false;
					}
				}
				return Permission::model()->hasGlobalPermission('superadmin','read') || Permission::model()->hasGlobalPermission('surveys','create');
			}
		}

		return false;
	}

	public function doRemind($surveyId)
	{
		if($this->hasPermission($surveyId))
		{
			$this->sendEmails($surveyId, 'remind');
			exit();
		}
	}

	public function doWarn($surveyId)
	{
		if($this->hasPermission($surveyId))
		{
			if($ids = $this->getParticipantsForDeletionWarning($surveyId))
			{
				$this->sendEmails($surveyId, 'warn', $ids);
				exit();
			}
		}
	}

	public function doDelete($surveyId)
	{
		if($this->hasPermission($surveyId))
		{
			$this->removeTokens($surveyId);
			exit();
		}
	}

	public function isCron($set = null)
	{
		if($set !== null)
		{
			$this->is_cron = $set === TRUE;
		}
		return $this->is_cron;
	}

	public function doCron()
	{
		$actions = false;
		Yii::app()->loadHelper('common');

		// get all active surveys
		$query = "SELECT `sid` FROM `{{surveys}}` WHERE `active` = 'Y'";
		$rows = Yii::app()->db->createCommand($query)->query()->readAll();
		if(count($rows))
		{
			foreach($rows as $row)
			{
				$surveyId = $row['sid'];
				if($this->hasPermission($surveyId))
				{
					// first delete incomplete tokens
					$this->removeTokens($surveyId);

					// then the warnings...
					if($ids = $this->getParticipantsForDeletionWarning($surveyId))
					{
						$this->sendEmails($surveyId, 'warn', $ids);
					}

					// and the reminder
					$this->sendEmails($surveyId, 'remind');

					$actions = true;
				}
			}
		}

		if($actions)
		{
			exit();
		}
	}

	protected function mysql_escape_mimic($inp)
	{
	    if(is_array($inp))
	        return array_map(__METHOD__, $inp);

	    if(!empty($inp) && is_string($inp)) {
	        return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
	    }

	    return $inp;
	}

	protected function getInsertQuery(array $rows, $table)
	{
		$fields = array();
	    $values = array();
		$table = Yii::app()->db->tablePrefix . str_replace(array('{','}'),array('',''), $table);

	    foreach($rows as $row)
		{
	        $temp = array();
	        foreach ($row as $key => $value)
			{
	            $temp[$key] = ($value === null ? 'NULL' : "'" . $this->mysql_escape_mimic($value) . "'");
				if(empty($values))
				{
					$fields[] = "`$key`";
				}
	        }

	        $values[] = "(" . implode(",",$temp) . ")";
	    }

	    return "INSERT `{$table}` (" . implode(",",$fields) . ") VALUES \n" . implode(",\n", $values) . ";";
	}

	protected function removeTokens($surveyId, array $ids = [])
	{
		if(!tableExists('{{tokens_' . $surveyId . '}}'))
		{
			return false;
		}

		if(empty($ids))
		{
			$ids = $this->getParticipantsForDeletion($surveyId);
		}

		if(!empty($ids)) {
			$log = [];
			$log[] = 'Participants found: ' . join(', ', $ids);

			$INSERTS = [];

			// first export tokens to be removed...
			$query = "SELECT * FROM `{{tokens_$surveyId}}` WHERE `tid` IN (" . join(", ", $ids) . ")";
			$rows = Yii::app()->db->createCommand($query)->query()->readAll();
			if(count($rows))
			{
				$INSERTS[] = $this->getInsertQuery($rows, "{{tokens_$surveyId}}");

				$query = "DELETE FROM `{{tokens_$surveyId}}` WHERE `tid` IN (" . join(", ", $ids) . ")";
				if(Yii::app()->db->createCommand($query)->execute())
				{
					$log[] = 'SUCCESS: ' . $query;
				}
			}

			// clean up database
			$query = "SELECT * FROM {{survey_$surveyId}} WHERE `token` NOT IN (SELECT `token` FROM `{{tokens_$surveyId}}` WHERE (`blacklisted` IS NULL) OR `blacklisted` <> 'Y')";
			$rows = Yii::app()->db->createCommand($query)->query()->readAll();
			if(count($rows))
			{
				$INSERTS[] = $this->getInsertQuery($rows, "{{survey_$surveyId}}");
				$ids = [];
				foreach($rows as $row)
				{
					$ids[] = $row['id'];
				}

				$query = "DELETE FROM {{survey_$surveyId}} WHERE `id` IN (" . join(", ", $ids) . ")";
				if(Yii::app()->db->createCommand($query)->execute())
				{
					$log[] = 'SUCCESS: ' . $query;
				}
			}

			if(!empty($INSERTS))
			{
				// create backup file
				$filename = dirname(__FILE__) . '/secure/backups/' . date('Y-m-d_H-i-s', time()) . '_deleted_rows.sql';
				file_put_contents($filename, join("\n", $INSERTS));
				$log[] = 'Backup created: ' . $filename;
				unset($filename);
			}

			echo '<pre>' . $this->log('REMOVING INCOMPLETE RESULTS FOR SURVEY #' . $surveyId, $log) . '</pre>';
		}
	}

	protected function getParticipants($surveyId, array $ids = [], $where = '', $extra = 'ORDER BY `tid` ASC')
	{
		$query = "SELECT * FROM `{{tokens_$surveyId}}` WHERE";
		$query.= " `emailstatus` = 'OK'";
		$query.= " AND `completed` = 'N'";
		$query.= " AND (`blacklisted` <> 'Y' OR `blacklisted` IS NULL)";
		$query.= " AND `sent` <> 'N'";

		if(!empty($ids))
		{
			$query.= " AND (`tid` IN (" . join(", ", $ids) . ") OR `token` IN ('" . join("', '", $ids) . "'))";
		}

		if(!empty($where))
		{
			$query.=" AND (" . trim($where) . ")";
		}

		$query.= ' ' . trim($extra);

		$rows = Yii::app()->db->createCommand($query)->query()->readAll();
		if(count($rows))
		{
			return $rows;
		}
		return null;
	}

	protected function log($title, $lines = null)
	{
		if(is_array($lines))
		{
			$lines = join("\n\t", $lines);
		}

		$text = "\n--------------------------------------------------------------------------------";
		$text.= "\n" . date('Y-m-d H:i:s', time()) . " : " . substr($title, 0, 58);
		if(!empty($lines))
		{
			$text.= "\n................................................................................";
			$text.= "\n\t" . $lines;
		}

		$filename = dirname(__FILE__) . '/logs/email.log';

		// truncate file
		$maxlines = 1000;

		$text = explode("\n", trim($text));
		if(file_exists($filename))
		{
			$lines = file_get_contents($filename);
			$lines = explode("\n", trim($lines));
			$text = array_merge($text, $lines);
		}

		if(count($text) > $maxlines)
		{
			$text = array_slice($text, 0, $maxlines);
		}

		unset($maxlines, $lines);

		$text = join("\n", $text);

		@file_put_contents($filename, $text);

		return $text;
	}

	protected function getDeletionDate($from = null)
	{
		if(empty($from))
		{
			$from = time();
		}

		$deletion_date = (int) $this->getSettings('delete_after');

		if($deletion_date > 0) {
			$deletion_date = $from + ($deletion_date * 24 * 60 * 60);
			$deletion_date = mktime(0, 0, 0, date('m', $deletion_date), date('d', $deletion_date), date('Y', $deletion_date));

			return $deletion_date;
		}

		return 0;
	}

	protected function getParticipantsForDeletionWarning($surveyId)
	{
		$warning_date = (int) $this->getSettings('warn_after');
		if($warning_date > 0)
		{
			$now = time() + $warning_date * 24 * 60 * 60;
			return $this->getParticipantsForDeletion($surveyId, $now);
		}
		return null;
	}
	protected function getParticipantsForDeletion($surveyId, $now = null)
	{
		$deletion_date = (int) $this->getSettings('delete_after');
		if($deletion_date > 0)
		{
			if(empty($now))
			{
				$now = time();
			}

			$now-= $deletion_date * 24 * 60 * 60;

			$since = mktime(0, 0, 0, date('m', $now), date('d', $now), date('Y', $now));

			$tokens = $this->getParticipants(
				$surveyId,
				[],
				 "`sent` < '" . date('Y-m-d H:i', $since) . "'"
			);

			if(!empty($tokens))
			{
				$ids = [];
				foreach($tokens as $token)
				{
					$ids[] = $token['tid'];
				}

				return $ids;
			}
		}
		return null;
	}

	public function sendEmails($surveyId, $type, array $ids = [])
	{
		$setting = $type . '_after';
		if($days = (int) $this->getSettings($setting))
		{
			if($type == 'warn')
			{
				$days = (int) $this->getSettings('delete_after') - $days;
			}
			if($days > 0)
			{
				$log = [];

				$since = time() - (($days - 1) * 24 * 60 * 60);
				$since = mktime(0, 0, 0, date('m', $since), date('d', $since), date('Y', $since));

				$where = "`sent` < '" . date('Y-m-d H:i', $since) . "'";
				$where.= " AND (`remindersent` < '" . date('Y-m-d H:i', $since) . "' OR `remindersent` = 'N')";

				if($type == 'remind')
				{
					$where.= " AND (`remindercount` < 1)";
				}

				$tokens = $this->getParticipants(
					$surveyId,
					$ids,
					$where
				);

				$log[] = 'Gathering participants inactive since ' . date('Y-m-d H:i', $since);

				if(!empty($tokens))
				{
					$tokenids = [];
					foreach($tokens as $token)
					{
						$tokenids[] = $token['tid'];
					}
					$log[] = 'Participants found: ' . join(', ', $tokenids);

					if($return = $this->email($surveyId, $tokenids, $type))
					{
						if(!empty($return['tokenoutput']))
						{
							foreach(explode("\n", strip_tags($return['tokenoutput'])) as $line)
							{
								$line = trim($line);
								if(!empty($line))
								{
									$log[] = $line;
								}
							}
						}
						else
						{
							$log[] = 'Emails sent (but no return message received)';
						}
					}
					else
					{
						$log[] = 'WARNING: Something went wrong sending emails!';
					}
				}
				else
				{
					$log[] = 'No participants found, nothing to do.';
				}

				echo '<pre>' . $this->log('SENDING ' . strtoupper($type) . ' EMAILS FOR SURVEY #' . $surveyId, $log) . '</pre>';
			}
		}
		return false;
	}

	public function getController()
	{
		if($controller = App()->getController())
		{
			return $controller;
		}
		else
		{
			return null; // new RegisterController('index');
		}
	}

	protected function createAbsoluteUrl($suffix, array $params = [])
	{
		if($controller = $this->getController())
		{
			return $controller->createAbsoluteUrl($suffix, $params);
		}
		else
		{
			$scriptname = strpos($_SERVER['SCRIPT_NAME'],'/plugins') !== false ? substr($_SERVER['SCRIPT_NAME'], 0, strpos($_SERVER['SCRIPT_NAME'],'/plugins') ) : $_SERVER['SCRIPT_NAME'];
			$url = 'http://' . $_SERVER['SERVER_NAME'] . '/' . ltrim($scriptname, '/') . '/' . ltrim($suffix, '/');
			if(!empty($params))
			{
				$url.= '?' . http_build_query($params);
			}
			return $url;
		}
	}

	protected function setDummyServer($on = false)
	{
		if($on === true)
		{
			if(empty($_SERVER['SERVER_NAME']))
			{
				$this->server = $_SERVER;
				$dummy = '{"USER":"a-eufa","HOME":"\/var\/www\/vhosts\/eu-fundraising.eu","PATH_TRANSLATED":"redirect:\/index.php","PATH_INFO":"\/admin","SCRIPT_NAME":"\/index.php","REQUEST_URI":"\/index.php\/admin","QUERY_STRING":"","REQUEST_METHOD":"GET","SERVER_PROTOCOL":"HTTP\/1.1","GATEWAY_INTERFACE":"CGI\/1.1","REMOTE_PORT":"62913","SCRIPT_FILENAME":"\/var\/www\/vhosts\/eu-fundraising.eu\/assessment.eu-fundraising.eu\/index.php","SERVER_ADMIN":"alexandra.haertel@emcra.eu","CONTEXT_DOCUMENT_ROOT":"\/var\/www\/vhosts\/eu-fundraising.eu\/assessment.eu-fundraising.eu","CONTEXT_PREFIX":"","REQUEST_SCHEME":"http","DOCUMENT_ROOT":"\/var\/www\/vhosts\/eu-fundraising.eu\/assessment.eu-fundraising.eu","REMOTE_ADDR":"84.61.94.97","SERVER_PORT":"80","SERVER_ADDR":"92.51.181.241","SERVER_NAME":"assessment.eu-fundraising.eu","SERVER_SOFTWARE":"Apache","SERVER_SIGNATURE":"<address>Apache Server at assessment.eu-fundraising.eu Port 80<\/address>\n","PATH":"\/usr\/local\/sbin:\/usr\/local\/bin:\/usr\/sbin:\/usr\/bin:\/sbin:\/bin","HTTP_COOKIE":"_ga=GA1.2.1797938673.1471979530; __utma=125117598.1797938673.1471979530.1471979530.1471979530.1; __utmz=125117598.1471979530.1.1.utmcsr=osat.dev|utmccn=(referral)|utmcmd=referral|utmcct=\/survey\/index.php\/323695; PHPSESSID=hkhmko4c089s10t575jr87ugm5; YII_CSRF_TOKEN=efbd4a2d512efde70d23f0bb204aa31901e385e3","HTTP_ACCEPT_LANGUAGE":"de-DE,de;q=0.8,en-US;q=0.6,en;q=0.4","HTTP_ACCEPT_ENCODING":"gzip, deflate, sdch","HTTP_ACCEPT":"text\/html,application\/xhtml+xml,application\/xml;q=0.9,image\/webp,*\/*;q=0.8","HTTP_USER_AGENT":"Mozilla\/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/52.0.2743.116 Safari\/537.36","HTTP_UPGRADE_INSECURE_REQUESTS":"1","HTTP_CONNECTION":"keep-alive","HTTP_HOST":"assessment.eu-fundraising.eu","proxy-nokeepalive":"1","UNIQUE_ID":"V731ygUj8n0AAHQXZesAAAAF","FCGI_ROLE":"RESPONDER","PHP_SELF":"\/index.php\/admin","REQUEST_TIME_FLOAT":1472067018.6674,"REQUEST_TIME":1472067018}';
				$dummy = json_decode($dummy, TRUE);
				$_SERVER = $dummy;
				foreach($dummy as $k => $v)
				{
					if(empty($_SERVER[$k]))
					{
						$_SERVER[$k] = $v;
					}
				}
			}
		}
		else
		{
			if(!empty($this->server))
			{
				$_SERVER = $this->server;
				unset($this->server);
			}
		}
	}

	/**
    * Handle email action
    */
    protected function email($iSurveyId, array $tokenids = [], $sSubAction, array $custom_replacements = [])
    {
        $iSurveyId = sanitize_int($iSurveyId);

        // CHECK TO SEE IF A TOKEN TABLE EXISTS FOR THIS SURVEY
        $bTokenExists = tableExists('{{tokens_' . $iSurveyId . '}}');
        if (!$bTokenExists) //If no tokens table exists
        {
            return false;
        }

		if(!in_array($sSubAction, ['remind', 'warn']))
		{
			return false;
		}

		$this->setDummyServer(true);
		$return = false;

        $surveyinfo = Survey::model()->findByPk($iSurveyId)->surveyinfo;

        $aTokenIds = $tokenids;
        if (!empty($aTokenIds))
        {
			$aTokenIds = array_filter($aTokenIds);
            $aTokenIds = array_map('sanitize_int', $aTokenIds);
        }
        $aTokenIds=array_unique(array_filter((array) $aTokenIds));

        $bEmail = false;

        Yii::app()->loadHelper('surveytranslator');
        Yii::app()->loadHelper('/admin/htmleditor');
        Yii::app()->loadHelper('replacements');
        if(!function_exists('cmpQuestionSeq')) {
			Yii::app()->loadHelper('/expressions/em_manager');
		}
        Yii::app()->loadHelper('globalsettings');

        $token = Token::model($iSurveyId)->find();

        $aExampleRow = isset($token) ? $token->attributes : array();
        $aSurveyLangs = Survey::model()->findByPk($iSurveyId)->additionalLanguages;
        $sBaseLanguage = Survey::model()->findByPk($iSurveyId)->language;
        array_unshift($aSurveyLangs, $sBaseLanguage);
        $aTokenFields = getTokenFieldsAndNames($iSurveyId, true);
        $iAttributes = 0;
        $bHtml = (getEmailFormat($iSurveyId) == 'html');

        $timeadjust = Yii::app()->getConfig("timeadjust");

        $thissurvey = getSurveyInfo($iSurveyId);
        foreach($aSurveyLangs as $sSurveyLanguage)
        {
            $thissurvey[$sSurveyLanguage] = getSurveyInfo($iSurveyId, $sSurveyLanguage);

			if(!isset($thissurvey[$sSurveyLanguage]["email_{$sSubAction}"]) || !isset($thissurvey[$sSurveyLanguage]["email_{$sSubAction}_subj"]))
			{
				if(!isset($thissurvey[$sSurveyLanguage]["email_{$sSubAction}"]))
				{
					$thissurvey[$sSurveyLanguage]["email_{$sSubAction}"] = $thissurvey[$sSurveyLanguage]["email_remind"];
				}
				if(!isset($thissurvey[$sSurveyLanguage]["email_{$sSubAction}_subj"]))
				{
					$thissurvey[$sSurveyLanguage]["email_{$sSubAction}_subj"] = $thissurvey[$sSurveyLanguage]["email_remind_subj"];
				}

				// gather email text from settings
				if($text = $this->getSettings($sSubAction . '_email_' . $sSurveyLanguage))
				{
					if(($p = strpos($text, "\n---")) !== false)
					{
						$thissurvey[$sSurveyLanguage]["email_{$sSubAction}_subj"] = trim(substr($text, 0, $p));
						$text = trim(substr($text, $p + 4));
					}
					$thissurvey[$sSurveyLanguage]["email_{$sSubAction}"] = trim($text);
				}
			}

			unset($text, $subject, $p);
        }

        $aData['surveyid'] = $iSurveyId;
        $aData['sSubAction'] = $sSubAction;
        $aData['bEmail'] = $bEmail;
        $aData['aSurveyLangs'] = $aData['surveylangs'] = $aSurveyLangs;
        $aData['baselang'] = $sBaseLanguage;
        $aData['tokenfields'] = array_keys($aTokenFields);
        $aData['nrofattributes'] = $iAttributes;
        $aData['examplerow'] = $aExampleRow;
        $aData['tokenids'] = $aTokenIds;
        $aData['ishtml'] = $bHtml;
        $iMaxEmails = Yii::app()->getConfig('maxemails');

        $SQLremindercountcondition = "";
        $SQLreminderdelaycondition = "";

        $ctresult = TokenDynamic::model($iSurveyId)->findUninvitedIDs($aTokenIds, 0, $bEmail);
        $ctcount = count($ctresult);

        $emresult = TokenDynamic::model($iSurveyId)->findUninvited($aTokenIds, $iMaxEmails, $bEmail);
        $emcount = count($emresult);

		// set up email bodies and subjects
		foreach ($aSurveyLangs as $language)
		{
			// See #08683 : this allow use of {TOKEN:ANYTHING}, directly replaced by {ANYTHING}
			$sSubject[$language]=preg_replace("/{TOKEN:([A-Z0-9_]+)}/","{"."$1"."}", $thissurvey[$language]["email_{$sSubAction}_subj"]);
			$sMessage[$language]=preg_replace("/{TOKEN:([A-Z0-9_]+)}/","{"."$1"."}", $thissurvey[$language]["email_{$sSubAction}"]);
			if ($bHtml)
				$sMessage[$language] = html_entity_decode($sMessage[$language], ENT_QUOTES, Yii::app()->getConfig("emailcharset"));
		}

        $tokenoutput = "";
        $bInvalidDate = false;
        $bSendError=false;
        if ($emcount > 0)
        {

            foreach ($emresult as $emrow)
            {
                $to = $fieldsarray = array();

				foreach($thissurvey[$emrow['language']] as $k => $v)
				{
					$fieldsarray['{SURVEY' . strtoupper($k) . '}'] = $v;
					$fieldsarray['{' . strtoupper($k) . '}'] = $v;
				}

                $aEmailaddresses = preg_split( "/(,|;)/", $emrow['email'] );
                foreach ($aEmailaddresses as $sEmailaddress)
                {
                    $to[] = ($emrow['firstname'] . " " . $emrow['lastname'] . " <{$sEmailaddress}>");
                }

                foreach ($emrow as $attribute => $value) // LimeExpressionManager::loadTokenInformation use $oToken->attributes
                {
                    $fieldsarray['{' . strtoupper($attribute) . '}'] = $value;
                }

                $emrow['language'] = trim($emrow['language']);
                $found = array_search($emrow['language'], $aSurveyLangs);
                if ($emrow['language'] == '' || $found == false)
                {
                    $emrow['language'] = $sBaseLanguage;
                }

                $from = $thissurvey[$sBaseLanguage]['adminname']." <".$thissurvey[$sBaseLanguage]['adminemail'].">";

                $fieldsarray["{OPTOUTURL}"] = $this->createAbsoluteUrl("/optout/tokens",array("surveyid"=>$iSurveyId,"langcode"=>trim($emrow['language']),"token"=>$emrow['token']));
                $fieldsarray["{OPTINURL}"] = $this->createAbsoluteUrl("/optin/tokens",array("surveyid"=>$iSurveyId,"langcode"=>trim($emrow['language']),"token"=>$emrow['token']));
                $fieldsarray["{SURVEYURL}"] = $this->createAbsoluteUrl("/survey/index",array("sid"=>$iSurveyId,"token"=>$emrow['token'],"lang"=>trim($emrow['language'])));

                // Add some var for expression : actually only EXPIRY because : it's used in limereplacement field and have good reason to have it.
                $fieldsarray["{EXPIRY}"]=$thissurvey["expires"];
                $customheaders = array('1' => "X-surveyid: " . $iSurveyId,
                '2' => "X-tokenid: " . $fieldsarray["{TOKEN}"]);
                global $maildebug;
                $modsubject = $sSubject[$emrow['language']];
                $modmessage = $sMessage[$emrow['language']];
                foreach(array('OPTOUT', 'OPTIN', 'SURVEY') as $key)
                {
                    $url = $fieldsarray["{{$key}URL}"];
                    if ($bHtml) $fieldsarray["{{$key}URL}"] = "<a href='{$url}'>" . htmlspecialchars($url) . '</a>';
                    $modsubject = str_replace("@@{$key}URL@@", $url, $modsubject);
                    $modmessage = str_replace("@@{$key}URL@@", $url, $modmessage);
                }

				// DELETION DATE!!!
				$deletion_date = $emrow['sent'] <> "N" ? $this->getDeletionDate(strtotime($emrow['sent'])) : 0;
				if(!empty($deletion_date))
				{
					$format = 'Y-m-d';
					if($tmp = getDateFormatData($thissurvey[$emrow['language']]['surveyls_dateformat']))
					{
						$format = $tmp['phpdate'];
					}

					$deletion_date = date($format, $deletion_date);
				}
				else
				{
					$deletion_date = '';
				}

				$fieldsarray['{DELETION_DATE}'] = $deletion_date;

				$fieldsarray = array_replace($fieldsarray, $custom_replacements);

            	$modsubject = Replacefields($modsubject, $fieldsarray);
                $modmessage = Replacefields($modmessage, $fieldsarray);

                /*
                 * Get attachments.
                 */
                $sTemplate = 'reminder';

                $aRelevantAttachments = array();
                if (isset($thissurvey[$emrow['language']]['attachments']))
                {
                    $aAttachments = unserialize($thissurvey[$emrow['language']]['attachments']);
                    if (!empty($aAttachments))
                    {
                        if (isset($aAttachments[$sTemplate]))
                        {
                            LimeExpressionManager::singleton()->loadTokenInformation($thissurvey['sid'], $emrow['token']);

                            foreach ($aAttachments[$sTemplate] as $aAttachment)
                            {
                                if (LimeExpressionManager::singleton()->ProcessRelevance($aAttachment['relevance']))
                                {
                                    $aRelevantAttachments[] = $aAttachment['url'];
                                }
                            }
                        }
                    }
                }

                /**
                 * Event for email handling.
                 * Parameter    type    description:
                 * subject      rw      Body of the email
                 * to           rw      Recipient(s)
                 * from         rw      Sender(s)
                 * type         r       "invitation" or "reminder"
                 * send         w       If true limesurvey will send the email. Setting this to false will cause limesurvey to assume the mail has been sent by the plugin.
                 * error        w       If set and "send" is true, log the error as failed email attempt.
                 * token        r       Raw token data.
                 */
                $event = new PluginEvent('beforeTokenEmail');
                $event->set('type', $sTemplate);
                $event->set('subject', $modsubject);
                $event->set('to', $to);
                $event->set('body', $modmessage);
                $event->set('from', $from);
                $event->set('bounce', getBounceEmail($iSurveyId));
                $event->set('token', $emrow);
                App()->getPluginManager()->dispatchEvent($event);
                $modsubject = $event->get('subject');
                $modmessage = $event->get('body');
                $to = $event->get('to');
                $from = $event->get('from');
                $bounce = $event->get('bounce');
                if ($event->get('send', true) == false)
                {
                    // This is some ancient global used for error reporting instead of a return value from the actual mail function..
                    $maildebug = $event->get('error', $maildebug);
                    $success = $event->get('error') == null;
                }
                else
                {
                    $success = SendEmailMessage($modmessage, $modsubject, $to, $from, Yii::app()->getConfig("sitename"), $bHtml, $bounce, $aRelevantAttachments, $customheaders);
                }

                if ($success)
                {
                    // Put date into sent
                    $token = Token::model($iSurveyId)->findByPk($emrow['tid']);
                    if ($bEmail)
                    {
                        $tokenoutput .= gT("Invitation sent to:");
                        $token->sent = dateShift(date("Y-m-d H:i:s"), "Y-m-d H:i", Yii::app()->getConfig("timeadjust"));
                    }
                    else
                    {
                        $tokenoutput .= gT("Reminder sent to:");
                        $token->remindersent = dateShift(date("Y-m-d H:i:s"), "Y-m-d H:i", Yii::app()->getConfig("timeadjust"));
                        $token->remindercount++;
                    }
                    $token->save();

                    //Update central participant survey_links
                    if(!empty($emrow['participant_id']))
                    {
                        $slquery = SurveyLink::model()->find('participant_id = :pid AND survey_id = :sid AND token_id = :tid',array(':pid'=>$emrow['participant_id'],':sid'=>$iSurveyId,':tid'=>$emrow['tid']));
                        if (!is_null($slquery))
                        {
                            $slquery->date_invited = dateShift(date("Y-m-d H:i:s"), "Y-m-d H:i", Yii::app()->getConfig("timeadjust"));
                            $slquery->save();
                        }
                    }
                    $tokenoutput .= htmlspecialchars(ReplaceFields("{$emrow['tid']}: {FIRSTNAME} {LASTNAME} ({EMAIL})", $fieldsarray)). "<br />\n";
                    if (Yii::app()->getConfig("emailsmtpdebug") == 2)
                    {
                        // $tokenoutput .= $maildebug;
                    }
                } else {
                    $tokenoutput .= htmlspecialchars(ReplaceFields(gT("Email to {FIRSTNAME} {LASTNAME} ({EMAIL}) failed. Error message:",'unescaped') . " " . $maildebug , $fieldsarray)). "<br />";
                    $bSendError=true;
                }
            }
            unset($fieldsarray);

	        $aViewUrls = array( 'emailpost');
	        $aData['tokenoutput']=$tokenoutput;

	        if ($ctcount > $emcount)
	        {
	            $i = 0;
	            if (isset($aTokenIds))
	            {
	                while ($i < $iMaxEmails)
	                {
	                    array_shift($aTokenIds);
	                    $i++;
	                }
	                $aData['tids'] = implode('|', $aTokenIds);
	            }

	            $aData['lefttosend'] = $ctcount - $iMaxEmails;
	            $aViewUrls[] = 'emailwarning';
	        }
	        else
	        {
	            if (!$bInvalidDate && !$bSendError)
	            {
	                $aData['tokenoutput'].="<strong class='result success text-success'>".gT("All emails were sent.")."<strong>";
	            }
	            else
	            {
	                $aData['tokenoutput'].="<strong class='result warning text-warning'>".gT("Not all emails were sent:")."<strong><ul class='list-unstyled'>";
	                if ($bInvalidDate)
	                {
	                    $aData['tokenoutput'].="<li>".gT("Some entries had a validity date set which was not yet valid or not valid anymore.")."</li>";
	                }
	                if ($bSendError)
	                {
	                    $aData['tokenoutput'].="<li>".gT("Some emails were not sent because the server did not accept the email(s) or some other error occured.")."</li>";
	                }
	                $aData['tokenoutput'].='</ul>';
	                $aData['tokenoutput'].= '<p><a href="'.App()->createUrl('admin/tokens/sa/index/surveyid/'.$iSurveyId).'" title="" class="btn btn-default btn-lg">'.gT("Ok").'</a></p>';
	            }
	        }

	        $return = $aData;
	    }

		$this->setDummyServer();
		return $return;
    }
}
