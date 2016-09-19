<!-- The Login form //-->
<div class="register" id="register-forgot-password">
    <div class="inner">
        <h2 class="register--title">{{Forgot password}}</h2>
        <p class="register--text">
            <?php if(!empty($notices)): ?>
                <?php echo nl2br(join("\n", $notices)); ?>
            <?php else: ?>
            {{If you have forgotten your password you can reset it with this form.}}
            <?php endif; ?>
        </p>

        <?php if(!empty($errors)): ?><div class="alert alert-danger text-danger register--error" role="alert">
            <?php echo nl2br(join("\n", $errors)); ?>
        </div><?php endif; ?>

        <?php echo CHtml::form($urlAction,'post',array('id'=>'osatregister', 'role' => 'form')); ?>
            <input type="hidden" name="lang" value="<?php echo $sLanguage; ?>" id="register_lang" />
            <input type="hidden" name="function" value="forgot-password" />

            <div class='form-group'>
                <input aria-label="{{Email address}}"  required type="email" name="register_email" value="<?php echo $register_email; ?>" class="form-control" placeholder="{{Email address}}" />
            </div>

            <div class='form-group'>
                <button type="submit" id="register" value="forgot-password" name="register" accesskey="n" class="submit btn btn-lg btn-primary">{{Reset password}}</button>
            </div>
        <?php echo CHtml::endForm(); ?>
    </div>
</div>
