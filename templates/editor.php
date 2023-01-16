<?php
    style("drawio", "editor");
    script("drawio", "editor");

    $frame_params = "?embed=1&embedRT=1";
    if ($_["drawioOfflineMode"] === "yes")
    {
        $frame_params .= "&offline=1&stealth=1";
    }
    if ($_["drawioLibraries"] == "yes")
    {
        $frame_params .= "&libraries=1";
    }

    if ($_['isWB'] == "true")
    {
        $frame_params .= "&ui=sketch";
    }
    else if (!empty($_["drawioTheme"]))
    {
        $frame_params .= "&ui=".$_["drawioTheme"];
    }
    
    if ($_["drawioDarkMode"] == "on") $frame_params .= "&dark=1";
    if (!empty($_["drawioLang"])) $frame_params .= "&lang=".$_["drawioLang"];
    if (!empty($_["drawioUrlArgs"])) $frame_params .= "&".$_["drawioUrlArgs"];
    $finalAutosave = $_['drawioAutosave'];

    if ($_['drawioReadOnly']) {
        $frame_params .= "&chrome=0"; //read-only viewer
        $finalAutosave = false;
    }

    $frame_params .= "&spin=1&proto=json&p=nxtcld&keepmodified=1";
?>

<div id="app-content">

    <iframe id="iframeEditor" data-id="<?php p($_["fileId"]) ?>" data-sharetoken="<?php p($_["shareToken"]) ?>" width="100%" height="100%" align="top" frameborder="0" name="iframeEditor" onmousewheel="" allowfullscreen=""></iframe>

    <script type="text/javascript" nonce="<?php p(base64_encode($_["requesttoken"])) ?>" defer>
        window.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($_['error'])) { ?>
                OCA.DrawIO.DisplayError("<?php p($_['error']) ?>");
            <?php } else { ?>
                var iframe = document.getElementById("iframeEditor");
                var originUrl = "<?php p($_['drawioUrl']); ?>";
                var drawIoUrl = "<?php p($_['drawioUrl']); print_unescaped($frame_params); ?>"
                var autosave = "<?php p($finalAutosave); ?>";
                var isWB = <?php p($_['isWB']); ?>;
                var previews = <?php p($_['drawioPreviews'] == 'yes'? 'true' : 'false'); ?>;

                <?php if ($_["drawioDarkMode"] == "auto") { ?>
                    try
                    {
                        var darkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                        var themeName = OCA.Theming.enabledThemes[0];

                        if ((!themeName || themeName === 'default') && darkMode)
                        {
                            drawIoUrl += '&dark=1';
                        }
                        else if (themeName && themeName.indexOf('dark') !== -1)
                        {
                            drawIoUrl += '&dark=1';
                        }
                    }
                    catch (e){}
                <?php } ?>
                OCA.DrawIO.EditFile(iframe.contentWindow, originUrl, autosave, isWB, previews);
                iframe.setAttribute('src', drawIoUrl);
            <?php } ?>
        });
    </script>
</div>
