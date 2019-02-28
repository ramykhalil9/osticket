<?php
if (!defined('OSTCLIENTINC'))
    die('Access Denied');

$email = Format::input($_POST['luser'] ?: $_GET['e']);
$passwd = Format::input($_POST['lpasswd'] ?: $_GET['t']);

$content = Page::lookupByType('banner-client');

if ($content) {
    list($title, $body) = $ost->replaceTemplateVariables(
            array($content->getLocalName(), $content->getLocalBody()));
} else {
    $title = __('Sign In');
    $body = __('To better serve you, we encourage our clients to register for an account and verify the email address we have on record.');
}
?>
<h1><?php echo Format::display($title); ?></h1>
<p><?php echo Format::display($body); ?></p>
<div class="login-page row login-only">    
    <div id="loginbox" class="col-md-6">                    
                   <div class="well">
                <form action="login.php" method="post" id="clientLogin" class="form-horizontal">
                    <?php csrf_token(); ?>

                    <strong><?php echo Format::htmlchars($errors['login']); ?></strong>
                    <div style="margin-bottom: 25px" class="input-group">
                        <span class="input-group-addon btn-primary"><i class="fas fa-user"></i></span>
                        <input id="username" placeholder="<?php echo __('Email or Username'); ?>" type="text" name="luser" size="30" value="<?php echo $email; ?>" class="form-control">
                    </div>
                    <div style="margin-bottom: 25px" class="input-group">
                        <span class="input-group-addon"><i class="fas fa-lock"></i></span>
                        <input id="passwd" placeholder="<?php echo __('Password'); ?>" type="password" name="lpasswd" size="30" value="<?php echo $passwd; ?>" class="form-control">
                    </div>
                    
                        <input class="btn btn-lg btn-primary btn-block" type="submit" value="<?php echo __('Sign In'); ?>">
                        <?php if ($suggest_pwreset) { ?>
                            <a style="padding-top:4px;display:inline-block;" href="pwreset.php"><?php echo __('Forgot My Password'); ?></a>
                        <?php } ?>

                    <div style="display:table-cell;padding: 15px;vertical-align:top">
                        <?php
                        $ext_bks = array();
                        foreach (UserAuthenticationBackend::allRegistered() as $bk)
                            if ($bk instanceof ExternalAuthentication)
                                $ext_bks[] = $bk;

                        if (count($ext_bks)) {
                            foreach ($ext_bks as $bk) {
                                ?>
                                <div class="external-auth"><?php $bk->renderExternalLink(); ?></div><?php
                            }
                        }
                        if ($cfg && $cfg->isClientRegistrationEnabled()) {
                            if (count($ext_bks))
                                echo '<hr style="width:70%"/>';
                            ?>

<?php } ?>
                    </div>

                </form>
            </div>
        </div>
                  <div class="col-md-6">
                       <div class="well">
                      <ul class="list-unstyled" style="line-height: 2">
                          <li><span class="fa fa-check text-success"></span> <?php echo __('Not yet registered?'); ?> <a href="account.php?do=create"><?php echo __('Create an account'); ?></a></li>
                          <li><span class="fa fa-check text-success"></span>    <?php
    if ($cfg->getClientRegistrationMode() != 'disabled' || !$cfg->isClientLoginRequired()) {
        echo sprintf(__('If this is your first time contacting us or you\'ve lost the ticket number, please %s open a new ticket %s'), '<a href="open.php">', '</a>');
    }
    ?></li>
                          <li><span class="fa fa-check text-success"></span>                             <b><?php echo __("I'm an agent"); ?></b> -
                            <a href="<?php echo ROOT_PATH; ?>scp/"><?php echo __('sign in here'); ?></a></li>
                      </ul>
                       </div>
                  </div>

</div>



