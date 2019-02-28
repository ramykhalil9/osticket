<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$email=Format::input($_POST['lemail']?$_POST['lemail']:$_GET['e']);
$ticketid=Format::input($_POST['lticket']?$_POST['lticket']:$_GET['t']);

if ($cfg->isClientEmailVerificationRequired())
    $button = __("Email Access Link");
else
    $button = __("View Ticket");
?>
<h1><?php echo __('Check Ticket Status'); ?></h1>
<p><?php
echo __('Please provide your email address and a ticket number.');
if ($cfg->isClientEmailVerificationRequired())
    echo ' '.__('An access link will be emailed to you.');
else
    echo ' '.__('This will sign you in to view your ticket.');
?></p>
<div class="login-page row">    
    <div id="loginbox" class="col-md-6">                    
                   <div class="well">
<form action="login.php" method="post" id="clientLogin" class="form-horizontal">
    <?php csrf_token(); ?>

    <div><strong><?php echo Format::htmlchars($errors['login']); ?></strong></div>
       <div style="margin-bottom: 25px" class="input-group">
                        <span class="input-group-addon btn-primary"><i class="fas fa-envelope"></i></span>
        <input id="email" placeholder="<?php echo __('Email Address'); ?>" type="text"
            name="lemail" size="30" value="<?php echo $email; ?>" class="nowarn form-control"></label>
    </div>
    <div style="margin-bottom: 25px" class="input-group">
                        <span class="input-group-addon btn-primary"><i class="fas fa-ticket-alt"></i></span>
        <input id="ticketno" type="text" name="lticket" placeholder="<?php echo __('Ticket Number'); ?>"
            size="30" value="<?php echo $ticketid; ?>" class="nowarn form-control">
    </div>
        <input class="btn btn-lg btn-primary btn-block" type="submit" value="<?php echo $button; ?>">

</form>
</div></div>
<div class="col-md-6">
                       <div class="well">
                      <ul class="list-unstyled" style="line-height: 2"><li><span class="fa fa-check text-success"></span>
<?php if ($cfg && $cfg->getClientRegistrationMode() !== 'disabled') { ?>
        <?php echo __('Have an account with us?'); ?>
            <a href="login.php"><?php echo __('Sign In'); ?></a> <?php
    if ($cfg->isClientRegistrationEnabled()) { ?>
<?php echo sprintf(__('or %s register for an account %s to access all your tickets.'),
    '<a href="account.php?do=create">','</a>');
    }
    }?></li>

 <li><span class="fa fa-check text-success"></span>                      
<?php
if ($cfg->getClientRegistrationMode() != 'disabled'
    || !$cfg->isClientLoginRequired()) {
    echo sprintf(
    __("If this is your first time contacting us or you've lost the ticket number, please %s open a new ticket %s"),
        '<a href="open.php">','</a>');
    } ?></li>
                      </ul>
                       </div>
                  </div>
</div>