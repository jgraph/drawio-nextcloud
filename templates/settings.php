<?php
    style("drawio", "settings");
    script("drawio", "settings");
?>
<div id="drawio" class="section section-drawio">
    <h2>Draw.io</h2>

    <div class="drawio-setting">
        <label for="drawioUrl"><?php p($l->t("Draw.io URL")) ?></label>
        <input id="drawioUrl" value="<?php p($_["drawioUrl"]) ?>" placeholder="https://<drawio-url>" type="text">
        <p class="drawio-hint"><?php p($l->t("Note: To enable realtime collaboration, leave blank or enter (https://embed.diagrams.net)")) ?></p>
    </div>

    <div class="drawio-setting">
        <label for="theme"><?php p($l->t("Theme:")) ?></label>
        <select id="theme">
            <option value="kennedy"<?php if ($_["drawioTheme"] === "kennedy") echo ' selected'; ?>><?php p($l->t("Classic")) ?></option>
            <option value="simple"<?php if ($_["drawioTheme"] === "simple") echo ' selected'; ?>><?php p($l->t("Modern")) ?></option>
            <option value="min"<?php if ($_["drawioTheme"] === "min") echo ' selected'; ?>><?php p($l->t("Minimal")) ?></option>
            <option value="atlas"<?php if ($_["drawioTheme"] === "atlas") echo ' selected'; ?>><?php p($l->t("Atlas")) ?></option>
        </select>
    </div>

    <div class="drawio-setting">
        <label for="darkMode"><?php p($l->t("Dark")) ?></label>
        <select id="darkMode">
            <option value="auto"<?php if ($_["drawioDarkMode"] === "auto") echo ' selected'; ?>><?php p($l->t("Auto")) ?></option>
            <option value="on"<?php if ($_["drawioDarkMode"] === "on") echo ' selected'; ?>><?php p($l->t("Yes")) ?></option>
            <option value="off"<?php if ($_["drawioDarkMode"] === "off") echo ' selected'; ?>><?php p($l->t("No")) ?></option>
        </select>
    </div>

    <div class="drawio-setting">
        <label for="lang"><?php p($l->t("Language")) ?></label>
        <select id="lang"></select>
        <input type="hidden" id="curLang" value="<?php p($_["drawioLang"]) ?>">
    </div>

    <div class="drawio-setting">
        <label for="offlineMode"><?php p($l->t("Activate offline mode in Draw.io?")) ?></label>
        <select id="offlineMode">
            <option value="yes"<?php if ($_["drawioOfflineMode"] === "yes") echo ' selected'; ?>><?php p($l->t("Yes")) ?></option>
            <option value="no"<?php if ($_["drawioOfflineMode"] === "no") echo ' selected'; ?>><?php p($l->t("No")) ?></option>
        </select>
        <p class="drawio-hint"><?php p($l->t("When the \"offline mode\" is active, this disables all remote operations and features to protect the users privacy. Draw.io will then also only be in English, even if you set a different language manually.")) ?></p>
    </div>

    <div class="drawio-setting">
        <label for="drawioAutosave"><?php p($l->t("Activate autosave?")) ?></label>
        <select id="drawioAutosave">
            <option value="yes"<?php if ($_["drawioAutosave"] === "yes") echo ' selected'; ?>><?php p($l->t("Yes")) ?></option>
            <option value="no"<?php if ($_["drawioAutosave"] === "no") echo ' selected'; ?>><?php p($l->t("No")) ?></option>
        </select>
        <p class="drawio-hint"><?php p($l->t("Note: To enable realtime collaboration, autosave must be active.")) ?></p>
    </div>

    <div class="drawio-setting">
        <label for="drawioLibraries"><?php p($l->t("Enable libraries?")) ?></label>
        <select id="drawioLibraries">
            <option value="yes"<?php if ($_["drawioLibraries"] === "yes") echo ' selected'; ?>><?php p($l->t("Yes")) ?></option>
            <option value="no"<?php if ($_["drawioLibraries"] === "no") echo ' selected'; ?>><?php p($l->t("No")) ?></option>
        </select>
    </div>

    <div class="drawio-setting">
        <label for="drawioPreviews"><?php p($l->t("Enable diagram previews?")) ?></label>
        <select id="drawioPreviews">
            <option value="yes"<?php if ($_["drawioPreviews"] === "yes") echo ' selected'; ?>><?php p($l->t("Yes")) ?></option>
            <option value="no"<?php if ($_["drawioPreviews"] === "no") echo ' selected'; ?>><?php p($l->t("No")) ?></option>
        </select>
        <p class="drawio-hint"><?php p($l->t("Note: Disable previews to save storage space used to store diagram preview images")) ?></p>
    </div>

    <div class="drawio-setting">
        <label for="drawioConfig"><?php p($l->t("draw.io Configuration")) ?></label>
        <textarea id="drawioConfig"><?php p($_["drawioConfig"]) ?></textarea>
    </div>

    <a id="drawioSave" class="button"><?php p($l->t("Save")) ?></a>
</div>
