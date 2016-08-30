<?php

if(!empty($attributes) && is_array($attributes)):
    foreach($attributes as $label => $options):
        $n = 'register_' . $label;
        $value = isset($$n) ? $$n : (isset($options['value']) ? $options['value'] : '');
        unset($n);

        $type = 'text';

        if($options['attribute_type'] == 'DD')
        {
            $type = count($options['options']) > 3 ? 'select' : 'checkbox';
        }
?>

            <?php if($options['label'] == 'personaldataconfirmed'): ?>
            <input aria-label="{{<?php echo $options['caption']; ?>}}" type="hidden" name="register_<?php echo $label; ?>" value="1" />

            <?php else: ?><div class="form-group-wrapper">
                <?php if(!empty($options['caption'])): ?><p class="form-group--caption">
                    <?php echo htmlspecialchars($options['caption']); ?>

                </p><?php endif; ?>

                <div class="form-group is-<?php echo $type; ?>">
            <?php if($options['attribute_type'] == 'DD'): // a dropdown element ?>
                <?php if(!empty($options['options']) && is_array($options['options'])) : ?>
                    <?php if($type == 'checkbox'): // just a view options? display a radio list ?>
                        <?php foreach($options['options'] as $i => $opt): ?>

                    <div class="checkbox">
                        <input type="radio" required id="<?php echo $label . '_' . $i; ?>" value="<?php echo htmlspecialchars($opt); ?>" name="register_<?php echo $label; ?>"<?php if($value == $opt): ?> checked="checked"<?php endif; ?> />
                        <label for="<?php echo $label . '_' . $i; ?>">
                            {{<?php echo $opt; ?>}}
                        </label>
                    </div>
                        <?php endforeach; ?>
                    <?php else: // otherwise display a select box ?>

                    <label for="<?php echo $label; ?>" class="control-label" data-title="{{Choose %s|<?php echo $options['description']; ?>}}"></label>

                    <div>
                        <select id="<?php echo $label; ?>" aria-label="{{<?php echo $options['description']; ?>}}" required name="register_<?php echo $label; ?>" class="form-control<?php echo $label == 'attribute_4' ? ' sort-options' : ''; ?>">
                            <option value="">{{Choose %s|<?php echo $options['description']; ?>}}</option>
                        <?php foreach($options['options'] as $opt): ?>

                            <option<?php if($value == $opt): ?> selected="selected"<?php endif; ?>>{{<?php echo $opt; ?>}}</option>
                        <?php endforeach; ?>

                        </select>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else: ?>

                    <input aria-label="{{<?php echo !empty($options['caption']) ? $options['caption'] : $options['description']; ?>}}" required type="text" name="register_<?php echo $label; ?>" value="<?php echo htmlspecialchars($value); ?>"<?php echo !empty($options['placeholder']) ? ' placeholder="' . htmlspecialchars($options['placeholder']) . '"' : ''; ?> class="form-control" />
            <?php endif; ?>
                </div>
            </div><?php endif; ?>
    <?php endforeach;
endif; ?>
