<!-- The Login form //-->
<div class="register" id="register-<?php echo $function; ?>">
    <div class="inner">
        <h2 class="register--title">{{Please confirm your personal data!}}</h2>

        <div class="register--text optional-attributes-text simple--text">
            {OPTIONAL_ATTRIBUTES_TEXT}
        </div>

        <?php if(!empty($errors)): ?><div class="alert alert-danger text-danger register--error" role="alert">
            <?php echo nl2br(join("\n", $errors)); ?>
        </div><?php endif; ?>

        <?php echo CHtml::form($urlAction,'post',array('id'=>'osatregister', 'role' => 'form')); ?>

            <input type="hidden" name="lang" value="<?php echo $sLanguage; ?>" id="register_lang" />
            <input type="hidden" name="function" value="extraattributes" />

            <input aria-label="{{Email address}}" required type="hidden" name="register_email" value="<?php echo $register_email; ?>" class="form-control" placeholder="{{Email address}}" />

            <div class="form-group-wrapper">
                <p class="form-group--caption">
                    {{First name}}*
                </p>
                <div class='form-group'>
                    <input aria-label="{{First name}}" required type="text" name="register_firstname" value="<?php echo $register_firstname; ?>" class="form-control" placeholder="{{First name}}" />
                </div>
            </div>

            <div class="form-group-wrapper">
                <p class="form-group--caption">
                    {{Last name}}*
                </p>
                <div class='form-group'>
                    <input aria-label="{{Last name}}" required type="text" name="register_lastname" value="<?php echo $register_lastname; ?>" class="form-control" placeholder="{{Last name}}" />
                </div>
            </div>

            <!-- div class='form-group'>
                <input aria-label="{{Password}}" type="password" name="register_password" class="form-control" placeholder="{{Password}}" />
            </div>

            <div class='form-group'>
                <input aria-label="{{Confirm Password}}" type="password" name="register_password_confirm" class="form-control" placeholder="{{Confirm Password}}" />
            </div //-->

            <?php include(dirname(__FILE__) . '/_attributes.php'); ?>

            <p>
                * {{are obligatory fields}}
            </p>

            <div class="form-group submit">
                <p class="register--hint">
                    <a href="<?php echo $backUrl; ?>">{{Cancel}}</a>
                </p>
                <button type="submit" id="register" value="login" name="register" accesskey="n" class="submit btn btn-lg btn-primary">{{<?php echo !empty($optional_attributes) ? 'Send' : 'Forward' ; ?>}}</button>
            </div>
        <?php echo CHtml::endForm(); ?>
    </div>
</div>
