<div class="panel panel-primary" id="pannel-1">
    <div class="panel-heading">
        <h4 class="panel-title"><?php eT("User attributes"); ?></h4>
    </div>
    <div class="panel-body">
        <?php
            $attributes = ParticipantAttributeName::model()->getVisibleAttributes();
            $allAttributes = ParticipantAttributeName::model()->getAllAttributes();
            $attributeValues = ParticipantAttributeName::model()->getAllAttributesValues();
            $visibleAttributes = ParticipantAttributeName::model()->getVisibleAttributes();

            foreach($attributes as $attribute_id => $attribute)
            {
                if((bool) $attribute['visible'])
                {
                    $el_id = 'user_attribute_' . strval($attribute_id);
                    $attribute_options = ParticipantAttributeName::model()->getAttributesValues($attribute_id);

                    if(!empty($attribute_options))
                    {
                        $selected = isset($_POST['user_attribute'][strval($attribute_id)]) ? (array) $_POST['user_attribute'][strval($attribute_id)] : array();

                    ?>

        <div class='form-group'>
            <label for="<?php echo $el_id; ?>" class="col-sm-4 control-label"><?php echo $attribute['attribute_name']; ?></label>
            <div class='col-sm-4'>
                <select name="user_attribute[<?php echo strval($attribute_id); ?>][]" id="<?php echo $el_id; ?>" class="form-control" multiple="multiple" size="5">
                    <?php foreach($attribute_options as $option): ?>

                        <option value="<?php echo $option['value_id']; ?>"<?php echo in_array($option['value_id'], $selected) ? ' selected="selected"' : ''; ?>><?php echo gT($option['value']); ?></option>
                    <?php endforeach; ?>

                </select>
            </div>
        </div>

                <?php
                    }
                }
            }

        ?>
<?php /*

        <div class='form-group'>
            <label for='completionstate' class="col-sm-4 control-label"><?php eT("Include:"); ?> </label>
            <?php $this->widget('yiiwheels.widgets.buttongroup.WhButtonGroup', array(
                'name' => 'completionstate',
                'value'=> 'all' ,
                'selectOptions'=>array(
                    "all"=>gT("All responses",'unescaped'),
                    "complete"=>gT("Complete only",'unescaped'),
                    "incomplete"=>gT("Incomplete only",'unescaped'),
                )
            ));?>
        </div>

        <div class='form-group'>
            <label class="col-sm-4 control-label" for='viewsummaryall'><?php eT("View summary of all available fields:"); ?></label>
            <div class='col-sm-1'>
                <?php $this->widget('yiiwheels.widgets.switch.WhSwitch', array('name' => 'viewsummaryall', 'id'=>'viewsummaryall', 'value'=>(isset($_POST['viewsummaryall'])), 'onLabel'=>gT('On'),'offLabel'=>gT('Off')));?>
            </div>
        </div>

        <div class='form-group'>
            <label class="col-sm-4 control-label" id='noncompletedlbl' for='noncompleted' title='<?php eT("Count stats for each question based only on the total number of responses for which the question was displayed"); ?>'><?php eT("Subtotals based on displayed questions:"); ?></label>
            <div class='col-sm-1'>
                <?php $this->widget('yiiwheels.widgets.switch.WhSwitch', array('name' => 'noncompleted', 'id'=>'noncompleted', 'value'=>(isset($_POST['noncompleted'])), 'onLabel'=>gT('On'),'offLabel'=>gT('Off')));?>
            </div>
        </div>

        <?php
        $language_options="";
        foreach ($survlangs as $survlang)
        {
            $language_options .= "\t<option value=\"{$survlang}\"";
            if ( $survlang == $surveyinfo['language'])
            {
                $language_options .= " selected=\"selected\" " ;
            }
            $temp = getLanguageNameFromCode($survlang,true);
            $language_options .= ">".$temp[1]."</option>\n";
        }

        ?>

        <div class='form-group'>
            <label for='statlang' class="col-sm-4 control-label" ><?php eT("Statistics report language:"); ?></label>
            <div class='col-sm-4'>
                <select name="statlang" id="statlang" class="form-control"><?php echo $language_options; ?></select>
            </div>
        </div>
*/ ?>
    </div>
</div>
