<?php
$context->hooks->registerLast("echo_empty", function (array $vars) {
    // $bodyAttributes
    // $title
    // $css
    // $js
    // $body
    extract($vars);
    $class = &$bodyAttributes["class"];
    $class .= " js_no";
?>
<!DOCTYPE html>
<html lang="<?=htmlspecialchars($this->language)?>">
    <head>
        <meta charset="utf-8">
        <title><?=htmlspecialchars($title)?></title>
        <meta name="viewport" content="initial-scale=1.0, width=device-width">
        <?php foreach ($css as $url) {?>
            <link rel="stylesheet" href="<?=htmlspecialchars($url)?>">
        <?php }?>
        <?php foreach ($js as $url) {?>
            <script defer src="<?=htmlspecialchars($url)?>"></script>
        <?php }?>
    </head>
    <body <?=Kanbani\htmlAttributes($bodyAttributes)?>>
        <?=$body?>
    </body>
</html>
<?php
});