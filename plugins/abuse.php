<?php
/* This plugin adds a link to the bottom of every page with the configured target
   like "mailto:abuse@the.company" or any other URL. May be useful for public servers. */

if ($href = $context->config["abuse.href"] ?? "") {
    $context->hooks->registerAfter("echo_empty", function () use ($href) {
?>
<a id="abuse" class="isle" href="<?=htmlspecialchars($href)?>"><?=$this("Abuse Contact")?></a>
<?php
    });
}