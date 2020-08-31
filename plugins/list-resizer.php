<?php
/* This plugin allows resizing lists of a board in horizontal view mode. */

$context->hooks->register("echo_afterListItem", function () {
?>
<div class="lists__list-resizer"
     title="<?=$this("Drag to resize the list on the left. Click to show it or hide. Reveal any hidden list or its card via ToC.")?>"
></div><?php    // can't have spaces to not break margin between list-items.
});
