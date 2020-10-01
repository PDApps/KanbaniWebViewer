<?php
/* This plugin displays a user friendly page on uncaught PHP exceptions. */

set_exception_handler(function ($exception) use ($context) {
    while (ob_get_level()) {
        ini_get("display_errors") == 1 ? ob_end_flush() : ob_end_clean();
    }
    error_log(sprintf("%s in %s:%d\n%s",
        $exception->getMessage(),
        $exception->getFile(), $exception->getLine(),
        $exception->getTraceAsString()));
    http_response_code(500);
    $context->hooks->echo_empty([
        "bodyAttributes" => ["class" => "middle body_full"],
        "body" => $context->hooks->template("exception", [
            "exception" => $exception,
            "showMessage" =>
                ($exception instanceof Kanbani\PublicException) ||
                ($exception instanceof Kanbani\SyncFileException) ||
                ($exception instanceof Kanbani\QrCodeException) ||
                !empty($exception->kwvIsPublic),
        ]),
    ]);
});

$context->hooks->register("echo_exception", function (array $vars) {
    // $exception
    // $showMessage
    extract($vars);
?>
    <article class="ex middle__out">
        <div class="ex__wr middle__in">
            <?php if ($showMessage) {?>
                <p><?=htmlspecialchars($this($exception->getMessage()))?></p>
            <?php } else {?>
                <h1><?=$this("No way!")?></h1>
                <p>
                    <?=$this(
                        "You hit an unexpected %s – contact us for assistance.",
                        htmlspecialchars(preg_replace('/([a-z])([A-Z])/', '\1 \2', basename(get_class($exception))))
                    )?>
                </p>
            <?php }?>
            <?php if (ini_get("display_errors") == 1) {?>
                <textarea readonly class="ex__trace light-shade block-area">
<?=htmlspecialchars($exception->getMessage())?>

in <?=htmlspecialchars($exception->getFile().":".$exception->getLine())?>


<?=htmlspecialchars($exception->getTraceAsString())?>
</textarea>
            <?php }?>
        </div>
    </article>
<?php
});
