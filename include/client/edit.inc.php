<?php

if(!defined('OSTCLIENTINC') || !$thisclient || !$ticket || !$ticket->checkUserAccess($thisclient)) die('Access Denied!');

?>

<h1>
    <?php echo sprintf(__('Editing Ticket #%s'), $ticket->getNumber()); ?>
</h1>

<form action="tickets.php" method="post">
    <?php echo csrf_token(); ?>
    <input type="hidden" name="a" value="edit"/>
    <input type="hidden" name="id" value="<?php echo Format::htmlchars($_REQUEST['id']); ?>"/>

    <div id="dynamic-form">
    <?php if ($forms)
        foreach ($forms as $form) {
           $form->render(['staff' => false]);
    } ?>
    </div>

<hr>
<p style="text-align: center;"><br><br>
    <input class="btn btn-success" type="submit" value="Update"/>
    <input class="btn btn-warning" type="reset" value="Reset"/>
    <input class="btn btn-danger" type="button" value="Cancel" onclick="javascript:
        window.location.href='index.php';"/>
</p>
</form>
