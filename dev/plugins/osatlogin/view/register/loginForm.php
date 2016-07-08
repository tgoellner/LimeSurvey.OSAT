<!-- The Login form //-->
<div class="register" id="register-login">
    <div class="inner">
        <h2 class="register--title">{{Log in}}</h2>
        <p class="register--hint">
            <?php if(!empty($notices)): ?>
                <?php echo nl2br(join("\n", $notices)); ?>
            <?php else: ?>
                {{If you don't have an account yet,<br />you can <a href="%s">sign up here</a>.|<?php echo $url_register; ?>|raw}}
            <?php endif; ?>
        </p>

        <?php if(!empty($errors)): ?><div class="alert alert-danger text-danger register--error" role="alert">
            <?php echo nl2br(join("\n", $errors)); ?>
        </div><?php endif; ?>

        <?php echo CHtml::form($urlAction,'post',array('id'=>'osatregister', 'role' => 'form')); ?>
            <input type="hidden" name="lang" value="<?php echo $sLanguage; ?>" id="register_lang" />
            <input type="hidden" name="function" value="login" />

            <div class='form-group'>
                <input aria-label="{{Email address}}" required type="email" name="register_email" value="<?php echo $register_email; ?>" class="form-control" placeholder="{{Email address}}" />
            </div>

            <div class='form-group'>
                <input aria-label="{{Password}}" required type="password" name="register_password" class="form-control" placeholder="{{Password}}" />
            </div>

            <p class="register--hint">
                <a href="<?php echo $url_forgot_password; ?>">{{Forgot your password?}}</a>
            </p>
            <div class='form-group'>
                <button type="submit" id="register" value="login" name="register" accesskey="n" class="submit btn btn-lg btn-primary">{{Log in}}</button>
            </div>
        <?php echo CHtml::endForm(); ?>
    </div>
</div>
