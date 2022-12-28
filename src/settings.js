/**
 *
 * @author Pawel Rojek <pawel at pawelrojek.com>
 * @author Ian Reinhart Geiser <igeiser at devonit.com>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 **/

import { generateUrl } from '@nextcloud/router'
import * as $ from 'jquery';
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import { showInfo, showSuccess, showError } from '@nextcloud/dialogs'
import '@nextcloud/dialogs/styles/toast.scss'

$(function () {
    OCA.DrawIO = OCA.DrawIO || {};
    if (!OCA.DrawIO.AppName)    
    {
        OCA.DrawIO = {
            AppName: 'drawio'
        };
    }

    $('#drawioSave').click(async function ()
    {
        var f_drawioUrl = $('#drawioUrl').val().trim();
        var f_offlineMode = $('#offlineMode option:selected').val();
        var f_theme = $('#theme option:selected').val();
        var f_lang = $('#lang').val().trim();
        var f_autosave = $('#drawioAutosave option:selected').val();
        var f_libraries = $('#drawioLibraries option:selected').val();
        var f_darkMode = $('#darkMode option:selected').val();
        var f_previews = $('#drawioPreviews option:selected').val();

        var saving = showInfo(t(OCA.DrawIO.AppName, 'Saving...'));

        var settings = {
            drawioUrl: f_drawioUrl,
            offlineMode: f_offlineMode,
            theme: f_theme,
            lang: f_lang,
            autosave: f_autosave,
            libraries: f_libraries,
            darkMode: f_darkMode,
            previews: f_previews
        };

        const params = new URLSearchParams();

        for (var key in settings) 
        {
            params.append(key, settings[key]);
        }

        var response = await axios.post(generateUrl('apps/'+ OCA.DrawIO.AppName + '/ajax/settings'), params);
        saving.hideToast();

        if (response.status == 200)
        {
            showSuccess(t(OCA.DrawIO.AppName, 'Settings have been successfully saved'), { timeout: 2500 });
        }
        else
        {
            showError(t(OCA.DrawIO.AppName, 'Error when trying to connect') + ' (' + response.data + ')', { timeout: 2500 });
        }
    });

    $('#drawioUrl, #lang').keypress(function (e)
    {
        var code = e.keyCode || e.which;
        if (code === 13) $('#drawioSave').click();
    });
});
