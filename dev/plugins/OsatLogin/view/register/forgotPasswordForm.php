<!-- The Login form //-->
<div class="register" id="register-forgot-password">
    <div class="inner">
        <h2 class="register--title">{{Forgot password}}</h2>
        <p class="register--text">
            {{If you have forgotten your password you can reset it with this form.}}
        </p>

        <?php echo CHtml::form($urlAction,'post',array('id'=>'limesurvey', 'role' => 'form')); ?>
            <input type="hidden" name="lang" value="<?php echo $sLanguage; ?>" id="register_lang" />
            <input type="hidden" name="function" value="forgot-password" />

            <div class='form-group'>
                <label for="register_email">{{Email address}}</label>
                <input required type="email" name="register_email" value="<?php echo $register_email; ?>" class="form-control" placeholder="{{Email address}}" />
            </div>

            <div class='form-group'>
                <button type="submit" id="register" value="forgot-password" name="register" accesskey="n" class="submit btn btn-lg btn-primary">{{Reset password}}</button>
            </div>
        <?php echo CHtml::endForm(); ?>
    </div>
</div>
