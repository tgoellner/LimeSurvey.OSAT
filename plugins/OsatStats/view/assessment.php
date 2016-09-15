<div class="osatstats">
<?php if(is_object($assessment) && $groups = $assessment->getGroups()): ?>
    <?php echo $header; ?>
    <div class="osatstats--assessment">
        <?php

            $grouplist = [];
            $sess = !empty($_SESSION['survey_'.$assessment->get('surveyId')]['grouplist']) ? $_SESSION['survey_'.$assessment->get('surveyId')]['grouplist'] : [];

            if(class_exists('OsatExpressions'))
            {
                $exp = new OsatExpressions();
            }
            else
            {
                $exp = null;
            }

            $count = 1;
            foreach($groups as $gid => $group)
            {
                // init group in list and add name
                $grouplist[$gid] = [
                    'label' => str_pad($count, 2, '0', STR_PAD_LEFT),
                    'total' => $assessment->getGroupTotal($gid),
                    'total_score' => $group['total'],
                    'average' => $assessment->getGroupAverage($gid),
                    'average_score' => $group['average'],
                    'min' => $group['min'],
                    'max' => $group['max'],
                    'questions' => count($group['questions']),
                    'assessment' => $assessment->getGroupAssessment($gid),
                    'long_title' => ''
                ];

                // and get the name!
                if(!empty($sess[$gid]['group_name']))
                {
                    $grouplist[$gid]['name'] = $sess[$gid]['group_name'];
                    $grouplist[$gid]['summary'] = !empty($exp) ? $exp->groupoutro($gid) : $sess[$gid]['description'];
                    $grouplist[$gid]['long_title'] = !empty($exp) ? $exp->groupintro($gid) : '';
                }
                elseif($sGroup = QuestionGroup::model()->findByAttributes(array('sid' => $assessment->get('surveyId'), 'gid' => $gid, 'language' => $assessment->get('sLanguage'))))
                {
                    $grouplist[$gid]['name'] = $sGroup->group_name;
                    $grouplist[$gid]['summary'] = !empty($exp) ? $exp->groupoutro($gid) : $sGroup->description;
                    $grouplist[$gid]['long_title'] = !empty($exp) ? $exp->groupintro($gid) : '';
                }
                else
                {
                    $grouplist[$gid]['name'] = $grouplist[$gid]['label'];
                    $grouplist[$gid]['summary'] = '';
                }

                if(empty($grouplist[$gid]['long_title']) && !empty($grouplist[$gid]['name']))
                {
                    $grouplist[$gid]['long_title'] = $grouplist[$gid]['name'];
                }
                unset($sGroup);

                if(!empty($grouplist[$gid]['summary']))
                {
                    $grouplist[$gid]['summary'] = '<h4>{{Background}}</h4>' . $grouplist[$gid]['summary'];
                }

                $count++;
            }
            unset($sess, $exp);

            // and finally our survey stats
            $grouplist['survey'] = array(
                'name' => '{{Next steps}}',
                'label' => str_pad($count, 2, '0', STR_PAD_LEFT),
                'total' => $assessment->getTotal(),
                'total_score' => $assessment->get('total'),
                'average' => $assessment->getAverage(),
                'average_score' => $assessment->get('average'),
                'min' => $assessment->get('min'),
                'max' => $assessment->get('max'),
                'questions' => count($assessment->get('questions')),
                'assessment' => $assessment->getSurveyAssessment(),
                'summary' => ''
            );

            if($surveyInfo = getSurveyInfo($assessment->get('surveyId'), $assessment->get('sLanguage')))
            {
                if(!empty($surveyInfo['surveyls_endtext']))
                {
                    $grouplist['survey']['summary'] = $surveyInfo['surveyls_endtext'];
                }
            }
            unset($surveyInfo);

            // create chartist data sets
            $chartist = [
                'labels' => [],
                'series' => [
                    [],
                    []
                ]
            ];

            foreach($grouplist as $gid => $group)
            {
                $chartist['labels'][] = $group['label'];
                $chartist['series'][0][] = $group['total'];
                $chartist['series'][1][] = $group['average'];
            }

        ?>
        <?php /*<div id="osatstats-chart" class="osatstats--chartwapper has--averages" aria-described-by="osatstats-table">
            <!-- div class="osatstats--chart is--chartist" data-json="<?php echo base64_encode(json_encode($chartist)); ?>"></div //-->
            <div class="osatstats--chart">
                <dl class="results"><?php foreach($grouplist as $gid => $group): ?>

                    <dt class="group-<?php echo $group['label']; ?>">
                        <?php echo $sGroup = QuestionGroup::model()->findByAttributes(array('sid' => $assessment->get('surveyId'), 'gid' => $gid, 'language' => $assessment->get('sLanguage')));
                    </dt>
                </dl><?php $count = 1; foreach($groups as $gid => $group): ?>
                </span><?php endforeach; ?>
            </div>
        </div> */ ?>

        <div id="osatstats-chart" class="osatstats-table is--chart<?php echo $hasAverages ? ' has--averages' : ''; ?>">

            <div class="osatstats-table--filter">
                <h3 class="osatstats-table--filter--title">{{Compare by:}}</h3>
                <?php echo CHtml::form($assessment->getUrl(), 'post', array('id'=>'assessmentfilter')); ?>

                    <div class="form-group">
                        <button type="submit" id="assessmentfilter_overall" value="1" name="osatstats[filter][reset]" class="btn">{{Overall}}</button>
                    </div>

                    <?php foreach($assessment->getAvailableFilter() as $label => $options): ?><div class="form-group is--select">
                        <select id="assessmentfilter_<?php echo $label; ?>" aria-label="{{<?php echo $options['description']; ?>}}" name="osatstats[filter][<?php echo $label; ?>][]" class="form-control">
                            <option value="">{{<?php echo $options['description']; ?>}}</option>
                        <?php foreach($options['options'] as $value => $count): ?>

                            <option<?php if(in_array($value, $assessment->getActiveFilter($label))): ?> selected="selected"<?php endif; ?>>{{<?php echo $value; ?>}}</option>
                        <?php endforeach; ?>

                        </select>
                        <label for="assessmentfilter_<?php echo $label; ?>" class="control-label" title="{{<?php echo $options['description']; ?>}}">
                            <?php echo $assessment->getActiveFilter($label) ? join(', ', $assessment->getActiveFilter($label)) : '{{' . $options['description'] . '}}' ?>
                        </label>
                    </div><?php endforeach; ?>
                <?php echo CHtml::endForm(); ?>
            </div>

            <div class="osatstats-table--wrapper">
                <table class="table osatstats-table--table">
                    <caption>
                        {{Your results for this survey}}
                    </caption>

                    <colgroup>
                        <col style="width:5%" />
                        <col />
                        <col style="width:20%" />
                        <col style="width:20%" />
                    </colgroup>

                    <thead>
                        <tr>
                            <th class="osatstats-table--cell is--label">
                            </th>
                            <th class="osatstats-table--cell is--title">
                                {{Question / Group}}
                            </th>
                            <th class="osatstats-table--cell is--total">
                                {{Your result}}
                            </th>
                            <th class="osatstats-table--cell is--average">
                                {{Average result}}
                            </th>
                        </tr>
                    </thead>

                    <?php $c = 0; foreach($grouplist as $gid => $group):
                            $questions = $assessment->getGroupQuestions($gid); ?><<?php echo $gid=='survey' ? 'tfoot' : 'tbody'; ?> class="has--<?php echo empty($questions) ? 'no-' : ''; ?>questions">
                        <tr class="osatstats-table--group">
                            <td class="osatstats-table--cell is--label"><?php echo htmlspecialchars($group['label']); ?></td>
                            <td class="osatstats-table--cell is--name"><?php echo $gid=='survey' ? '{{Total}}' : htmlspecialchars($group['name']); ?></td>
                            <td class="osatstats-table--cell is--total">
                                <button
                                    data-gid="<?php echo $gid; ?>"
                                    style="height:<?php echo number_format($group['total'], 2, '.', ''); ?>%"
                                    data-balloon="{{You reached %s|<?php echo ceil($group['total']) . '%'; ?>}}"
                                    data-balloon-pos="<?php echo $c < count($grouplist)/2 ? 'top' : 'top'; ?>">
                                    <span class="osatstats-table--cell--percentage" aria-labelledby="g<?php echo $gid; ?>-total"><?php echo floatval(number_format($group['total'], 2)); ?>%</span>
                                    <span id="g<?php echo $gid; ?>-total" class="osatstats-table--cell--score" data-value="<?php echo $group['total_score']; ?>" data-max="<?php echo $group['max']; ?>">
                                        {{You reached %s|<?php echo ceil($group['total']) . '%'; ?>}}
                                    </span>
                                </button>
                            </td>
                            <td class="osatstats-table--cell is--average" style="height:<?php echo number_format($group['average'], 2, '.', ''); ?>%" data-balloon="{{Average value is %s|<?php echo number_format($group['average'], 2, '.', '') . '%' ?>}}">
                                <span class="osatstats-table--cell--percentage" aria-labelledby="g<?php echo $gid; ?>-average"><?php echo floatval(number_format($group['average'], 2)); ?>%</span>
                                <span id="g<?php echo $gid; ?>-average" class="osatstats-table--cell--score" data-value="<?php echo floatval(number_format($group['average_score'], 2)); ?>" data-max="<?php echo $group['max']; ?>">
                                    {{Average value is %s|<?php echo number_format($group['average'], 2, '.', '') . '%' ?>}}
                                </span>
                            </td>
                        </tr>

                        <?php if($questions = $assessment->getGroupQuestions($gid)): foreach($questions as $qid => $question):
                            $sQuestion = Question::model()->findByAttributes(array('gid' => $gid, 'qid' => $qid));

                        ?><tr class="osatstats-table--question" data-qid="<?php echo $qid; ?>">
                            <td></td>
                            <td class="osatstats-table--cell is--name"><?php echo htmlspecialchars($sQuestion->question); ?></td>
                            <td class="osatstats-table--cell is--total" style="height:<?php echo number_format($assessment->getQuestionTotal($qid), 2, '.', ''); ?>%" data-balloon="{{You reached %s|<?php echo ceil($question['total']) . '%'; ?>}}">
                                <span class="osatstats-table--cell--percentage" aria-labelledby="q<?php echo $qid; ?>-total"><?php echo floatval(number_format($assessment->getQuestionTotal($qid), 2)); ?>%</span>
                                <span id="q<?php echo $qid; ?>-total" class="osatstats-table--cell--score" data-value="<?php echo $question['total']; ?>" data-max="<?php echo $question['max']; ?>">
                                    {{You reached %s|<?php echo ceil($question['total']) . '%'; ?>}}
                                </span>
                            </td>
                            <td class="osatstats-table--cell is--average" style="height:<?php echo number_format($assessment->getQuestionAverage($qid), 2, '.', ''); ?>%" data-balloon="{{Average value is %s|<?php echo number_format($question['average'], 2, '.', '') . '%' ?>}}">
                                <span class="osatstats-table--cell--percentage" aria-labelledby="q<?php echo $qid; ?>-average"><?php echo floatval(number_format($assessment->getQuestionAverage($qid), 2)); ?>%</span>
                                <span id="q<?php echo $qid; ?>-average" class="osatstats-table--cell--score" data-value="<?php echo $question['average']; ?>" data-max="<?php echo $question['max']; ?>">
                                    {{Average value is %s|<?php echo number_format($question['average'], 2, '.', '') . '%' ?>}}
                                </span>
                            </td>
                        </tr><?php endforeach; endif; ?>
                    </<?php echo $gid=='survey' ? 'tfoot' : 'tbody'; ?>><?php $c++; endforeach; ?>
                </table>
            </div>
        </div>

        <div id="osatstats-texts" class="osatstats-text center">
            <div class="center-min">

                <div class="question-index">
                    <nav id="index-menu" class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Summary index&nbsp;<span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <?php $c = 0; foreach($grouplist as $gid => $group): ?><li class="group-<?php echo $gid; ?><?php echo empty($c) ? ' is--active': '';?>" data-gid="<?php echo $gid; ?>">
                                <a href="#summary-<?php echo $gid; ?>" class="linkToGid">
                                    <span class="count"><?php echo $group['label']; ?></span> <span class="name"><?php echo $group['name']; ?></span>
                                </a>
                            </li><?php $c++; endforeach; ?>
                        </ul>
                    </nav>
                </div>


                <div class="osatstats-text--assessments">
                    <?php $c = 0; foreach($grouplist as $gid => $group): ?><div id="summary-<?php echo $gid; ?>" class="osatstats-text--assessment<?php echo empty($c) ? ' is--active': '';?>" data-gid="<?php echo $gid; ?>">
                        <p class="osatstats-text--assessment--label"><?php echo $group['label']; ?></p>
                        <h3 class="osatstats-text--assessment--groupname"><?php echo $group['long_title']; ?></h3>
                        <?php if(!empty($group['summary'])): ?><div class="osatstats-text--assessment--summary">
                            <?php echo $group['summary']; ?>
                        </div><?php endif; ?>

                        <?php if(!empty($group['assessment'])): ?><div class="osatstats-text--assessment--assessment">
                            <?php if(!empty($group['assessment']['name'])): ?><h4><?php echo htmlspecialchars($group['assessment']['name']); ?></h4><?php endif; ?>
                            <?php if(!empty($group['assessment']['message'])): ?><?php echo $group['assessment']['message']; ?><?php endif; ?>
                        </div><?php endif; ?>
                    </div><?php $c++; endforeach; ?>
                    <span class="copyprotect"></span>
                </div>
            </div>
        </div>

    </div><?php endif; ?>
</div>
