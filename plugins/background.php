<?php
/* This plugin adds background image to every page and a selector in Customize
   to change it for board-related pages. */
namespace Kanbani;

$backgrounds = [];
foreach (glob("backgrounds/*.{jpg,png}", GLOB_BRACE) as $file) {
    $backgrounds[] = $file;
}

if ($backgrounds) {
    $current = function ($context) use ($backgrounds) {
        $current = $context->request["background"] ?? "";
        if (!in_array($current, $backgrounds) && $context->currentBoard) {
            $current = $backgrounds[abs(crc32($context->currentBoard->id)) % count($backgrounds)];
        }
        if (!in_array($current, $backgrounds)) {
            $current = $backgrounds[gmdate("W") % count($backgrounds)];
        }
        return $current;
    };

    $context->hooks->register("echo_empty", function (array &$vars) use ($current) {
        if ($url = $current($this)) {
            $style = &$vars["bodyAttributes"]["style"];
            $style .= "; background-image: url(\"".htmlspecialchars($url)."\")";
        }
    });

    $context->hooks->registerFirst("echo_boardCustomize", function () use ($current, $backgrounds) {
        $titles = array_map(function ($url) {
            return ucwords(strtr(basename($url, strrchr($url, ".")), "-_.", "   "));
        }, $backgrounds);
?>
        <tr>
            <th class="tbl__th"><?=$this("Background:")?></th>
            <td>
                <select name="background">
                    <?=htmlOptions(
                        array_merge(["-"], $backgrounds),
                        array_merge([$this("None")], $titles),
                        $current($this)
                    )?>
                </select>
            </td>
        </tr>
<?php
    });
}