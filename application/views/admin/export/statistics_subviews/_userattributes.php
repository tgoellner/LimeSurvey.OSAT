<?php
    $surveyId = Yii::app()->request->getParam('sid', Yii::app()->request->getParam('surveyid', 0));
    $_attributes = [];

    if($surveyId)
    {
        $aSurveyInfo = getSurveyInfo($surveyId, App()->language);
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
                $tOptions['caption'] = !empty($aSurveyInfo['attributecaptions'][$tLabel]) ? $aSurveyInfo['attributecaptions'][$tLabel] : $tOptions['description'];

                unset($aSurveyInfo);

                $_attributes[$tLabel] = $tOptions;
                unset($tOptions, $tLabel);
            }
        }
    }

    if(!empty($_attributes)): ?><div class="panel panel-primary" id="pannel-1">
    <div class="panel-heading">
        <h4 class="panel-title"><?php eT("User attributes"); ?></h4>
    </div>
    <div class="panel-body">
        <?php
            foreach($_attributes as $attribute_id => $attribute)
            {
                if((bool) $attribute['visible'] && !empty($attribute['options']))
                {
                    $el_id = 'user_' . $attribute_id;

                    $selected = isset($_POST['user_attributes'][$attribute_id]) ? (array) $_POST['user_attributes'][$attribute_id] : array();
                    ?>

        <div class='form-group'>
            <label for="<?php echo $el_id; ?>" class="col-sm-4 control-label"><?php echo $attribute['caption']; ?></label>
            <div class='col-sm-4'>
                <select name="user_attributes[<?php echo $attribute_id; ?>][]" id="<?php echo $el_id; ?>" class="form-control" multiple="multiple" size="5">
                    <?php foreach($attribute['options'] as $option): ?>

                        <option value="<?php echo $option; ?>"<?php echo in_array($option, $selected) ? ' selected="selected"' : ''; ?>><?php echo gT($option); ?></option>
                    <?php endforeach; ?>

                </select>
            </div>
        </div>
                <?php
                }
            }
        ?>
    </div>
</div><?php endif; ?>
