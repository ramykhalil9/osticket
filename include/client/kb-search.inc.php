<div class="row">
    <div class="col-md-12">
    <h1><?php echo __('Frequently Asked Questions');?></h1>
    </div>
<div class="col-md-9">
    
    <div><h4><strong><?php echo __('Search Results'); ?></strong></h4></div>
<?php
    if ($faqs->exists(true)) {
        echo (sprintf(__('%d FAQs matched your search criteria.'),$faqs->count()));
        echo '<div id="faq" style="margin-top:10px; margin-left: 15px;"><div class="rectangle-list"><ol>';
        foreach ($faqs as $F) {
            echo sprintf(
                '<li><a href="faq.php?id=%d" class="previewfaq">%s</a></li>',
                $F->getId(), $F->getLocalQuestion(), $F->getVisibilityDescription());
        }
        echo '</ol></div></div>';
    } else {
        echo '<strong class="faded">'.__('The search did not match any FAQs.').'</strong>';
    }
?>
</div>

<div class="col-md-3">
    <div class="sidebar">
  <!--  <div class="searchbar">
        <form method="get" action="faq.php">
        <input type="hidden" name="a" value="search"/>
        <input type="text" name="q" class="search" placeholder="<?php
            echo __('Search our knowledge base'); ?>"/>
        <input type="submit" style="display:none" value="search"/>
        </form>
    </div> -->
    <div class="content">
        <div class="panel panel-primary">
           <div class="panel-heading"><?php echo __('Help Topics'); ?></div>
           <ul class="list-group">
<?php
foreach (Topic::objects()
    ->annotate(array('faqs_count'=>SqlAggregate::count('faqs')))
    ->filter(array('faqs_count__gt'=>0))
    as $t) { ?>
       <li class="list-group-item"><a href="?topicId=<?php echo urlencode($t->getId()); ?>"
            ><?php echo $t->getFullName(); ?></a></li>
<?php } ?></ul>
        </div>
         <div class="panel panel-success">
  <div class="panel-heading"><?php echo __('Categories'); ?></div>
  <ul class="list-group">
<?php
foreach (Category::objects()
    ->exclude(Q::any(array('ispublic'=>Category::VISIBILITY_PRIVATE)))    
    ->annotate(array('faqs_count'=>SqlAggregate::count('faqs')))
    ->filter(array('faqs_count__gt'=>0))
    as $C) { ?>
       <li class="list-group-item"><a href="?cid=<?php echo urlencode($C->getId()); ?>"
            ><?php echo $C->getLocalName(); ?></a></li>
<?php } ?></ul>
        </div>
    </div>
    </div>
</div>
</div>
