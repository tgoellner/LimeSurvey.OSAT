<!-- The Register form //-->
<div class="osat-feedback" id="feedback-form">
    <div class="inner">
<?php if(!empty($email_sent)): ?>

        <h2 class="osat-feedback--title alert alert-success text-success feedback--success" role="alert">
            {{Thank you for your feedback, we appreciate it!}}
        </h2>
<?php else: ?>

        <h2 class="osat-feedback--title">{{Your feedback is important to us}}</h2>

        <?php if(!empty($errors)): ?><div class="alert alert-danger text-danger feedback--error" role="alert">
            <?php echo nl2br(join("\n", $errors)); ?>
        </div><?php endif; ?>

        <?php echo CHtml::form($urlAction . '#feedback-form','post',array('id'=>'osat-feedback-form', 'class' => 'osat-feedback--form', 'role' => 'form')); ?>
            <input type="hidden" name="lang" value="<?php echo $sLanguage; ?>" />
            <input type="hidden" name="osatfeedback[submitted]" value="1" />

            <?php foreach($fields as $label => $options): ?><div class="form-group-wrapper osat-feedback--field is--<?php echo $label; ?>">
                <?php if(!empty($options['label'])): ?><p class="form-group--caption">
                    {{<?php echo $options['label']; ?>}}
                </p><?php endif; ?>

                <div class='form-group'>
                    <?php $options['multiple'] = !empty($options['multiple']); ?>
                    <?php $options['name'] = 'osatfeedback[' . $label . ']' . ($options['multiple'] ? '[]' : ''); ?>
                    <?php if(isset($options['options'])): ?>
                        <?php if($options['type'] == 'checkbox' || $options['type'] == 'radio'): ?>
                            <?php foreach($options['options'] as $i => $v): ?><div class="<?php echo $options['type']; ?> inline">
                        <input type="<?php echo $options['multiple'] ? 'checkbox' : 'radio'; ?>"<?php echo !empty($options['required']) ? ' required' : ''; ?> id="osat-feedback--<?php echo $label . '-' . $i; ?>" name="<?php echo $options['name']; ?>" value="<?php echo addslashes($v); ?>"<?php echo !empty($options['value']) && in_array($v, $options['value']) ? ' checked="checked"' : ''; ?> />
                        <label for="osat-feedback--<?php echo $label . '-' . $i; ?>">
                            <?php echo htmlspecialchars($v); ?>
                        </label>
                    </div><?php endforeach; ?>
                        <?php else: ?>
                    <select<?php echo !empty($options['required']) ? ' required' : ''; ?> name="<?php echo $options['name']; ?>" size="1" class="form-control">
                            <?php foreach($options['options'] as $i => $v): ?>
                        <option<?php echo in_array($v, $options['value']) ? ' selected="selected"' : ''; ?> />
                            {{<?php echo $v; ?>}}
                        </option>
                            <?php endforeach; ?>
                    </select>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if($options['type'] == 'textarea'): ?>
                    <textarea<?php echo !empty($options['required']) ? ' required' : ''; ?>
                        aria-label="{{<?php echo $options['label']; ?>}}"
                        name="<?php echo $options['name']; ?>"
                        rows="<?php echo !empty($options['rows']) ? $options['rows'] : 10; ?>"
                        cols="<?php echo !empty($options['cols']) ? $options['cols'] : 5; ?>"
                        class="form-control"
                        <?php echo !empty($options['placeholder']) ? 'placeholder="{{' . $options['placeholder'] . '}}"' : ''; ?>
                    ><?php echo addslashes($options['value']); ?></textarea>
                        <?php else: ?>
                    <input<?php echo $options['required'] ? ' required' : ''; ?>
                        aria-label="{{<?php echo $options['label']; ?>}}"
                        type="<?php echo $options['type']; ?>"
                        name="<?php echo $options['name']; ?>"
                        value="<?php echo addslashes($options['value']); ?>"
                        class="form-control"
                        <?php echo !empty($options['placeholder']) ? 'placeholder="{{' . $options['placeholder'] . '}}"' : ''; ?> />
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div><?php endforeach; ?>

            <div class='form-group'>
                <button type="submit" id="osat-feedbackform-submit" value="1" name="osatfeedback[submit]" class="btn btn-lg btn-primary">{{Save my feedback}}</button>
            </div>
        <?php echo CHtml::endForm(); ?>
<?php endif; ?>

    </div>
</div>
