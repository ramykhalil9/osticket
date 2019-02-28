<div class="row">
    <div class="col-md-12">
    <h1><?php echo __('Knowledgebase'); ?></h1>

<?php
    $categories = Category::objects()
        ->exclude(Q::any(array(
            'ispublic'=>Category::VISIBILITY_PRIVATE,
            Q::all(array(
                    'faqs__ispublished'=>FAQ::VISIBILITY_PRIVATE,
                    'children__ispublic' => Category::VISIBILITY_PRIVATE,
                    'children__faqs__ispublished'=>FAQ::VISIBILITY_PRIVATE,
                    ))
        )))
                //->annotate(array('faq_count'=>SqlAggregate::COUNT('faqs__ispublished')));
        ->annotate(array('faq_count' => SqlAggregate::COUNT(
                        SqlCase::N()
                        ->when(array(
                                'faqs__ispublished__gt'=> FAQ::VISIBILITY_PRIVATE), 1)
                        ->otherwise(null)
        )))
        ->annotate(array('children_faq_count' => SqlAggregate::COUNT(
                        SqlCase::N()
                        ->when(array(
                                'children__faqs__ispublished__gt'=> FAQ::VISIBILITY_PRIVATE), 1)
                        ->otherwise(null)
        )));
    if ($categories->exists(true)) { ?>
    <p><?php echo __('Click on the category to browse FAQs.'); ?></p></div>
        <div class="col-md-9">
        <ul id="kb" class="list-group">
<?php
          foreach ($categories as $C) {
            // Don't show subcategories with parents.
            if (($p=$C->parent)
                    && ($categories->findFirst(array(
                                'category_id' => $p->getId()))))
                continue;

            $count = $C->faq_count + $C->children_faq_count;
            ?>
            <li class="list-group-item">
                <div class="row">
                <div class="col-xs-3 col-md-2"><i class="fas fa-folder-open"></i></div>
            <div class="col-xs-9 col-md-10">
            <h4><?php echo sprintf('<a href="faq.php?cid=%d">%s %s</a>',
                $C->getId(), Format::htmlchars($C->getFullName()),
                $count ? "({$count})": ''
                ); ?></h4>
            <div class="faded list-group-item-text" style="margin:10px 0">
                <?php echo Format::safe_html($C->getLocalDescriptionWithImages()); ?>
            </div>
                <div class="rectangle-list">
                <ol>
<?php
            if (($subs=$C->getPublicSubCategories())) {
                echo '<p/><div style="padding-bottom:15px;">';
                foreach ($subs as $c) {
                    echo sprintf('<div><i class="icon-folder-open"></i>
                            <a href="faq.php?cid=%d">%s (%d)</a></div>',
                            $c->getId(),
                            $c->getLocalName(),
                            $c->faq_count
                            );
                }
                echo '</div>';
            }

            foreach ($C->faqs
                    ->exclude(array('ispublished'=>FAQ::VISIBILITY_PRIVATE))
                    ->limit(5) as $F) { ?>
                <li>
                <a href="faq.php?id=<?php echo $F->getId(); ?>">
                <?php echo $F->getLocalQuestion() ?: $F->getQuestion(); ?>
                </a></li>
<?php       } ?> </ol></div>
            </div></div>
            </li>
<?php   } ?>
       </ul>
<?php
    } else {
        echo __('NO FAQs found');
    }
?>
        </div><div class="col-md-3">
    <div class="sidebar">
    <div class="searchbar">
        <form method="get" action="faq.php">
        <input type="hidden" name="a" value="search"/>
        <select class="form-control" name="topicId"
            onchange="javascript:this.form.submit();">
            <option value="">—<?php echo __("Browse by Topic"); ?>—</option>
<?php
$topics = Topic::objects()
    ->annotate(array('has_faqs'=>SqlAggregate::COUNT('faqs')))
    ->filter(array('has_faqs__gt'=>0));
foreach ($topics as $T) { ?>
        <option value="<?php echo $T->getId(); ?>"><?php echo $T->getFullName();
            ?></option>
<?php } ?>
        </select>
        </form>
    </div>
    <br/>
    </div>
</div>
</div>
