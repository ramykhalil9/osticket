<?php
/*********************************************************************
    index.php

    Helpdesk landing page. Please customize it to fit your needs.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('client.inc.php');

require_once INCLUDE_DIR . 'class.page.php';
$section = 'home';
require(CLIENTINC_DIR.'header.inc.php');
?>
</div>
<div class="container-fluid">
<div class="row support-image" style="background-image: url('<?php echo ASSETS_PATH; ?>images/support.jpg')">
    
      <div class="centered"><h2>You have questions.</h2><h4>We have answers.</h4>
<?php

    if($cfg && ($page = $cfg->getLandingPage())){}
        // echo $page->getBodyWithImages();
    else
        echo  '<h1>'.__('Quick and Efficient Support').'</h1>';
    ?></div></div>
</div>
<div class="container">
<div class="row front-boxes">
<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="outer_box_green">
                        <div class="triangle triangle-green">
                            <img src="<?php echo ASSETS_PATH; ?>images/new_ticket_icon.png" alt="<?php echo __('Open a New Ticket') ?>" style="width:65px;">
                        </div>
                        <div class="box-body">
                            <h1><?php echo __('Open a New Ticket') ?></h1>
                            <p><?php echo __('Please provide as much detail as possible so we can best assist you. To update a previously submitted ticket, please login.') ?></p>
                            <a class="btn btn-success" href="<?php echo ROOT_PATH; ?>open.php"><?php echo __('Open a New Ticket') ?></a> <br><br>
                        </div>
                    </div>
                </div>
  <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <div class="outer_box_blue">
                        <div class="triangle triangle-blue">
                            <img src="<?php echo ASSETS_PATH; ?>images/check_status_icon.png" alt="Check Ticket Status" style="width:65px;">
                        </div>
                        <div class="box-body">
                            <h1><?php echo __('Check Ticket Status') ?></h1>
                            <p><?php echo __('We provide archives and history of all your current and past support requests complete with responses.') ?></p>
                            <a class="btn btn-primary" href="<?php echo ROOT_PATH; ?>tickets.php"><?php echo __('Check Ticket Status') ?></a> <br><br>
                        </div>
                    </div>
                </div>  
</div>
    
<?php require(CLIENTINC_DIR.'footer.inc.php'); ?>
