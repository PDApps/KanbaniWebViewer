<?php
$context->hooks->registerLast("echo_decrypt", function () {
?>
<div class="middle__in_pad">
    <h1><?=$this("Encrypted profile")?></h1>
    <p><?=$this("This profile contains encrypted data. However, viewing such profiles was disabled on this server.")?></p>
</div>
<?php
});
