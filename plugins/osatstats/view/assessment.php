<div class="osatstats">
    <?php if(is_object($assessment) && $groups = $assessment->getGroups()): ?><div class="osatstats--assessment">
        <?php
            $grouplist = [];
            $sess = !empty($_SESSION['survey_'.$assessment->get('surveyId')]['grouplist']) ? $_SESSION['survey_'.$assessment->get('surveyId')]['grouplist'] : [];

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
                    'assessment' => $assessment->getGroupAssessment($gid)
                ];

                // and get the name!
                if(!empty($sess[$gid]['group_name']))
                {
                    $grouplist[$gid]['name'] = $sess[$gid]['group_name'];
                }
                elseif($sGroup = QuestionGroup::model()->findByAttributes(array('sid' => $assessment->get('surveyId'), 'gid' => $gid, 'language' => $assessment->get('sLanguage'))))
                {
                    $grouplist[$gid]['name'] = $sGroup->group_name;
                }
                else
                {
                    $grouplist[$gid]['name'] = $grouplist[$gid]['label'];
                }
                unset($sGroup);

                $count++;
            }
            unset($sess);

            // and finally our survey stats
            $grouplist['survey'] = array(
                'name' => '{{Survey total}}',
                'label' => str_pad($count, 2, '0', STR_PAD_LEFT),
                'total' => $assessment->getTotal(),
                'total_score' => $assessment->get('total'),
                'average' => $assessment->getAverage(),
                'average_score' => $assessment->get('average'),
                'min' => $assessment->get('min'),
                'max' => $assessment->get('max'),
                'questions' => count($assessment->get('questions')),
                'assessment' => $assessment->getSurveyAssessment()
            );

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
                <?php echo CHtml::form($assessment->getUrl(), 'post', array('id'=>'assessmentfilter', 'role' => 'form')); ?>

                    <div class="form-group">
                        <button type="submit" id="assessmentfilter_overall" value="1" name="osatstats[filter][reset]" class="btn">{{Overall}}</button>
                    </div>

                    <?php foreach($assessment->getAvailableFilter() as $label => $options): ?><div class="form-group is--select">
                        <label for="assessmentfilter_<?php echo $label; ?>" class="control-label" title="{{<?php echo $options['description']; ?>}}">
                            <?php echo $assessment->getActiveFilter($label) ? join(', ', $assessment->getActiveFilter($label)) : '{{' . $options['description'] . '}}' ?>
                        </label>

                        <div>
                            <select id="assessmentfilter_<?php echo $label; ?>" aria-label="{{<?php echo $options['description']; ?>}}" name="osatstats[filter][<?php echo $label; ?>][]" class="form-control">
                                <option value="">{{<?php echo $options['description']; ?>}}</option>
                            <?php foreach($options['options'] as $value => $count): ?>

                                <option<?php if(in_array($value, $assessment->getActiveFilter($label))): ?> selected="selected"<?php endif; ?>>{{<?php echo $value; ?>}}</option>
                            <?php endforeach; ?>

                            </select>
                        </div>
                    </div><?php endforeach; ?>
                <?php echo CHtml::endForm(); ?>

                </form>
            </div>

            <div class="osatstats-table--wrapper">
                <table class="table osatstats-table--table">
                    <colgroup>
                        <col width="5%" />
                        <col />
                        <col width="20%" />
                        <col width="20%" />
                    </colgroup>
                    <caption>
                        {{Your results for this survey}}
                    </caption>

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

                    <?php foreach($grouplist as $gid => $group):
                            $questions = $assessment->getGroupQuestions($gid); ?><<?php echo $gid=='survey' ? 'tfoot' : 'tbody'; ?> data-gid="<?php echo $gid; ?>" class="has--<?php echo empty($questions) ? 'no-' : ''; ?>questions">
                        <tr class="osatstats-table--group">
                            <td class="osatstats-table--cell is--label"><?php echo htmlspecialchars($group['label']); ?></td>
                            <td class="osatstats-table--cell is--name"><?php echo htmlspecialchars($group['name']); ?></td>
                            <td class="osatstats-table--cell is--total" style="height:<?php echo number_format($group['total'], 2, '.', ''); ?>%" data-balloon="{{You reached %1$s of %2$s|<?php echo $group['total_score']; ?>|<?php echo $group['max']; ?>}}">
                                <span class="osatstats-table--cell--percentage" aria-described-by="g<?php echo $gid; ?>-total"><?php echo number_format($group['total'], 2); ?>%</span>
                                <span id="g<?php echo $gid; ?>-total" class="osatstats-table--cell--score" data-value="<?php echo $group['total_score']; ?>" data-max="<?php echo $group['max']; ?>">
                                    {{You reached %1$s of %2$s|<?php echo $group['total_score']; ?>|<?php echo $group['max']; ?>}}
                                </span>
                            </td>
                            <td class="osatstats-table--cell is--average" style="height:<?php echo number_format($group['average'], 2, '.', ''); ?>%">
                                <span class="osatstats-table--cell--percentage" aria-described-by="g<?php echo $gid; ?>-average"><?php echo number_format($group['average'], 2); ?>%</span>
                                <span id="g<?php echo $gid; ?>-average" class="osatstats-table--cell--score" data-value="<?php echo $group['average_score']; ?>" data-max="<?php echo $group['max']; ?>">
                                    {{Average value is %1$s of %2$s|<?php echo $group['total_score']; ?>|<?php echo $group['max']; ?>}}
                                </span>
                            </td>
                        </tr>

                        <?php if($questions = $assessment->getGroupQuestions($gid)): foreach($questions as $qid => $question):
                            $sQuestion = Question::model()->findByAttributes(array('gid' => $gid, 'qid' => $qid));

                        ?><tr class="osatstats-table--question" data-qid="<?php echo $qid; ?>">
                            <td></td>
                            <td class="osatstats-table--cell is--name"><?php echo htmlspecialchars($sQuestion->question); ?></td>
                            <td class="osatstats-table--cell is--total" style="height:<?php echo number_format($assessment->getQuestionTotal($qid), 2, '.', ''); ?>%" data-balloon="{{You reached %1$s of %2$s|<?php echo $question['total']; ?>|<?php echo $question['max']; ?>}}">
                                <span class="osatstats-table--cell--percentage" aria-described-by="q<?php echo $gid; ?>-total"><?php echo number_format($assessment->getQuestionTotal($qid), 2); ?>%</span>
                                <span id="q<?php echo $qid; ?>-total" class="osatstats-table--cell--score" data-value="<?php echo $question['total']; ?>" data-max="<?php echo $question['max']; ?>">
                                    {{You reached %1$s of %2$s|<?php echo $question['total']; ?>|<?php echo $question['max']; ?>}}
                                </span>
                            </td>
                            <td class="osatstats-table--cell is--average" style="height:<?php echo number_format($assessment->getQuestionAverage($qid), 2, '.', ''); ?>%">
                                <span class="osatstats-table--cell--percentage" aria-described-by="q<?php echo $gid; ?>-average"><?php echo number_format($assessment->getQuestionAverage($qid), 2); ?>%</span>
                                <span id="q<?php echo $qid; ?>-average" class="osatstats-table--cell--score" data-value="<?php echo $question['average']; ?>" data-max="<?php echo $question['max']; ?>">
                                    {{Average value is %1$s of %2$s|<?php echo $question['average']; ?>|<?php echo $question['max']; ?>}}
                                </span>
                            </td>
                        </tr><?php endforeach; endif; ?>
                    </<?php echo $gid=='survey' ? 'tfoot' : 'tbody'; ?>><?php endforeach; ?>
                </table>
            </div>
        </div>

        <div id="osatstats-texts" class="osatstats-text center">
            <div class="center-min">
                <?php foreach($grouplist as $gid => $group):
                    if(empty($group['assessment'])) continue;

                ?><div class="osatstats-text--assessment" data-gid="<?php echo $gid; ?>">
                    <?php if(!empty($group['assessment']['name'])): ?><h3 class="h2"><?php echo htmlspecialchars($group['assessment']['name']); ?></h2><?php endif; ?>
                    <?php if(!empty($group['assessment']['message'])): ?><?php echo nl2br(htmlspecialchars($group['assessment']['message'])); ?><?php endif; ?>
                </div><?php endforeach; ?>
            </div>
        </div>

    </div><?php endif; ?>
</div>
