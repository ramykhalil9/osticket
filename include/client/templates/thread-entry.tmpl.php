<?php
global $cfg;
$entryTypes = array('M'=>'message', 'R'=>'response', 'N'=>'note');
$isUser = $entry->getUser() ? true : false;
$user = $entry->getUser() ?: $entry->getStaff();
$name = $user ? $user->getName() : $entry->poster;
$avatar = '';
if ($cfg->isAvatarsEnabled() && $user)
    $avatar = $user->getAvatar();
?>
<?php
 $type = $entryTypes[$entry->type];
 ?>
        <article class="panel panel-primary <?php if($isUser) echo "thread-is-user"; else echo "thread-is-agent"; ?> <?php echo $type; ?> <?php if ($avatar) echo 'avatar'; ?>">
            <div class="panel-heading icon image-user">
                <?php if ($avatar) { ?>
                    <?php echo $avatar; ?>
                <?php } else { ?>
                    <i class="fas fa-user"></i>
                <?php } ?>

            </div>
            <div class="panel-heading">
                <div class="pull-right">
                    <span style="vertical-align:middle;" class="textra">
                        <?php if ($entry->flags & ThreadEntry::FLAG_EDITED) { ?>
                            <span class="label label-bare" title="<?php
                            echo sprintf(__('Edited on %s by %s'), Format::datetime($entry->updated), 'You');
                            ?>"><?php echo __('Edited'); ?></span>
                              <?php } ?>
                    </span>
                </div>
                <?php
                echo sprintf(__('<b>%s</b> posted %s'), $name, sprintf('<time datetime="%s" title="%s">%s</time>', date(DateTime::W3C, Misc::db2gmtime($entry->created)), Format::daydatetime($entry->created), Format::datetime($entry->created)
                        )
                );
                ?>
                <span class="faded title truncate"><?php echo $entry->title; ?></span>
               
            </div>
            <div class="panel-body" id="thread-id-<?php echo $entry->getId(); ?>">
                <div><?php echo $entry->getBody()->toHtml(); ?></div>
                <div class="clear"></div>
<?php if ($entry->has_attachments) { ?>
                    <div class="attachments"><?php
    foreach ($entry->attachments as $A) {
        if ($A->inline)
            continue;
        $size = '';
        if ($A->file->size)
            $size = sprintf('<small class="filesize faded">%s</small>', Format::file_size($A->file->size));
        ?>
                            <span class="attachment-info">
                                <i class="icon-paperclip icon-flip-horizontal"></i>
                                        <a  class="no-pjax truncate filename"
            href="<?php echo $A->file->getDownloadUrl(['id' => $A->getId()]);
        ?>" download="<?php echo Format::htmlchars($A->getFilename()); ?>"
                                   target="_blank"><?php echo Format::htmlchars($A->getFilename());
                    ?></a><?php echo $size; ?>
                            </span>
                               <?php } ?>
                    </div>
                            <?php } ?>
            </div>

<?php if ($urls = $entry->getAttachmentUrls()) { ?>
        <script type="text/javascript">
            $('#thread-id-<?php echo $entry->getId(); ?>')
                    .data('urls', <?php echo JsonDataEncoder::encode($urls); ?>)
                    .data('id', <?php echo $entry->getId(); ?>);
        </script>
<?php }
?>
</article>



