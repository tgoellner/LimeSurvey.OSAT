<?php

class OsatAssessment
{
    protected $surveyId;
    protected $surveyLanguage;
    protected $hasAssessment = false;
    protected $sToken;
    protected $sLanguage;
    protected $exactMatch;
    protected $thisCountsInAverage;

    protected $requestedFilter;
    protected $activeFilter;
    protected $availableFilter;

    protected $min;
    protected $max;
    protected $total;
    protected $average;
    protected $tokenCount;

    protected $groups;

    protected $questions;

    public function __construct(array $attributes = [])
    {
        if(empty($attributes['surveyId']) || empty($attributes['sToken']))
        {
            throw new Exception('To calculate assessments a surveyId and a token id have to be given');
        }

        $this->surveyId = $attributes['surveyId'];
        $this->sToken = $attributes['sToken'];
        $this->sLanguage = empty($attributes['sLanguage']) ? App()->language : $attributes['sLanguage'];
        $this->requestedFilter = empty($attributes['filter']) || !is_array($attributes['filter']) ? [] : $attributes['filter'];

        $this->exactMatch = empty($attributes['exactMatch']) || !((bool) $attributes['exactMatch']);
        $this->thisCountsInAverage = empty($attributes['thisCountsInAverage']) || !((bool) $attributes['thisCountsInAverage']);

        $this->init();
    }

    protected function init()
    {
        $this->min = 0;
        $this->max = 0;
        $this->total = 0;
        $this->average = 0;
        $this->tokenCount = 0;
        $this->groups = [];
        $this->questions = [];

        if($this->initSurvey())
        {
            // TODO: Also create stats for survey without assessment values -
            // for now we just quit!
            if(!$this->hasAssessment)
            {
                return null;
            }

            if($this->initTokens())
            {
                $this->initMinMax();
            }
        }
        else
        {
            throw new Exception(sprintf('No valid survey found for survey id %s', $this->surveyId));
        }
    }

    protected function initSurvey()
    {
        if($surveyData = Survey::model()->findByPk($this->surveyId))
        {
            $this->surveyLanguage = $surveyData->language;
            $this->hasAssessment = !($surveyData->assessments != "Y");
            return true;
        }
        return false;
    }

    public function getUrl(array $attributes = [])
    {
        // let's restart with this new token!
		$controller = new RegisterController('survey');

        $attributes = array_replace($attributes, array('token' => $this->sToken, 'lang' => $this->sLanguage));

		return $controller->createUrl("/survey/index/sid/" . $this->surveyId, $attributes);
    }

    public function getAvailableFilter($key = null)
    {
        if(!isset($this->availableFilter))
        {
            $this->availableFilter = [];
            if($surveyInfo = getSurveyInfo($this->surveyId, $this->sLanguage))
            {
                if(!empty($surveyInfo['attributedescriptions']))
                {
                    foreach($surveyInfo['attributedescriptions'] as $label => $options)
                    {
                        if($options['mandatory'] != 'Y')
                        {
                            continue;
                        }

                        // let's check if the attribute contains md5 or sha1 strings - we don't want to allow filtering against those!
                        $query = "SELECT `$label`, COUNT(*) AS count FROM {{tokens_$this->surveyId}} WHERE $label NOT REGEXP '^[0-9a-f]{32}$' AND $label NOT REGEXP '^[0-9a-f]{40}$' AND $label <> '' GROUP BY $label ORDER BY $label ASC";
                        $rows = Yii::app()->db->createCommand($query)->query()->readAll();
                        if(empty($rows))
                        {
                            // nothing found so we won't show this filter
                            continue;
                        }

                        $options['options'] = [];
                        foreach($rows as $row)
                        {
                            $options['options'][$row[$label]] = $row['count'];
                        }
                        unset($query, $rows, $row);
                        $this->availableFilter[$label] = $options;
                    }
                }
                unset($label, $options);
            }
            unset($surveyInfo);
        }

        if(!empty($key))
        {
            return isset($this->availableFilter[$key]) ? $this->availableFilter[$key] : null;
        }
        return $this->availableFilter;
    }

    public function getActiveFilter($key = null)
    {
        if(!isset($this->activeFilter))
        {
            $this->activeFilter = [];
            if(!empty($this->requestedFilter))
            {
                if($availableFilter = $this->getAvailableFilter())
                {
                    foreach($this->requestedFilter as $label => $values)
                    {
                        if(isset($availableFilter[$label]))
                        {
                            if(!empty($availableFilter[$label]['options']))
                            {
                                $values = (array) $values;
                                $result = array_intersect($values, array_keys($availableFilter[$label]['options']));

                                if(!empty($result))
                                {
                                    $this->activeFilter[$label] = $result;
                                }
                                unset($result);
                            }
                        }
                    }
                    unset($label, $values);
                }
            }
        }

        if(!empty($key))
        {
            return isset($this->activeFilter[$key]) ? $this->activeFilter[$key] : [];
        }

        return $this->activeFilter;
    }

    protected function getTokenCount()
    {
        return $this->tokenCount + ($this->thisCountsInAverage ? 1 : 0);
    }

    protected function initTokens()
    {
        // let's prepare the token data
        if(!($tokenData = Token::model($this->surveyId)->findByAttributes(array('token' => $this->sToken))) || ($tokenData->completed == 'N'))
        {
            // token is not found or it did not complete the survey yet
            return false;
        }
        $this->tokenData = $tokenData;

        $answered = [];
        $unanswered = [];

        if(!($this->getScores()))
        {
            // no scores found - nothing to do.
            return false;
        }
        $query = "SELECT * FROM {{survey_" . $this->surveyId . "}} WHERE token = '" . $this->sToken . "'";

        $rows = Yii::app()->db->createCommand($query)->query()->readAll();
        if(empty($rows))
        {
            // token not found - nothing to do.
            return false;
        }

        foreach($rows[0] as $field => $value)
        {
            if(preg_match('/^\d+X\d+X\d+$/', $field))
            {
                $gid = preg_replace('/^\d+X(\d+)X\d+$/', "$1", $field);
                $qid = preg_replace('/^\d+X\d+X(\d+)$/', "$1", $field);

                if($this->exactMatch && ($value === null || $value === ''))
                {
                    $unanswered[] = $field;
                    continue;
                }

                $answered[] = $field;

                $value = $this->getScoreForValue($field, $value);

                $this->total+= $value;

                // a value is set, let's store it!
                if(empty($this->groups[$gid]))
                {
                    // set up group
                    $this->groups[$gid] = [
                        'total' => 0,
                        'average' => 0,
                        'min' => 0,
                        'max' => 0,
                        'questions' => []
                    ];
                }
                $this->groups[$gid]['questions'][] = $qid;
                $this->groups[$gid]['total']+= $value;

                // store answer to group
                $this->questions[$qid] = [
                    'total' => $value,
                    'average' => 0,
                    'min' => 0,
                    'max' => 0
                ];

                unset($gid, $qid);
            }
        }
        unset($field, $value, $query, $rows);

        $filter = $this->getActiveFilter();

        // and now the answers of all the other tokens matching the answers of the current one (only )
        $query = "SELECT
            s.token,
            t.completed,
            " . join(", ", $answered) .
            (!empty($unanswered) && empty($filter) ? ", CONCAT(" . join(", ", $unanswered) . ") AS empty" : "") . "
        FROM
            {{survey_" . $this->surveyId . "}} s
        LEFT JOIN({{tokens_" . $this->surveyId . "}} t) ON (t.token = s.token)
        WHERE t.completed != 'N'
        AND s.token <> '" . $this->sToken . "'";

        // let's add the filters
        if(!empty($filter))
        {
            foreach($filter as $field => $value)
            {
                $query.=" AND t.$field IN ('" . (join("', '", (array) $value)) . "')";
            }
        }

        $query.= (!empty($unanswered && empty($filter)) ? " HAVING (empty = '' OR empty IS NULL)" : "") . "
        ORDER BY t.completed ASC";

        unset($answered, $unanswered);

        $rows = Yii::app()->db->createCommand($query)->query()->readAll();
        $this->tokenCount = count($rows);

        if(!empty($this->tokenCount))
        {
            // other tokens found, let's count their results!
            while($row = array_shift($rows))
            {
                foreach($row as $field => $value)
                {
                    if(preg_match('/^\d+X\d+X\d+$/', $field))
                    {
                        $gid = preg_replace('/^\d+X(\d+)X\d+$/', "$1", $field);
                        $qid = preg_replace('/^\d+X\d+X(\d+)$/', "$1", $field);

                        $value = $this->getScoreForValue($field, $value);
                        if(isset($this->questions[$qid]))
                        {
                            $this->questions[$qid]['average']+= $value;

                            if(isset($this->groups[$gid]))
                            {
                                $this->groups[$gid]['average']+= $value;
                            }
                            $this->average+= $value;
                        }

                        unset($gid, $qid);
                    }
                }
                unset($field, $value);
            }
            unset($row, $rows, $query);
        }

        // now caluclate averages for questions
        foreach($this->questions as $id => &$q)
        {
            if($this->thisCountsInAverage)
            {
                $q['average'] = ($q['average'] + $q['total']) / $this->getTokenCount();
            }
            else if($this->getTokenCount())
            {
                $q['average']/= $this->getTokenCount();
            }
        }

        // now caluclate averages for groups
        foreach($this->groups as &$g)
        {
            if($this->thisCountsInAverage)
            {
                $g['average'] = ($g['average'] + $g['total']) / $this->getTokenCount();
            }
            else if($this->getTokenCount())
            {
                $g['average']/= $this->getTokenCount();
            }
        }

        // and for the survey
        if($this->thisCountsInAverage)
        {
            $this->average = ($this->average + $this->total) / $this->getTokenCount();
        }
        else if($this->getTokenCount())
        {
            $this->average/= $this->getTokenCount();
        }

        return true;
    }

    protected function initMinMax()
    {
        $query = "SELECT DISTINCT
            CONCAT(q.sid,'X',q.gid,'X',q.qid) AS field,
            q.sid,
            q.gid,
            q.qid,
            MAX(a.assessment_value) as max,
            MIN(a.assessment_value) as min
        FROM
            {{answers}} a
        LEFT JOIN({{questions}} q) ON (a.qid = q.qid)
        WHERE q.qid IN (" . @join(", ", array_keys($this->questions)) . ")
        GROUP BY q.sid, q.gid, q.qid
        ORDER BY q.sid, q.gid, q.qid";

        $rows = Yii::app()->db->createCommand($query)->query()->readAll();
        if(count($rows))
        {
            foreach($rows as $i => $row)
            {
                $gid = $row['gid'];
                $qid = $row['qid'];

                $this->questions[$qid]['min']+= (float) $row['min'];
                $this->questions[$qid]['max']+= (float) $row['max'];

                $this->groups[$gid]['min']+= $this->questions[$qid]['min'];
                $this->groups[$gid]['max']+= $this->questions[$qid]['max'];

                $this->min+= $this->questions[$qid]['min'];
                $this->max+= $this->questions[$qid]['max'];

                unset($qid, $gid);
            }
        }
        unset($rows, $row, $i, $query);

        return true;
    }

    protected function getScoreForValue($field, $value)
    {
        if(is_string($field))
        {
            if(preg_match('/^\d+X\d+X\d+X.*$/', $field))
            {
                $scoreId = $field;
            }
            elseif(is_string($value))
            {
                $scoreId = $field . 'X' . $value;
            }
        }
        elseif(is_array($field) && isset($field['gid'], $field['qid']))
        {
            $scoreId = $this->surveyId . 'X' . $field['gid'] . 'X' . $field['qid'] . 'X' . $value;
        }

        if(!empty($scoreId) && isset($this->scores[$scoreId]))
        {
            // score value is found
            $value = $this->scores[$scoreId];
        }
        elseif(!is_array($value) && !is_object($value))
        {
            $value = (float) $value;
            if(is_nan($value))
            {
                $value = 0;
            }
        }
        else
        {
            $value = 0;
        }

        return $value;
    }

    protected function getScores()
    {
        if(!isset($this->scores))
        {
            $this->scores = [];

            $query = "SELECT DISTINCT
                CONCAT(q.sid,'X',q.gid,'X',q.qid,'X',a.code) AS field,
                q.sid,
                q.gid,
                q.qid,
                a.assessment_value as score
            FROM
                {{answers}} a
            LEFT JOIN({{questions}} q) ON (a.qid = q.qid)
            WHERE sid = '" . $this->surveyId . "'
            ORDER BY q.sid, q.qid";

            $rows = Yii::app()->db->createCommand($query)->query()->readAll();
            if(count($rows))
            {
                while($row = array_shift($rows))
                {
                    $this->scores[$row['field']] = (float) $row['score'];
                }
                unset($row);
            }
            unset($rows, $query);
        }

        return $this->scores;
    }

    protected function _getValue($name, $arguments = null)
    {
        // get(Group, Question)(Min, Max, Total, Average)
        $reg = '/^get([a-z]+)?(Min|Max|Total|Average)$/i';

        if(preg_match($reg, $name))
        {
            $type = strtolower(preg_replace($reg, "$1", $name));
            $what = strtolower(preg_replace($reg, "$2", $name));

            if(empty($type) && isset($this->$what))
            {
                return empty($this->max) = 0 : ($this->$what / $this->max) * 100;
            }
            else
            {
                $id = isset($arguments[0]) ? $arguments[0] : null;
                $type.='s';
                if(!empty($id) && isset($this->$type))
                {
                    $data = $this->$type;
                    if(isset($data[$id][$what]))
                    {
                        return empty($data[$id]['max']) ? 0 : ($data[$id][$what] / $data[$id]['max']) * 100;
                    }
                }
            }
            return false;
        }
        return null;
    }

    public function get($attr = null)
    {
        if(!empty($attr) && is_string($attr))
        {
            if(isset($this->$attr))
            {
                return $this->$attr;
            }
            return null;
        }
        else
        {
            return get_class_vars();
        }
    }

    public function __call($name, $arguments)
    {
        if(!method_exists($this, $name))
        {
            // getMin, getMax
            // getGroupMin, getGroupMax
            // getQuestionMin, getQuestionMax
            if(($r = $this->_getValue($name, $arguments)) !== null)
            {
                return $r;
            }
        }

        return null;
    }

    protected function getAssessments($gid = null)
    {
        $assessments = [];
        $scope = "scope = 'T'";
        $total = $this->total;

        if($gid === null)
        {
            if(isset($this->assessments))
            {
                return $this->assessments;
            }
        }
        else
        {
            $scope = null;
            if(isset($this->groups[$gid]))
            {
                if(isset($this->groups[$gid]['assessments']))
                {
                    return $this->groups[$gid]['assessments'];
                }

                $scope = "scope = 'G' AND gid = $gid";
                $total = $this->groups[$gid]['total'];
            }
        }

        if(empty($scope))
        {
            return null;
        }

        $languages = array_unique([$this->sLanguage, $this->surveyLanguage]);

        $query = "SELECT
            id, name, message
        FROM
            {{assessments}}
        WHERE
            sid = '". $this->surveyId . "'
        AND
            $scope
        AND
            minimum <= " . $total . "
        AND
            maximum >= " . $total . "
        AND
            language IN('" . join("', '", $languages) . "')
        ORDER BY FIELD(language, '" . join("', '", $languages) . "')";

        $rows = Yii::app()->db->createCommand($query)->query()->readAll();
        if(count($rows))
        {
            while($row = array_shift($rows))
            {
                $aid = $row['id'];
                if(!isset($assessments[$aid]))
                {
                    $assessments[$aid] = [
                        'name' => '',
                        'message' => ''
                    ];
                }
                else if(count(array_keys($assessments[$aid])) == count(@array_filter(array_values($assessments[$aid]))))
                {
                    // all texts set
                    continue;
                }

                // search for texts
                foreach($assessments[$aid] as $k => $v)
                {
                    if(empty($v) && !empty($row[$k]))
                    {
                        $assessments[$aid][$k] = $row[$k];
                    }
                }
                unset($k, $v, $aid);
            }
            unset($row);
        }
        unset($rows, $query, $languages);

        if($gid === null)
        {
            $this->assessments = $assessments;
        }
        else
        {
            $this->groups[$gid]['assessments'] = $assessments;
        }

        return $assessments;
    }

    public function getGroupAssessments($gid = 0)
    {
        return $this->getAssessments($gid);
    }

    public function getGroupAssessment($gid = 0, $aid = null)
    {
        if($a = $this->getAssessments($gid))
        {
            if($aid != null)
            {
                if(isset($a[$aid]))
                {
                    return $a[$aid];
                }
            }
            else
            {
                // no aid, return first one
                return reset($a);
            }
        }
    }

    public function getSurveyAssessments()
    {
        return $this->getAssessments();
    }

    public function getSurveyAssessment($aid = null)
    {
        if($a = $this->getSurveyAssessments())
        {
            if($aid != null)
            {
                if(isset($a[$aid]))
                {
                    return $a[$aid];
                }
            }
            else
            {
                // no aid, return first one
                return reset($a);
            }
        }
    }

    public function getGroups($gid = null)
    {
        if($gid === null)
        {
            return $this->groups;
        }

        if(isset($this->groups[$gid]))
        {
            return $this->groups[$gid];
        }

        return null;
    }

    public function getGroupQuestions($gid = null)
    {
        if($gid !== null && isset($this->groups[$gid]['questions']))
        {
            $ret = [];
            foreach($this->groups[$gid]['questions'] as $qid)
            {
                if(isset($this->questions[$qid]))
                {
                    $ret[$qid] = $this->questions[$qid];
                }
            }
            return $ret;
        }

        return null;
    }

    public function getQuestions($qid = null)
    {
        if($qid === null)
        {
            return $this->questions;
        }

        if(isset($this->questions[$qid]))
        {
            return $this->questions[$qid];
        }

        return null;
    }
}
