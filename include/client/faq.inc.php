<?php
if(!defined('OSTCLIENTINC') || !$faq  || !$faq->isPublished()) die('Access Denied');

$category=$faq->getCategory();

?>
<div class="row">
    <div class="col-md-12">
    <h1><?php echo __('Frequently Asked Question');?></h1>
<div id="breadcrumbs">
    <a href="index.php"><?php echo __('All Categories');?></a>
    &raquo; <a href="faq.php?cid=<?php echo $category->getId(); ?>"><?php
    echo $category->getFullName(); ?></a>
</div></div>
<div class="col-md-9">

<div class="faq-content">
<div class="article-title flush-left">
    <h3><?php echo $faq->getLocalQuestion() ?></h3>
</div>
<div class="faded"><i class="far fa-clock"></i> <?php echo sprintf(__('Last Updated %s'),
    Format::relativeTime(Misc::db2gmtime($faq->getUpdateDate()))); ?></div>
<br/>
<div>
    <p><?php echo $faq->getLocalAnswerWithImages(); ?></p>
</div>
</div>
</div>

<div class="col-md-3">
<!--<div class="searchbar">
    <form method="get" action="faq.php">
    <input type="hidden" name="a" value="search"/>
    <input type="text" name="q" class="search" placeholder="<?php
        echo __('Search our knowledge base'); ?>"/>
    <input type="submit" style="display:none" value="search"/>
    </form>
</div> -->
<div class="content"><?php
    if ($attachments = $faq->getLocalAttachments()->all()) { ?>
<section>
    <strong><?php echo __('Attachments');?>:</strong>
<?php foreach ($attachments as $att) { ?>
    <div>
        <a href="<?php echo $att->file->getDownloadUrl(['id' => $att->getId()]);
    ?>" class="no-pjax">
        <i class="icon-file"></i>
        <?php echo Format::htmlchars($att->getFilename()); ?>
    </a>
    </div>
<?php } ?>
</section>
<?php }
if ($faq->getHelpTopics()->count()) { ?>
<div class="panel panel-success">
    <div class="panel-heading">
    <?php echo __('Help Topics'); ?></div>
    <ul class="list-group">
<?php foreach ($faq->getHelpTopics() as $T) { ?>
    <li class="list-group-item"><?php echo $T->topic->getFullName(); ?></li>
<?php } ?>
</ul></div>
<?php }
?></div>

</div>

</div>
