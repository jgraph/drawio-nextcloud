<?php
    style("drawio", "editor");
    script("drawio", "editor");

    $frame_params = "?embed=1&embedRT=1&configure=1";
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

    $_["frame_params"] = $frame_params;
    $_["finalAutosave"] = $finalAutosave;

    $drawioData = base64_encode(json_encode($_));
?>

<div id="app-content">
    <iframe id="iframeEditor" data-id="<?php p($_["fileId"]) ?>" data-sharetoken="<?php p($_["shareToken"]) ?>" width="100%" height="100%" align="top" frameborder="0" name="iframeEditor" onmousewheel="" allowfullscreen=""></iframe>
    <div style="display: none" id="drawioData"><?php print_unescaped($drawioData) ?></div>
</div>
