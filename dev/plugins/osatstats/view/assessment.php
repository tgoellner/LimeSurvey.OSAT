<div class="osatstats">
    <?php if(is_object($assessment) && $groups = $assessment->getGroups()): ?><div class="osatstats--assessment">
        <?php
            $chartist = [
                'labels' => [],
                'series' => [
                    [],
                    []
                ]
            ];
            $chartistlabels = [];

            $count = 1;
            foreach($groups as $gid => $group)
            {
                $chartist['labels'][] = str_pad($count, 2, '0', STR_PAD_LEFT);
                $chartistlabels[$gid] = str_pad($count, 2, '0', STR_PAD_LEFT);
                $chartist['series'][0][] = $assessment->getGroupTotal($gid);
                $chartist['series'][1][] = $assessment->getGroupAverage($gid);

                $count++;
            }

            $chartist['labels'][] = str_pad($count, 2, '0', STR_PAD_LEFT);
            $chartist['series'][0][] = $assessment->getTotal() / count($groups);
            $chartist['series'][1][] = $assessment->getAverage() / count($groups);
        ?>
        <div id="osatstats-chart" class="osatstats--chartwapper has--averages" aria-described-by="osatstats-table">
            <div class="osatstats--chart is--chartist" data-json="<?php echo base64_encode(json_encode($chartist)); ?>"></div>
        </div>

        <div id="osatstats-table" class="osatstats--tablewrapper center">
            <table class="table osatstats--table center-min">
                <colgroup>
                    <col />
                    <col width="10%" />
                    <col width="10%" />
                    <col width="10%" />
                    <col width="10%" />
                </colgroup>
                <caption>
                    {{Your results for this survey}}
                </caption>

                <thead>
                    <tr>
                        <th class="osatstats--table--headercell is--title">
                            {{Question / Group}}
                        </th>
                        <th class="osatstats--table--headercell is--total">
                            {{Your score}}
                        </th>
                        <th class="osatstats--table--headercell is--min">
                            {{Minimum score}}
                        </th>
                        <th class="osatstats--table--headercell is--max">
                            {{Maximum score}}
                        </th>
                        <th class="osatstats--table--headercell is--average">
                            {{Average score}}
                        </th>
                    </tr>
                </thead>

                <?php foreach($groups as $gid => $group):
                    $sGroup = QuestionGroup::model()->findByAttributes(array('sid' => $assessment->get('surveyId'), 'gid' => $gid, 'language' => $assessment->get('sLanguage')));

                ?><tbody data-gid="<?php echo $gid; ?>">
                    <tr class="osatstats--table--group">
                        <td class="osatstats--table--cell is--title"><?php echo htmlspecialchars($sGroup->group_name); ?></td>
                        <td class="osatstats--table--cell is--total"><?php echo $group['total']; ?></td>
                        <td class="osatstats--table--cell is--min"><?php echo $group['min']; ?></td>
                        <td class="osatstats--table--cell is--max"><?php echo $group['max']; ?></td>
                        <td class="osatstats--table--cell is--average"><?php echo $group['average']; ?></td>
                    </tr>

                    <?php /* foreach($assessment->getGroupQuestions($gid) as $qid => $question):
                        $sQuestion = Question::model()->findByAttributes(array('gid' => $gid, 'qid' => $qid));

                    ?><tr class="osatstats--table--question" data-qid="<?php echo $qid; ?>">
                        <td class="osatstats--table--cell is--title"><?php echo $sQuestion->question; ?></td>
                        <td class="osatstats--table--cell is--total"><?php echo $question['total']; ?></td>
                        <td class="osatstats--table--cell is--min"><?php echo $question['min']; ?></td>
                        <td class="osatstats--table--cell is--max"><?php echo $question['max']; ?></td>
                        <td class="osatstats--table--cell is--average"><?php echo $question['average']; ?></td>
                    </tr><?php endforeach; */ ?>

                </tbody><?php endforeach; ?>

                <tfoot>
                    <tr class="osatstats--table--group">
                        <td class="osatstats--table--cell is--title">
                            {{Total}}
                        </td>
                        <td class="osatstats--table--cell is--total"><?php echo $assessment->getTotal(); ?></td>
                        <td class="osatstats--table--cell is--min"><?php echo $assessment->getMin(); ?></td>
                        <td class="osatstats--table--cell is--max"><?php echo $assessment->getMax(); ?></td>
                        <td class="osatstats--table--cell is--average"><?php echo number_format($assessment->getAverage(), 1); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div id="osatstats-texts" class="osatstats--textxwrapper center">
            <div class="center-min">
                <?php if($text = $assessment->getSurveyAssessment()): ?><div class="osatstats--surveytext">
                    <?php if(!empty($text['name'])): ?><h3 class="h2"><?php echo htmlspecialchars($text['name']); ?></h2><?php endif; ?>
                    <?php if(!empty($text['message'])): ?><p><?php echo nl2br(htmlspecialchars($text['message'])); ?></p><?php endif; ?>
                </div><?php endif; ?>

                <?php foreach($groups as $gid => $group): ?>

                <?php if($text = $assessment->getGroupAssessment($gid)): ?><div class="osatstats--grouptext" data-chartist-label="<?php echo $chartistlabels[$gid]; ?>">
                    <p class="h2"><?php echo $chartistlabels[$gid]; ?></p>
                    <?php if(!empty($text['name'])): ?><h3 class="h2"><?php echo htmlspecialchars($text['name']); ?></h3><?php endif; ?>
                    <?php if(!empty($text['message'])): ?><p><?php echo nl2br(htmlspecialchars($text['message'])); ?></p><?php endif; ?>
                </div><?php endif; ?>

                <?php endforeach; ?>
            </div>
        </div>

    </div><?php endif; ?>
</div>
