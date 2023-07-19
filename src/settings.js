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
import '@nextcloud/dialogs/dist/index.css'

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
        var f_lang = $('#lang').val();
        var f_autosave = $('#drawioAutosave option:selected').val();
        var f_libraries = $('#drawioLibraries option:selected').val();
        var f_darkMode = $('#darkMode option:selected').val();
        var f_previews = $('#drawioPreviews option:selected').val();
        var f_drawioConfig = $('#drawioConfig').val().trim();

        if (f_drawioConfig)
        {
            try
            {
                var tmp = JSON.parse(f_drawioConfig);
                f_drawioConfig = JSON.stringify(tmp); // convert to a single line
            }
            catch (e)
            {
                showError(t(OCA.DrawIO.AppName, 'draw.io Configuration error:') + ' ' + e.message, { timeout: 2500 });
                return;
            }
        }

        var saving = showInfo(t(OCA.DrawIO.AppName, 'Saving...'));

        var settings = {
            drawioUrl: f_drawioUrl,
            offlineMode: f_offlineMode,
            theme: f_theme,
            lang: f_lang,
            autosave: f_autosave,
            libraries: f_libraries,
            darkMode: f_darkMode,
            previews: f_previews,
            drawioConfig: f_drawioConfig
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

    var mxLanguageMap =
    {
        'id' : 'Bahasa Indonesia',
        'ms' : 'Bahasa Melayu',
        'bs' : 'Bosanski',
        'bg' : 'Bulgarian',
        'ca' : 'Català',
        'cs' : 'Čeština',
        'da' : 'Dansk',
        'de' : 'Deutsch',
        'et' : 'Eesti',
        'en' : 'English',
        'es' : 'Español',
        'eu' : 'Euskara',
        'fil' : 'Filipino',
        'fr' : 'Français',
        'gl' : 'Galego',
        'it' : 'Italiano',
        'hu' : 'Magyar',
        'lt' : 'Lietuvių',
        'lv' : 'Latviešu',
        'nl' : 'Nederlands',
        'no' : 'Norsk',
        'pl' : 'Polski',
        'pt-br' : 'Português (Brasil)',
        'pt' : 'Português (Portugal)',
        'ro' : 'Română',
        'fi' : 'Suomi',
        'sv' : 'Svenska',
        'vi' : 'Tiếng Việt',
        'tr' : 'Türkçe',
        'el' : 'Ελληνικά',
        'ru' : 'Русский',
        'sr' : 'Српски',
        'uk' : 'Українська',
        'he' : 'עברית',
        'ar' : 'العربية',
        'fa' : 'فارسی',
        'th' : 'ไทย',
        'ko' : '한국어',
        'ja' : '日本語',
        'zh' : '简体中文',
        'zh-tw' : '繁體中文'
    };

    var curLang = $('#curLang').val();
    var langSelect = document.getElementById('lang');

    function addLang(key, name)
    {
        var option = document.createElement('option');
        option.setAttribute('value', key);
        option.innerHTML = name;

        if (curLang == key)
        {
            option.setAttribute('selected', 'selected');
        }
        
        langSelect.appendChild(option);
    }

    addLang('auto', t(OCA.DrawIO.AppName, 'Auto'));

    for (var key in mxLanguageMap)
    {
        addLang(key, mxLanguageMap[key]);
    }

    try
    {
        $('#drawioConfig').val(JSON.stringify(JSON.parse($('#drawioConfig').val()), null, 2));
    }
    catch (e){}
});
