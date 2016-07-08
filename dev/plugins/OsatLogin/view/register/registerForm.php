<!-- The Register form //-->
<div class="register" id="register-register">
    <div class="inner">
        <h2 class="register--title">{{Create your account}}</h2>
        <p class="register--title--extra">
            {{Already have an account? <a href="%s">Log in here</a>.|<?php echo $url_login; ?>|raw}}
        </p>

        <?php if(!empty($errors)): ?><div class="alert alert-danger text-danger register--error" role="alert">
            <?php echo nl2br(join("\n", $errors)); ?>
        </div><?php endif; ?>

        <?php echo CHtml::form($urlAction,'post',array('id'=>'osatregister', 'role' => 'form')); ?>
            <input type="hidden" name="lang" value="<?php echo $sLanguage; ?>" id="register_lang" />
            <input type="hidden" name="function" value="register" />

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
                <input aria-label="{{Password}}" required type="password" name="register_password" class="form-control" placeholder="{{Password}}" />
            </div>

            <div class='form-group'>
                <input aria-label="{{Confirm Password}}" required type="password" name="register_password_confirm" class="form-control" placeholder="{{Confirm Password}}" />
            </div>

            <p class="register--hint">
                * {{are obligatory fields}}
            </p>

            <?php if(!empty($require_terms_of_service)): ?><div class="checkbox register--terms-of-service">
                <input type="checkbox" required id="register_termsaccepted" name="register_termsaccepted" value="1" aria-describedby="register_termsaccepted_info"<?php echo (bool) $register_termsaccepted ? ' checked="checked"' : ''; ?> />
                <label for="register_termsaccepted"></label>
                <span id="register_termsaccepted_info">
                    {{By clicking here you accept the <span data-toggle="modal" data-target="#terms-of-service">Terms of use</span> and the <span data-toggle="modal" data-target="#privacy-policy">Privacy Policy</span>|raw}}
                </span>
            </div><?php endif; ?>

            <div class='form-group'>
                <button type="submit" id="register" value="register" name="register" accesskey="n" class="btn btn-lg btn-primary">{{Register}}</button>
            </div>
        <?php echo CHtml::endForm(); ?>
    </div>
</div>

<!-- modals //-->
<div class="modal fade" id="terms-of-service" tabindex="-1" role="dialog" aria-labelledby="terms-of-service_label">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="terms-of-service_label">{{Terms of service}}</h4>
            </div>
            <div class="modal-body">
                {TERMS_OF_SERVICE}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{Close}}</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="privacy-policy" tabindex="-1" role="dialog" aria-labelledby="privacy-policy_label">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="privacy-policy_label">{{Privacy Policy}}</h4>
            </div>
            <div class="modal-body">
                {PRIVACY_POLICY}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{Close}}</button>
            </div>
        </div>
    </div>
</div>
