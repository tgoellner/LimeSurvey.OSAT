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
    </div>
</div>
