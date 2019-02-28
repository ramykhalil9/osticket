<?php
$info = $_POST;
if (!isset($info['timezone']))
    $info += array(
        'backend' => null,
    );
if (isset($user) && $user instanceof ClientCreateRequest) {
    $bk = $user->getBackend();
    $info = array_merge($info, array(
        'backend' => $bk::$id,
        'username' => $user->getUsername(),
    ));
}
$info = Format::htmlchars(($errors && $_POST)?$_POST:$info);

?>
<h1><?php echo __('Account Registration'); ?></h1>
<p><?php echo __(
'Use the forms below to create or update the information we have on file for your account'
); ?>
</p>
<form action="account.php" method="post" class="form-horizontal registration">
  <?php csrf_token(); ?>
  <input type="hidden" name="do" value="<?php echo Format::htmlchars($_REQUEST['do']
    ?: ($info['backend'] ? 'import' :'create')); ?>" />
<?php
    $cf = $user_form ?: UserForm::getInstance();
    $cf->render(array('staff' => false, 'mode' => 'create'));
?>

        <div><h3><?php echo __('Preferences'); ?><hr></h3>
            <div class="form-group">            
            <label class="control-label col-sm-2"><?php echo __('Time Zone');?></label>
            <div class="col-sm-10">
            <?php
            $TZ_NAME = 'timezone';
            $TZ_TIMEZONE = $info['timezone'];
            include INCLUDE_DIR.'staff/templates/timezone.tmpl.php'; ?>
            <div class="error"><?php echo $errors['timezone']; ?></div></div></div>

        <div><h3><?php echo __('Access Credentials'); ?></h3><hr></div>

<?php if ($info['backend']) { ?>
<tr>
    <td width="180">
        <?php echo __('Login With'); ?>:
    </td>
    <td>
        <input type="hidden" name="backend" value="<?php echo $info['backend']; ?>"/>
        <input type="hidden" name="username" value="<?php echo $info['username']; ?>"/>
<?php foreach (UserAuthenticationBackend::allRegistered() as $bk) {
    if ($bk::$id == $info['backend']) {
        echo $bk->getName();
        break;
    }
} ?>
    </td>
</tr>
<?php } else { ?>
<div class="form-group">
    <label class="control-label col-sm-2"> <?php echo __('Create a Password'); ?></label>
<div class="col-sm-10">
        <input type="password" size="18" class="form-control" name="passwd1" value="<?php echo $info['passwd1']; ?>"></div>
        &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd1']; ?></span> </div>
<div class="form-group">
       <label class="control-label col-sm-2"> <?php echo __('Confirm New Password'); ?></label>
<div class="col-sm-10">
        <input type="password" size="18" class="form-control" name="passwd2" value="<?php echo $info['passwd2']; ?>"></div>
        &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd2']; ?></span></div>

<?php } ?>
<p style="text-align: center;">
    <input class="btn btn-success" type="submit" value="Register"/>
    <input class="btn btn-danger" type="button" value="Cancel" onclick="javascript:
        window.location.href='index.php';"/>
</p>
</form>
<br><br>
</div>
<?php if (!isset($info['timezone'])) { ?>
<!-- Auto detect client's timezone where possible -->
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jstz.min.js?d4e240b"></script>
<script type="text/javascript">
$(function() {
    var zone = jstz.determine();
    $('#timezone-dropdown').val(zone.name()).trigger('change');
});
</script>
<?php }
