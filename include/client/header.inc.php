<?php
$title=($cfg && is_object($cfg) && $cfg->getTitle())
    ? $cfg->getTitle() : 'osTicket :: '.__('Support Ticket System');
$signin_url = ROOT_PATH . "login.php"
    . ($thisclient ? "?e=".urlencode($thisclient->getEmail()) : "");
$signout_url = ROOT_PATH . "logout.php?auth=".$ost->getLinkToken();

header("Content-Type: text/html; charset=UTF-8");
header("X-Frame-Options: SAMEORIGIN");
if (($lang = Internationalization::getCurrentLanguage())) {
    $langs = array_unique(array($lang, $cfg->getPrimaryLanguage()));
    $langs = Internationalization::rfc1766($langs);
    header("Content-Language: ".implode(', ', $langs));
}
?>
<!DOCTYPE html>
<html<?php
if ($lang
        && ($info = Internationalization::getLanguageInfo($lang))
        && (@$info['direction'] == 'rtl'))
    echo ' dir="rtl" class="rtl"';
if ($lang) {
    echo ' lang="' . $lang . '"';
}
?>>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title><?php echo Format::htmlchars($title); ?></title>
    <meta name="description" content="customer support platform">
    <meta name="keywords" content="osTicket, Customer support system, support ticket system">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/bootstrap.min.css?d4e240b" media="screen"/>
	<link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/osticket.css?d4e240b" media="screen"/>
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/print.css?d4e240b" media="print"/>
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>scp/css/typeahead.css?d4e240b"
         media="screen" />
    <link type="text/css" href="<?php echo ROOT_PATH; ?>css/ui-lightness/jquery-ui-1.10.3.custom.min.css?d4e240b"
        rel="stylesheet" media="screen" />
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/thread.css?d4e240b" media="screen"/>
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/redactor.css?d4e240b" media="screen"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>assets/default/fontawesome/css/fontawesome-all.min.css?d4e240b"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/flags.css?d4e240b"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/rtl.css?d4e240b"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/select2.min.css?d4e240b"/>
    <!-- Favicons -->
    <link rel="icon" type="image/png" href="<?php echo ROOT_PATH ?>images/oscar-favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="<?php echo ROOT_PATH ?>images/oscar-favicon-16x16.png" sizes="16x16" />
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-3.3.1.min.js?d4e240b"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-ui-1.12.1.custom.min.js?d4e240b"></script>
    <script src="<?php echo ROOT_PATH; ?>js/osticket.js?d4e240b"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/filedrop.field.js?d4e240b"></script>
    <script src="<?php echo ROOT_PATH; ?>scp/js/bootstrap-typeahead.js?d4e240b"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor.min.js?d4e240b"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-plugins.js?d4e240b"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-osticket.js?d4e240b"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/select2.min.js?d4e240b"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/fabric.min.js?d4e240b"></script>
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/mtheme.css?d4e240b" media="screen"/>
     <script type="text/javascript" src="<?php echo ASSETS_PATH; ?>js/bootstrap.min.js?d4e240b"></script>
     <script type="text/javascript" src="<?php echo ASSETS_PATH; ?>js/custom.js?d4e240b"></script>
    <?php
    if($ost && ($headers=$ost->getExtraHeaders())) {
        echo "\n\t".implode("\n\t", $headers)."\n";
    }

    // Offer alternate links for search engines
    // @see https://support.google.com/webmasters/answer/189077?hl=en
    if (($all_langs = Internationalization::getConfiguredSystemLanguages())
        && (count($all_langs) > 1)
    ) {
        $langs = Internationalization::rfc1766(array_keys($all_langs));
        $qs = array();
        parse_str($_SERVER['QUERY_STRING'], $qs);
        foreach ($langs as $L) {
            $qs['lang'] = $L; ?>
        <link rel="alternate" href="//<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>?<?php
            echo http_build_query($qs); ?>" hreflang="<?php echo $L; ?>" />
<?php
        } ?>
        <link rel="alternate" href="//<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>"
            hreflang="x-default" />
<?php
    }
    ?>
</head>
<body>
    
  <nav role="navigation" class="navbar navbar-inverse">

    <div class="container">
        <div class="navbar-header">
            <button aria-controls="navbar" aria-expanded="false" data-target="#navbar" data-toggle="collapse" class="navbar-toggle collapsed" type="button">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
             <!-- <?php if ($cfg->getClientRegistrationMode() == 'public') { ?>
                <label class="welcome-user">Welcome <?php echo __('Guest'); ?> </label><?php 
                } ?> -->
        <?php if ($thisclient && is_object($thisclient) && $thisclient->isValid()
                    && !$thisclient->isGuest()) { ?>
        <label class="welcome-user">Welcome <?php echo Format::htmlchars($thisclient->getName()); ?> </label>
                    <?php } else {?>
         <label class="welcome-user">Welcome <?php echo __('Guest'); ?> </label>
                    <?php }?>
        </div>
        <div class="navbar-collapse collapse" id="navbar" aria-expanded="false" style="height: 1px;">
            <ul class="nav navbar-nav navbar-right head-menu">
                <?php
if (($all_langs = Internationalization::getConfiguredSystemLanguages())
    && (count($all_langs) > 1)
) {
    $qs = array();
    parse_str($_SERVER['QUERY_STRING'], $qs);
    foreach ($all_langs as $code=>$info) {
        list($lang, $locale) = explode('_', $code);
        $qs['lang'] = $code;
?>
                        <a class="flag flag-<?php echo strtolower($info['flag'] ?: $locale ?: $lang); ?>"
            href="?<?php echo http_build_query($qs);
            ?>" title="<?php echo Internationalization::getLanguageDescription($code); ?>">&nbsp;</a></li>
<?php }
} ?>
             <?php        if($nav){ ?>       
            <?php
           if($nav && ($navs=$nav->getNavLinks()) && is_array($navs)){
                foreach($navs as $name =>$nav) {
                    echo sprintf('<li><a class="%s %s" href="%s">%s</a></li>%s',$nav['active']?'active':'',$name,(ROOT_PATH.$nav['href']),$nav['desc'],"\n");
                }
            } ?>
        
        <?php
        } ?>   
             <?php
                if ($thisclient && is_object($thisclient) && $thisclient->isValid()
                    && !$thisclient->isGuest()) {
                // echo Format::htmlchars($thisclient->getName()).'&nbsp;|';
                 ?>
                <li><a href="<?php echo ROOT_PATH; ?>profile.php"><?php echo __('Profile'); ?></a> </li>
            <!--    <li><a href="<?php echo ROOT_PATH; ?>tickets.php"><?php echo sprintf(__('Tickets <b>(%d)</b>'), $thisclient->getNumTickets()); ?></a> </li> -->
                <li><a href="<?php echo $signout_url; ?>"><?php echo __('Sign Out'); ?></a> </li>
            <?php
            } elseif($nav) {
                if ($thisclient && $thisclient->isValid() && $thisclient->isGuest()) { ?>
                    <li><a href="<?php echo $signout_url; ?>"><?php echo __('Sign Out'); ?></a></li><?php
                }
                elseif ($cfg->getClientRegistrationMode() != 'disabled') { ?>
                     <li><a href="<?php echo $signin_url; ?>"><?php echo __('Sign In'); ?></a></li>
<?php
                }
            } ?>
                            </ul>

        </div><!--/.navbar-collapse -->
    </div>
</nav>   
        <div class="container">
<div class="row">
            <div class="col-sm-6">
            <a id="logo" href="<?php echo ROOT_PATH; ?>index.php"
            title="<?php echo __('Support Center'); ?>">
                <span class="valign-helper"></span>
                <img src="<?php echo ROOT_PATH; ?>logo.php" border=0 alt="<?php
                //echo $ost->getConfig()->getTitle(); ?>">
            </a>
            </div> <div class="col-sm-6">
                
                <?php
if ($cfg && $cfg->isKnowledgebaseEnabled()) { ?>
<div class="search">
    <form method="get" action="/kb/faq.php">
    <input type="hidden" name="a" value="search"/>
    <input type="text" class="form-control input-sm" name="q" class="search" placeholder="<?php echo __('Search our knowledge base'); ?>"/>
    <button type="submit" class="btn btn-primary btn-sm"><?php echo __('Search'); ?></button>
    </form>
</div>

<?php
} ?>

            </div>
        </div>
        
         <?php if($errors['err']) { ?>
            <div class="alert alert-danger"><?php echo $errors['err']; ?></div>
         <?php }elseif($msg) { ?>
            <div class="alert alert-info"><?php echo $msg; ?></div>
         <?php }elseif($warn) { ?>
            <div class="alert alert-warning"><?php echo $warn; ?></div>
         <?php } ?>
