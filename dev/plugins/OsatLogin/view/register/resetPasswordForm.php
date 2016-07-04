<!-- The Register form //-->
<div class="register" id="register-reset-password">
    <div class="inner">
        <h2 class="register--title">{{Reset your password}}</h2>

        <?php echo CHtml::form($urlAction,'post',array('id'=>'limesurvey', 'role' => 'form')); ?>
            <input type="hidden" name="lang" value="<?php echo $sLanguage; ?>" id="register_lang" />
            <input type="hidden" name="function" value="reset-password" />

            <div class='form-group'>
                <label for="register_password">{{Password}}</label>
                <input required type="password" name="register_password" class="form-control" placeholder="{{Password}}" />
            </div>

            <div class='form-group'>
                <label for="register_password_confirm">{{Confirm Password}}</label>
                <input required type="password" name="register_password_confirm" class="form-control" placeholder="{{Confirm Password}}" />
            </div>

            <div class='form-group'>
                <button type="submit" id="register" value="reset-password" name="register" accesskey="n" class="submit btn btn-lg btn-primary">{{Reset password}}</button>
            </div>
        <?php echo CHtml::endForm(); ?>
    </div>
</div>
