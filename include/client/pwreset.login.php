<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$userid=Format::input($_POST['userid']);
?>
<h1><?php echo __('Forgot My Password'); ?></h1>
<p><?php echo __(
'Enter your username or email address again in the form below and press the <strong>Login</strong> to access your account and reset your password.');
?>
<br><br>    
<form action="pwreset.php" method="post" id="clientLogin" class="form-horizontal registration">
    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="reset"/>
    <input type="hidden" name="token" value="<?php echo Format::htmlchars($_REQUEST['token']); ?>"/>
    <strong><?php echo Format::htmlchars($banner); ?></strong>
    <br><br>
    <div class="form-group">
        <label class="control-label col-sm-2" for="username"><?php echo __('Username'); ?>:</label>
        <div class="col-sm-10"><input class="form-control" id="username" type="text" name="userid" size="30" value="<?php echo $userid; ?>">
        </div>
    </div>
    <p>
        <input class="btn btn-success pull-right" class="btn" type="submit" value="Login">
    </p>

</form>
