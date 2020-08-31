<?php
$context->hooks->registerLast("echo_decryptPage", function (array $vars) {
    http_response_code(401);
    ob_start();
?>
<article class="middle__out">
    <div class="middle__in">
        <?=$this->hooks->template("decrypt", $vars)?>
    </div>
</article>
<?php
    $this->hooks->echo_empty([
        "bodyAttributes" => ["class" => "middle body_full"],
        "body" => ob_get_clean(),
    ]);
});
