<!-- The Login form //-->
<div class="register" id="register-<?php echo $function; ?>">
    <div class="inner">
        <h2 class="register--title">{{Save your results}}</h2>

        <div class="register--text optional-attributes-text simple--text">
            {OPTIONAL_ATTRIBUTES_TEXT}
        </div>

        <?php if(!empty($errors)): ?><div class="alert alert-danger text-danger register--error" role="alert">
            <?php echo nl2br(join("\n", $errors)); ?>
        </div><?php endif; ?>

        <?php echo CHtml::form($urlAction,'post',array('id'=>'limesurvey', 'role' => 'form')); ?>

            <input type="hidden" name="lang" value="<?php echo $sLanguage; ?>" id="register_lang" />
            <input type="hidden" name="function" value="extraattributes" />

            <div class='form-group'>
                <input aria-label="{{Email address}}" required type="email" name="register_email" value="<?php echo $register_email; ?>" class="form-control" placeholder="{{Email address}}" />
            </div>

            <div class='form-group'>
                <input aria-label="{{First name}}" required type="text" name="register_firstname" value="<?php echo $register_firstname; ?>" class="form-control" placeholder="{{First name}}" />
            </div>

            <div class='form-group'>
                <input aria-label="{{Last name}}" required type="text" name="register_lastname" value="<?php echo $register_lastname; ?>" class="form-control" placeholder="{{Last name}}" />
            </div>

            <div class='form-group'>
                <input aria-label="{{Password}}" type="password" name="register_password" class="form-control" placeholder="{{Password}}" />
            </div>

            <div class='form-group'>
                <input aria-label="{{Confirm Password}}" type="password" name="register_password_confirm" class="form-control" placeholder="{{Confirm Password}}" />
            </div>

            <?php foreach($missing_attributes as $label => $options): ?>
                <?php $n = 'register_' . $label; $value = isset($$n) ? $$n : $options['value']; unset($n); ?>
                <?php $type = 'text'; if($options['attribute_type'] == 'DD') { $type = count($options['options']) > 3 ? 'select' : 'checkbox'; } ?>

            <div class="form-group-wrapper">
                <?php if($type != 'text'): ?><p class="form-group--caption">
                    <?php echo htmlspecialchars($options['caption']); ?>

                </p><?php endif; ?>
                <div class="form-group is-<?php echo $type; ?>">
                <?php if($options['attribute_type'] == 'DD'): // a dropdown element ?>
                    <?php if($type == 'checkbox'): // just a view options? display a radio list ?>
                        <?php foreach($options['options'] as $i => $opt): ?>

                    <div class="checkbox">
                        <input type="radio" required id="<?php echo $label . '_' . $i; ?>" value="<?php echo htmlspecialchars($opt); ?>" name="register_<?php echo $label; ?>"<?php if($value == $opt): ?> checked="checked"<?php endif; ?> />
                        <label for="<?php echo $label . '_' . $i; ?>">
                            {{<?php echo $opt; ?>}}
                        </span>
                    </div>
                        <?php endforeach; ?>
                    <?php else: // otherwise display a select box ?>

                    <label for="<?php echo $label; ?>" class="control-label" data-title="{{Choose %s|<?php echo $options['description']; ?>}}"></label>

                    <div>
                        <select id="<?php echo $label; ?>" aria-label="{{<?php echo $options['description']; ?>}}" required name="register_<?php echo $label; ?>" class="form-control">
                            <option value="">{{Choose %s|<?php echo $options['description']; ?>}}</option>
                        <?php foreach($options['options'] as $opt): ?>

                            <option<?php if($value == $opt): ?> selected="selected"<?php endif; ?>>{{<?php echo $opt; ?>}}</option>
                        <?php endforeach; ?>

                        </select>
                    </div>
                    <?php endif; ?>
                <?php else: ?>

                    <input aria-label="{{<?php echo $options['caption']; ?>}}" required type="text" name="register_<?php echo $label; ?>" value="<?php echo htmlspecialchars($value); ?>" class="form-control" placeholder="{{<?php echo $options['caption']; ?>}}" />
                <?php endif; ?>
                </div>
            </div>

            <?php endforeach; ?>

            <p>
                * {{are obligatory fields}}
            </p>

            <div class="form-group submit">
                <p class="register--hint">
                    <a href="javascript:window.history.back()">{{Cancel}}</a>
                </p>
                <button type="submit" id="register" value="login" name="register" accesskey="n" class="submit btn btn-lg btn-primary">{{<?php echo !empty($optional_attributes) ? 'Send' : 'Forward' ; ?>}}</button>
            </div>
        <?php echo CHtml::endForm(); ?>
    </div>
</div>
