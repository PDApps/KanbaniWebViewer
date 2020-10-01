<?php
$context->hooks->registerLast("echo_qrCode", function (array &$vars) {
    $vars["headers"][] = "Content-Type: image/svg+xml";
?>
<?='<?xml version="1.0" encoding="utf-8"?>'?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="256px" height="256px" viewBox="0 0 256 256" enable-background="new 0 0 256 256" xml:space="preserve">
    <text transform="matrix(1 0 0 1 34 118)">
        <tspan x="0" y="0" fill="#FF0000" font-size="36"><?=$this("No QR code")?></tspan>
        <tspan x="-24" y="43" fill="#FF0000" font-size="36"><?=$this("plugin installed")?></tspan>
    </text>
    <g>
        <path fill="#FF0000" d="M254,2v252H2V2H254 M256,0H0v256h256V0L256,0z" />
    </g>
</svg>
<?php
    return true;
});