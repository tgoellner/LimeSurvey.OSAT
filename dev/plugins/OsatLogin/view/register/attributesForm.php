<!-- The Login form //-->
<div class="register" id="register-<?php echo $function; ?>">
    <div class="inner">
        <h2 class="register--title">{{<?php echo !empty($optional_attributes) ? 'Save your results' : 'Welcome' ; ?>}}</h2>

        <?php if(!empty($optional_attributes)): ?><div class="register--text optional-attributes-text simple--text">
            {OPTIONAL_ATTRIBUTES_TEXT}
        </div><?php endif; ?>

        <?php if(!empty($errors)): ?><div class="alert alert-danger text-danger register--error" role="alert">
            <?php echo nl2br(join("\n", $errors)); ?>
        </div><?php endif; ?>

        <?php echo CHtml::form($urlAction,'post',array('id'=>'osatregister')); ?>

            <input type="hidden" name="lang" value="<?php echo $sLanguage; ?>" id="register_lang" />
            <input type="hidden" name="function" value="attributes" />

            <?php include(dirname(__FILE__) . '/_attributes.php'); ?>

            <?php if(empty($optional_attributes)): ?><div class="register--text required-attributes-text simple--text">
                {REQUIRED_ATTRIBUTES_TEXT}
            </div><?php else: ?><p>
                * {{are obligatory fields}}
            </p><?php endif; ?>

            <div class="form-group submit">
                <?php if(!empty($optional_attributes)): ?><p class="register--hint">
                    <a href="javascript:window.history.back()">{{Cancel}}</a>
                </p><?php endif; ?>
                <button type="submit" id="register" value="login" name="register" accesskey="n" class="submit btn btn-lg btn-primary">{{<?php echo !empty($optional_attributes) ? 'Send' : 'Forward' ; ?>}}</button>
            </div>
        <?php echo CHtml::endForm(); ?>
    </div>
</div>
