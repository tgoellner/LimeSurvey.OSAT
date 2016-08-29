<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
   * LimeSurvey
   * Copyright (C) 2013 The LimeSurvey Project Team / Carsten Schmitz
   * All rights reserved.
   * License: GNU/GPL License v2 or later, see LICENSE.php
   * LimeSurvey is free software. This version may have been modified pursuant
   * to the GNU General Public License, and as distributed it includes or
   * is derivative of works licensed under the GNU General Public License or
   * other free or open source software licenses.
   * See COPYRIGHT.php for copyright notices and details.
   *
   *    Files Purpose: lots of common functions
*/

class TokenDynamic extends LSActiveRecord
{
    protected static $sid = 0;
    public $emailstatus; // Default value for email status

    /**
     * Returns the static model of Settings table
     *
     * @static
     * @access public
     * @param int $surveyid
     * @return TokenDynamic
     */
    public static function model($sid = NULL)
    {
        $refresh = false;
        if (!is_null($sid))
        {
            self::sid($sid);
            $refresh = true;
        }

        $model = parent::model(__CLASS__);

        //We need to refresh if we changed sid
        if ($refresh === true) $model->refreshMetaData();
        return $model;
    }

    /**
     * Sets the survey ID for the next model
     *
     * @static
     * @access public
     * @param int $sid
     * @return void
     */
    public static function sid($sid)
    {
        self::$sid = (int) $sid;
    }

    /**
     * Returns the setting's table name to be used by the model
     *
     * @access public
     * @return string
     */
    public function tableName()
    {
        return '{{tokens_' . self::$sid . '}}';
    }

    /**
     * Returns the primary key of this table
     *
     * @access public
     * @return string
     */
    public function primaryKey()
    {
        return 'tid';
    }

    /**
    * Returns this model's validation rules
    *
    */
    public function rules()
    {
        return array(
            array('token', 'unique', 'allowEmpty'=>true),// 'caseSensitive'=>false only for mySql
            array('remindercount','numerical', 'integerOnly'=>true,'allowEmpty'=>true),
            array('email','filter','filter'=>'trim'),
            array('email','LSYii_EmailIDNAValidator', 'allowEmpty'=>true, 'allowMultiple'=>true,'except'=>'allowinvalidemail'),
            array('usesleft','numerical', 'integerOnly'=>true,'allowEmpty'=>true),
            array('mpid','numerical', 'integerOnly'=>true,'allowEmpty'=>true),
            array('blacklisted', 'in','range'=>array('Y','N'), 'allowEmpty'=>true),
            array('emailstatus', 'default', 'value' => $this->emailstatus),
        );
    }

    /**
    * Returns this model's relations
    *
    * @access public
    * @return array
    */
    public function relations()
    {
        SurveyDynamic::sid(self::$sid);
        return array(
            'survey'      => array(self::BELONGS_TO, 'Survey', array(), 'condition'=>'sid='.self::$sid, 'together' => true),
            'responses'   => array(self::HAS_MANY, 'SurveyDynamic', array('token' => 'token'))
        );
    }

    /**
    * Checks to make sure that all required columns exist in this tokens table
    * (some older tokens tables dont' get udated properly)
    *
    * This method should be moved to db update for 2.05 version so it runs only
    * once per token table / backup token table
    */
    public function checkColumns() {
        $sid = self::$sid;
        $sTableName = '{{tokens_'.$sid.'}}';
        $columncheck = array("tid", "participant_id", "firstname", "lastname", "email", "emailstatus","token","language","blacklisted","sent","remindersent","completed","usesleft","validfrom","validuntil");
        $tableSchema = Yii::app()->db->schema->getTable($sTableName);
        $columns = $tableSchema->getColumnNames();
        $missingcolumns=array_diff($columncheck,$columns);
        if(count($missingcolumns)>0) //Some columns are missing - we need to create them
        {
            Yii::app()->loadHelper('update/updatedb'); //Load the admin helper to allow column creation
            $columninfo=array('validfrom'=>'datetime',
                              'validuntil'=>'datetime',
                              'blacklisted'=> 'string(17)',
                              'participant_id'=> 'string(50)',
                              'remindercount'=>"integer DEFAULT '0'",
                              'usesleft'=>'integer NOT NULL default 1'); //Not sure if any other fields would ever turn up here - please add if you can think of any others
            foreach($missingcolumns as $columnname) {
                addColumn($sTableName,$columnname,$columninfo[$columnname]);
            }
            Yii::app()->db->schema->getTable($sTableName, true); // Refresh schema cache just in case the table existed in the past
        } else {
            // On some installs we have created not null for participant_id and blacklisted fix this
            $columns = array('blacklisted', 'participant_id');

            foreach ($columns as $columnname)
            {
                $definition = $tableSchema->getColumn($columnname);
                if ($definition->allowNull != true) {
                    Yii::app()->loadHelper('update/updatedb'); //Load the admin helper to allow column creation
                    Yii::app()->db->createCommand()->alterColumn($sTableName, $columnname, "string({$definition->size}})");
                    Yii::app()->db->schema->getTable($sTableName, true); // Refresh schema cache just in case the table existed in the past
                }
            }
        }
    }

    public function findUninvited($aTokenIds = false, $iMaxEmails = 0, $bEmail = true, $SQLemailstatuscondition = '', $SQLremindercountcondition = '', $SQLreminderdelaycondition = '')
    {
        $command = new CDbCriteria;
        $command->condition = '';
        $command->addCondition("(completed ='N') or (completed='')");
        $command->addCondition("token <> ''");
        $command->addCondition("email <> ''");

        if ($bEmail) {
            $command->addCondition("(sent = 'N') or (sent = '')");
        } else {
            $command->addCondition("(sent <> 'N') AND (sent <> '')");
        }

        if ($SQLemailstatuscondition)
            $command->addCondition($SQLemailstatuscondition);

        if ($SQLremindercountcondition)
            $command->addCondition($SQLremindercountcondition);

        if ($SQLreminderdelaycondition)
            $command->addCondition($SQLreminderdelaycondition);

        if ($aTokenIds)
            $command->addCondition("tid IN ('".implode("', '", $aTokenIds)."')" );

        if ($iMaxEmails)
            $command->limit = $iMaxEmails;

        $command->order = 'tid';

        $oResult = TokenDynamic::model()->findAll($command);
        return $oResult;
    }

    public function findUninvitedIDs($aTokenIds = false, $iMaxEmails = 0, $bEmail = true, $SQLemailstatuscondition = '', $SQLremindercountcondition = '', $SQLreminderdelaycondition = '')
    {
        $command = new CDbCriteria;
        $command->condition = '';
        $command->addCondition("(completed ='N') or (completed='')");
        $command->addCondition("token <> ''");
        $command->addCondition("email <> ''");
        if ($bEmail) {
            $command->addCondition("(sent = 'N') or (sent = '')");
        } else {
            $command->addCondition("(sent <> 'N') AND (sent <> '')");
        }

        if ($SQLemailstatuscondition)
            $command->addCondition($SQLemailstatuscondition);

        if ($SQLremindercountcondition)
            $command->addCondition($SQLremindercountcondition);

        if ($SQLreminderdelaycondition)
            $command->addCondition($SQLreminderdelaycondition);

        if ($aTokenIds)
            $command->addCondition("tid IN ('".implode("', '", $aTokenIds)."')" );

        if ($iMaxEmails)
            $command->limit = $iMaxEmails;

        $command->order = 'tid';

        $oResult=$this->getCommandBuilder()
            ->createFindCommand($this->getTableSchema(), $command)
            ->select('tid')
            ->queryColumn();
        return $oResult;
    }

    function insertParticipant($data)
    {
            $token = new self;
            foreach ($data as $k => $v)
                $token->$k = $v;
            try
            {
                $token->save();
                return $token->tid;
            }
            catch(Exception $e)
            {
                return false;
            }
    }

    function insertToken($iSurveyID, $data)
    {
        self::sid($iSurveyID);
        return Yii::app()->db->createCommand()->insert(self::tableName(), $data);
    }
    function updateToken($tid,$newtoken)
    {
        return Yii::app()->db->createCommand("UPDATE {$this->tableName()} SET token = :newtoken WHERE tid = :tid")
        ->bindParam(":newtoken", $newtoken, PDO::PARAM_STR)
        ->bindParam(":tid", $tid, PDO::PARAM_INT)
        ->execute();
    }

    /**
     * Retrieve an array of records with an empty token, in the result is just the id (tid)
     *
     * @param int $iSurveyID
     * @return array
     */
    function selectEmptyTokens($iSurveyID)
    {
        return Yii::app()->db->createCommand("SELECT tid FROM {{tokens_{$iSurveyID}}} WHERE token IS NULL OR token=''")->queryAll();
    }

    public static function countAllAndCompleted($sid)
    {
        $select = array(
            'count(*) AS cntall',
            'sum(CASE '. Yii::app()->db->quoteColumnName('completed') . '
                 WHEN '.Yii::app()->db->quoteValue('N').' THEN 0
                          ELSE 1
                 END) AS cntcompleted',
            );
        $result = Yii::app()->db->createCommand()->select($select)->from('{{tokens_' . $sid . '}}')->queryRow();
        return $result;
    }

   /**
     * Creates and inserts token for a specific token record and returns the token string created
     *
     * @param int $iTokenID
     * @return string  token string
     */
    function createToken($iTokenID)
    {
        //get token length from survey settings
        $tlrow = Survey::model()->findByAttributes(array("sid"=>self::$sid));
        $iTokenLength = $tlrow->tokenlength;

        //get all existing tokens
        $criteria = $this->getDbCriteria();
        $criteria->select = 'token';
        $ntresult = $this->findAllAsArray($criteria);
        foreach ($ntresult as $tkrow)
        {
            $existingtokens[] = $tkrow['token'];
        }
        //create new_token
        $bIsValidToken = false;
        while ($bIsValidToken == false)
        {
            $newtoken = randomChars($iTokenLength);
            if (!in_array($newtoken, $existingtokens)) {
                $existingtokens[] = $newtoken;
                $bIsValidToken = true;
            }
        }
        //update specific token row
        $itresult = $this->updateToken($iTokenID, $newtoken);
        return $newtoken;
    }

    /**
     * Creates tokens for all token records that have empty token fields and returns the number
     * of tokens created
     *
     * @param int $iSurveyID
     * @return array ( int number of created tokens, int number to be created tokens)
     */
    function createTokens($iSurveyID)
    {
        $tkresult = $this->selectEmptyTokens($iSurveyID);
        //Exit early if there are not empty tokens
        if (count($tkresult)===0) return array(0,0);

        //get token length from survey settings
        $tlrow = Survey::model()->findByAttributes(array("sid"=>$iSurveyID));
        $iTokenLength = $tlrow->tokenlength;

        //if tokenlength is not set or there are other problems use the default value (15)
        if(empty($iTokenLength))
        {
            $iTokenLength = 15;
        }
        //Add some criteria to select only the token field
        $criteria = $this->getDbCriteria();
        $criteria->select = 'token';
        $ntresult = $this->findAllAsArray($criteria);   //Use AsArray to skip active record creation

        // select all existing tokens
        foreach ($ntresult as $tkrow)
        {
            $existingtokens[$tkrow['token']] = true;
        }

        $newtokencount = 0;
        $invalidtokencount=0;
        foreach ($tkresult as $tkrow)
        {
            $bIsValidToken = false;
            while ($bIsValidToken == false && $invalidtokencount<50)
            {
                $newtoken = randomChars($iTokenLength);
                if (!isset($existingtokens[$newtoken]))
                {
                    $existingtokens[$newtoken] = true;
                    $bIsValidToken = true;
                    $invalidtokencount=0;
                }
                else
                {
                    $invalidtokencount ++;
                }
            }
            if($bIsValidToken)
            {
                $itresult = $this->updateToken($tkrow['tid'], $newtoken);
                $newtokencount++;
            }
            else
            {
                break;
            }
        }

        return array($newtokencount,count($tkresult));
    }

     /**
     * This method is invoked before saving a record (after validation, if any).
     * The default implementation raises the {@link onBeforeSave} event.
     * You may override this method to do any preparation work for record saving.
     * Use {@link isNewRecord} to determine whether the saving is
     * for inserting or updating record.
     * Make sure you call the parent implementation so that the event is raised properly.
     * @return boolean whether the saving should be executed. Defaults to true.
     */
    public function beforeSave()
    {
        if ($this->usesleft>0)
        {
            $this->completed='N';
        }
        return parent::beforeSave();
    }

    /**
     * Get CDbCriteria for a json search string
     *
     * @param array $condition
     * @return \CDbCriteria
     */
    function getSearchMultipleCondition($condition)
    {
        $i=0;
        $j=1;
        $tobedonelater =array();
        $command = new CDbCriteria;
        $command->condition = '';
        $iNumberOfConditions = (count($condition)+1)/4;
        $sConnectingOperator = 'AND';
        $aParams=array();
        while($i < $iNumberOfConditions){
            $sFieldname=$condition[$i*4];
            $sOperator=$condition[($i*4)+1];
            $sValue=$condition[($i*4)+2];
            switch ($sOperator)
            {
                case 'equal':
                    $command->addCondition($sFieldname.' = :condition_'.$i, $sConnectingOperator);
                    $aParams[':condition_'.$i] = $sValue;
                    break;
                case 'contains':
                    $command->addCondition($sFieldname.' LIKE :condition_'.$i, $sConnectingOperator);
                    $aParams[':condition_'.$i] = '%'.$sValue.'%';
                    break;
                case 'notequal':
                    $command->addCondition($sFieldname.' <> :condition_'.$i, $sConnectingOperator);
                    $aParams[':condition_'.$i] = $sValue;
                    break;
                case 'notcontains':
                    $command->addCondition($sFieldname.' NOT LIKE :condition_'.$i, $sConnectingOperator);
                    $aParams[':condition_'.$i] = '%'.$sValue.'%';
                    break;
                case 'greaterthan':
                    $command->addCondition($sFieldname.' > :condition_'.$i, $sConnectingOperator);
                    $aParams[':condition_'.$i] = $sValue;
                    break;
                case 'lessthan':
                    $command->addCondition($sFieldname.' < :condition_'.$i, $sConnectingOperator);
                    $aParams[':condition_'.$i] = $sValue;
                    break;
            }
            if (isset($condition[($i*4)+3]))
            {
                $sConnectingOperator=$condition[($i*4)+3];
            }
            else
            {
                $sConnectingOperator='AND';
            }
            $i++;

        }
        if (count($aParams)>0)
        {
            $command->params = $aParams;
        }

        return $command;
    }

    function deleteToken($tokenid)
    {
        $dlquery = "DELETE FROM ".TokenDynamic::tableName()." WHERE tid=:tokenid";
        return Yii::app()->db->createCommand($dlquery)->bindParam(":tokenid", $tokenid)->query();
    }

    function deleteRecords($iTokenIds)
    {
        foreach($iTokenIds as &$currentrow)
            $currentrow = Yii::app()->db->quoteValue($currentrow);
        $dlquery = "DELETE FROM ".TokenDynamic::tableName()." WHERE tid IN (".implode(", ", $iTokenIds).")";
        return Yii::app()->db->createCommand($dlquery)->query();
    }

    function getEmailStatus($token)
    {
        $command = Yii::app()->db->createCommand()
            ->select('emailstatus')
            ->from('{{tokens_'.intval(self::$sid).'}}')
            ->where('token=:token')
            ->bindParam(':token', $token, PDO::PARAM_STR);

        return $command->queryRow();
    }

    function updateEmailStatus($token,$status)
    {
        return Yii::app()->db->createCommand()->update('{{tokens_'.intval(self::$sid).'}}',array('emailstatus' => $status),'token = :token',array(':token' => $token ));
    }

    public function getStandardCols()
    {
        return array(
            "tid",
            "participant_id",
            "firstname",
            "lastname",
            "email",
            "emailstatus",
            "token",
            "language",
            "blacklisted",
            "sent",
            "remindersent",
            "remindercount",
            "completed",
            "usesleft",
            "validfrom",
            "validuntil",
            "mpid",
        );
    }

    public function getCustom_attributes()
    {
        $columns = $this->getMetaData()->columns;
        $attributes = array();

        foreach($columns as $sColName => $oColumn)
        {
            if (! in_array($sColName, $this->standardCols))
            {
                $attributes[$sColName] = $oColumn;
            }
        }

        return $attributes;
    }

    public function getSentFormated()
    {
        $field = $this->sent;
        return $this->getYesNoDateFormated($field);
    }

    public function getRemindersentFormated()
    {
        $field = $this->remindersent;
        return $this->getYesNoDateFormated($field);
    }

    public function getCompletedFormated()
    {
        $field = $this->completed;
        return $this->getYesNoDateFormated($field);
    }

    public function getValidfromFormated()
    {
        $field = $this->validfrom;
        return $this->getYesNoDateFormated($field);
    }

    public function getValiduntilFormated()
    {
        $field = $this->validuntil;
        return $this->getYesNoDateFormated($field);
    }

    private function getYesNoDateFormated($field)
    {
        if ( $field != 'N' && $field != '')
        {
            $field = convertToGlobalSettingFormat($field);
            $field = '<span class="text-success">'.$field.'</span>';
        }
        elseif( $field != '')
        {
            $field = '<i class="fa fa-minus text-warning"></i>';
        }
        return $field;
    }

    public function getStandardColsForGrid()
    {
        return array(
            array(
                'id'=>'tid',
                'class'=>'CCheckBoxColumn',
                'selectableRows' => '100',
            ),


            array(
                'header' => gT('Action'),
                'filter'=>false,
                'id'=>'action',
                'name' => 'actions',
                'value'=>'$data->buttons',
                'type'=>'raw',
                'htmlOptions' => array('class' => 'text-left'),
            ),

            array(
                'header' => gT('ID'),
                'name' => 'tid',
                'value'=>'$data->tid',
                'headerHtmlOptions'=>array('class' => 'hidden-xs'),
                'htmlOptions' => array('class' => 'hidden-xs text-right'),
            ),


            array(
                'header' => gT('First name'),
                'name' => 'firstname',
                'value'=>'$data->firstname',
                'headerHtmlOptions'=>array('class' => 'hidden-xs'),
                'htmlOptions' => array('class' => 'hidden-xs name'),
            ),

            array(
                'header' => gT('Last name'),
                'name' => 'lastname',
                'value'=>'$data->lastname',
                'headerHtmlOptions'=>array('class' => 'hidden-xs'),
                'htmlOptions' => array('class' => 'hidden-xs name'),
            ),

            array(
                'header' => gT('Email address'),
                'name' => 'email',
                'value'=>'$data->email',
                'headerHtmlOptions'=>array('class' => 'hidden-xs'),
                'htmlOptions' => array('class' => 'hidden-xs name'),
            ),

            array(
                'header' => gT('Email status'),
                'name' => 'emailstatus',
                'value'=>'$data->emailstatus',
                'headerHtmlOptions'=>array('class' => 'hidden-xs'),
                'htmlOptions' => array('class' => 'hidden-xs'),
            ),

            array(
                'header' => gT('Blacklisted'),
                'name' => 'blacklisted',
                'value'=>'$data->blacklisted',
                'headerHtmlOptions'=>array('class' => 'hidden-xs'),
                'htmlOptions' => array('class' => 'hidden-xs'),
            ),

            array(
                'header' => gT('Token'),
                'name' => 'token',
                'value'=>'$data->token',
                'headerHtmlOptions'=>array('class' => 'hidden-xs'),
                'htmlOptions' => array('class' => 'hidden-xs'),
            ),

            array(
                'header' => gT('Language'),
                'name' => 'language',
                'value'=>'$data->language',
                'headerHtmlOptions'=>array('class' => 'hidden-xs'),
                'htmlOptions' => array('class' => 'hidden-xs'),
            ),

            array(
                'header' => gT('Invitation sent?'),
                'name' => 'sent',
                'type'=>'raw',
                'value'=>'$data->sentFormated',
                'headerHtmlOptions'=>array('class' => 'hidden-xs'),
                'htmlOptions' => array('class' => 'hidden-xs  text-center'),
            ),

            array(
                'header' => gT('Reminder sent?'),
                'name' => 'remindersent',
                'type'=>'raw',
                'value'=>'$data->remindersentFormated',
                'headerHtmlOptions'=>array('class' => 'hidden-xs'),
                'htmlOptions' => array('class' => 'hidden-xs text-center'),
            ),

            array(
                'header' => gT('Reminder count'),
                'name' => 'remindercount',
                'value'=>'$data->remindercount',
                'headerHtmlOptions'=>array('class' => 'hidden-xs'),
                'htmlOptions' => array('class' => 'hidden-xs text-right'),
            ),

            array(
                'header' => gT('Completed?'),
                'name' => 'completed',
                'type'=>'raw',
                'value'=>'$data->completedFormated',
                'headerHtmlOptions'=>array('class' => 'hidden-xs'),
                'htmlOptions' => array('class' => 'hidden-xs text-center'),
            ),

            array(
                'header' => gT('Uses left'),
                'name' => 'usesleft',
                'value'=>'$data->usesleft',
                'headerHtmlOptions'=>array('class' => 'hidden-xs'),
                'htmlOptions' => array('class' => 'hidden-xs text-right'),
            ),
            array(
                'header' => gT('Valid from'),
                'name' => 'validfrom',
                'type'=>'raw',
                'value'=>'$data->validfromFormated',
                'headerHtmlOptions'=>array('class' => 'hidden-xs'),
                'htmlOptions' => array('class' => 'hidden-xs text-center'),
            ),
            array(
                'header' => gT('Valid until'),
                'type'=>'raw',
                'name' => 'validuntil',
                'value'=>'$data->validuntilFormated',
                'headerHtmlOptions'=>array('class' => 'hidden-xs'),
                'htmlOptions' => array('class' => 'hidden-xs'),
            ),
        );
    }

    public function getAttributesForGrid()
    {
        $aCustomAttributesCols = array();
        //$aCustomAttributes = $this->custom_attributes;

        $oSurvey = Survey::model()->findByAttributes(array("sid"=>self::$sid));
        $aCustomAttributes  = $oSurvey->tokenAttributes;

        // Custom attributes
        foreach($aCustomAttributes as $sColName => $oColumn)
        {
            $desc = ($oColumn['description']!='')?$oColumn['description']:$sColName;
            $aCustomAttributesCols[] = array(
                'header' => $desc,// $aAttributedescriptions->$sColName->description,
                'name' => $sColName,
                'value'=>'$data->'.$sColName,
                'headerHtmlOptions'=>array('class' => 'hidden-xs'),
                'htmlOptions' => array('class' => 'hidden-xs'),
            );
        }


        return array_merge($this->standardColsForGrid, $aCustomAttributesCols);
    }

    public function getbuttons()
    {
        $sPreviewUrl  = App()->createUrl("/survey/index/sid/".self::$sid."/token/".$this->token.'/lang/'.$this->language.'/newtest/Y');
        $sEditUrl     = App()->createUrl("/admin/tokens/sa/edit/iSurveyId/".self::$sid."/iTokenId/$this->tid/ajax/true");
        $sInviteUrl   = App()->createUrl("/admin/tokens/sa/email/surveyid/".self::$sid."/tokenids/$this->tid");
        $sRemindUrl   = App()->createUrl("admin/tokens/sa/email/action/remind/surveyid/".self::$sid."/tokenids/$this->tid");
        $button = '';

        // View response details
        if ($this->survey->isActive && Permission::model()->hasSurveyPermission(self::$sid, 'responses', 'read') && $this->survey->anonymized != 'Y')
        {
            if (count($this->responses)>0)
            {

                if (count($this->responses)<2)
                {
                    $sResponseUrl = App()->createUrl("admin/responses/sa/viewbytoken/surveyid/".self::$sid, array('token'=>$this->token));
                    $button .= '<a class="btn btn-default btn-xs" href="'.$sResponseUrl.'" target="_blank" role="button" data-toggle="tooltip" title="'.gT("View response details").'"><span class="glyphicon glyphicon-list-alt" ></span></a>';
                }
                // Multiple answers, give choice to user
                else
                {
                    // TODO: link to Response grid filtered on the base of this Token (when responses will be rewritten using CGridView instead of jQgrid)
                    $sResponseUrl = App()->createUrl("admin/responses/sa/viewbytoken/surveyid/".self::$sid, array('token'=>$this->token));
                    $button .= '<a class="btn btn-default btn-xs" href="'.$sResponseUrl.'" target="_blank" role="button" data-toggle="tooltip" title="'.gT("View last response details").'"><span class="glyphicon glyphicon-list-alt" ></span></a>';
                }
            }
        }
        else
        {
            $button .= '<span class="btn btn-default btn-xs disabled blank_button" href="#"><span class="fa-fw fa" ></span></span>';
        }

        // Launch the survey with this token
        if( ($this->completed=="N" || $this->completed=="" || $this->survey->alloweditaftercompletion == "Y") && Permission::model()->hasSurveyPermission(self::$sid, 'responses', 'create') )
        {
            $button .= '<a class="btn btn-default btn-xs" href="'.$sPreviewUrl.'" target="_blank" role="button" data-toggle="tooltip" title="'.gT('Launch the survey with this token').'"><span class="icon-do" ></span></a>';
        }
        else
        {
            $button .= '<span class="btn btn-default btn-xs disabled blank_button" href="#"><span class="fa-fw fa" ></span></span>';
        }

        // Invite or Remind
        if ($this->emailstatus && $this->email  && Permission::model()->hasSurveyPermission(self::$sid, 'tokens', 'update'))
        {
            if($this->completed == 'N' && $this->usesleft > 0)
            {
                if($this->sent == 'N')
                {
                    $button .= '<a class="btn btn-default btn-xs" href="'.$sInviteUrl.'" role="button" data-toggle="tooltip" title="'.gT('Send email invitation').'"><span class="icon-invite" ></span></a>';
                }
                else
                {
                    $button .= '<a class="btn btn-default btn-xs" href="'.$sRemindUrl.'" role="button" data-toggle="tooltip" title="'.gT('Send email reminder').'"><span class="icon-remind " ></span></a>';
                }
            }
            else
            {
                $button .= '<span class="btn btn-default btn-xs disabled blank_button" href="#"><span class="fa-fw fa" ></span></span>';
            }

        }
        else
        {
            $button .= '<span class="btn btn-default btn-xs disabled blank_button" href="#"><span class="fa-fw fa" ></span><!-- Invite or Remind --></span>';
        }

        // TODO: permission check
        if (Permission::model()->hasSurveyPermission(self::$sid, 'tokens', 'update'))
        {
            // $sEditUrl     = App()->createUrl("/admin/tokens/sa/edit/iSurveyId/".self::$sid."/iTokenId/$this->tid");
            $button .= '<a class="btn btn-default btn-xs edit-token" href="#" data-sid="'.self::$sid.'" data-tid="'.$this->tid.'" data-url="'.$sEditUrl.'" role="button" data-toggle="tooltip" title="'.gT('Edit this survey participant').'"><span class="icon-edit" ></span></a>';
        }
        else
        {
            $button .= '<span class="btn btn-default btn-xs disabled blank_button" href="#"><span class="fa-fw fa" ></span><!-- Edit --></span>';
        }

        // Display participant in CPDB
        if (!empty($this->participant_id) && $this->participant_id != "" && Permission::model()->hasGlobalPermission('participantpanel','read'))
        {
            $onClick = "sendPost('".App()->createUrl('admin/participants/sa/displayParticipants')."','',['searchcondition'],['participant_id||equal||{$this->participant_id}']);";
            $button .= '<a class="btn btn-default btn-xs" href="#" role="button" data-toggle="tooltip" title="'.gT('View this person in the central participants database').'" onclick="'.$onClick.'"><span class="icon-cpdb" ></span></a>';
        }
        else
        {
            $button .= '<span class="btn btn-default btn-xs disabled blank_button" href="#"><span class="fa-fw fa" ><!-- Display participant in CPDB--></span></span>';
        }
        return $button;
    }

    public function search()
    {
        $pageSize=Yii::app()->user->getState('pageSize',Yii::app()->params['defaultPageSize']);

        $sort = new CSort();
        $sort->defaultOrder = 'tid ASC';
        $sort->attributes = array(
          'tid'=>array(
            'asc'=>'tid',
            'desc'=>'tid desc',
          ),
          'partcipant'=>array(
            'asc'=>'partcipant',
            'desc'=>'partcipant desc',
          ),

          'firstname'=>array(
            'asc'=>'firstname',
            'desc'=>'firstname desc',
          ),

          'lastname'=>array(
            'asc'=>'lastname',
            'desc'=>'lastname desc',
          ),

          'email'=>array(
            'asc'=>'email',
            'desc'=>'email desc',
          ),

          'emailstatus'=>array(
            'asc'=>'emailstatus',
            'desc'=>'emailstatus desc',
          ),

          'token'=>array(
            'asc'=>'token',
            'desc'=>'token desc',
          ),

          'language'=>array(
            'asc'=>'language',
            'desc'=>'language desc',
          ),

          'blacklisted'=>array(
            'asc'=>'blacklisted',
            'desc'=>'blacklisted desc',
          ),

          'sent'=>array(
            'asc'=>'sent',
            'desc'=>'sent desc',
          ),

          'remindersent'=>array(
            'asc'=>'remindersent',
            'desc'=>'remindersent desc',
          ),

          'remindercount'=>array(
              'asc' => 'remindercount',
              'desc' => 'remindercount desc',
          ),

          'completed'=>array(
            'asc'=>'completed',
            'desc'=>'completed desc',
          ),

          'usesleft'=>array(
            'asc'=>'usesleft',
            'desc'=>'usesleft desc',
          ),

          'validfrom'=>array(
            'asc'=>'validfrom',
            'desc'=>'validfrom desc',
          ),

          'validuntil'=>array(
            'asc'=>'validuntil',
            'desc'=>'validuntil desc',
          ),
      );

      // Make sortable custom attributes
      foreach($this->custom_attributes as $sColName => $oColumn)
      {
          $sort->attributes[$sColName] =  array(
              'asc'=>$sColName,
              'desc'=>$sColName.' desc',
          );
      }

      $criteria = new CDbCriteria;
      $criteria->compare('tid',$this->tid,true);
      $criteria->compare('token',$this->token,true);
      $criteria->compare('firstname',$this->firstname,true);
      $criteria->compare('lastname',$this->lastname,true);
      $criteria->compare('email',$this->email,true);
      $criteria->compare('emailstatus',$this->emailstatus,true);
      $criteria->compare('blacklisted',$this->blacklisted,true);
      $criteria->compare('token',$this->token,true);
      $criteria->compare('language',$this->language,true);
      $criteria->compare('sent',$this->sent,true);
      $criteria->compare('remindersent',$this->remindersent,true);
      $criteria->compare('remindercount',$this->remindercount,true);
      $criteria->compare('completed',$this->completed,true);
      $criteria->compare('usesleft',$this->usesleft,true);
      $criteria->compare('validfrom',$this->validfrom,true);
      $criteria->compare('validuntil',$this->validuntil,true);

      foreach($this->custom_attributes as $sColName => $oColumn)
      {
          $criteria->compare($sColName,$this->$sColName,true);
      }

      $dataProvider=new CActiveDataProvider('TokenDynamic', array(
          'sort'=>$sort,
          'criteria'=>$criteria,
          'pagination'=>array(
              'pageSize'=>$pageSize,
          ),
      ));

      return $dataProvider;

    }
}
?>
