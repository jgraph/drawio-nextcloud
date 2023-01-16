/**
 *
 * @author Pawel Rojek <pawel at pawelrojek.com>
 * @author Ian Reinhart Geiser <igeiser at devonit.com>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 **/

import { generateUrl, imagePath } from '@nextcloud/router'
import * as $ from 'jquery';
import { translate as t } from '@nextcloud/l10n'
import { showError } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'

// Some code is inspired by Mind Map app (https://github.com/ACTom/files_mindmap)

OCA.DrawIO = {
    AppName: 'drawio',
    frameSelector: null,
    AppSettings: null,
    Mimes: {
        'drawio': { 'mime': 'application/x-drawio', 'type': 'text', 'css': 'icon-drawio', 'icon': 'drawio', 'newStr': 'New Diagram' },
        'dwb': { 'mime': 'application/x-drawio-wb', 'type': 'text', 'css': 'icon-whiteboard', 'icon': 'dwb', 'newStr': 'New Whiteboard' }
    },

    OpenEditor: function (fileId, isWB)
    {
        var url = generateUrl('/apps/' + OCA.DrawIO.AppName + '/edit?fileId={fileId}&isWB=' + isWB, {
            fileId: fileId
        });
        window.location.href = url;
    },

    registerFileActions: function () 
    {
        function registerAction(ext, attr)
        {
            OCA.Files.fileActions.registerAction({
                name: 'drawioOpen' + ext,
                displayName: t(OCA.DrawIO.AppName, 'Open in Draw.io'),
                mime: attr.mime,
                permissions: OC.PERMISSION_READ | OC.PERMISSION_UPDATE,
                icon: function () 
                {
                    return imagePath(OCA.DrawIO.AppName, attr.icon);
                },
                iconClass: attr.css,
                actionHandler: function (fileName, context) 
                {
                    var fileInfoModel = context.fileInfoModel || context.fileList.getModelForFile(fileName);
                    OCA.DrawIO.OpenEditor(fileInfoModel.id, ext == 'dwb');
                }
            });

            OCA.Files.fileActions.setDefault(attr.mime, 'drawioOpen' + ext);
        }
        
        for (const ext in OCA.DrawIO.Mimes) 
        {
            registerAction(ext, OCA.DrawIO.Mimes[ext]);
        }
    },

    hackFileIcon: function() 
    {
		var changeDrawioIcons = function() 
        {
			$('#filestable')
			.find('tr[data-type=file]')
			.each(function () 
            {
                for (const ext in OCA.DrawIO.Mimes) 
                {
                    var attr = OCA.DrawIO.Mimes[ext];
                    if (($(this).attr('data-mime') == attr.mime) 
                        && ($(this).find('div.thumbnail').length > 0)) 
                    {
                        if ($(this).find('div.thumbnail').hasClass(attr.css) == false) {
                            $(this).find('div.thumbnail').addClass('icon ' + attr.css);
                        }
                    }
                }
			});
		}

		if ($('#filesApp').val()) 
        {
			$('#app-content-files')
			.add('#app-content-extstoragemounts')
			.on('changeDirectory', changeDrawioIcons)
			.on('fileActionsReady', changeDrawioIcons);
        }
	},

    CreateNewFile: async function (name, fileList, ext) 
    {
        var isWB = ext == 'dwb';
        var dir = fileList.getCurrentDirectory();
        var url = generateUrl('apps/' + OCA.DrawIO.AppName + '/ajax/new');

        try
        {
            var response = await axios.post(url, {
                name: name,
                dir: dir
            });

            if (response.status !== 200) 
            {
                console.log('Fetch error. Status Code: ' + response.status);
                showError(t(OCA.DrawIO.AppName, 'Error: Creating a new file failed.'), { timeout: 2500 });
                return;
            }

            fileList.add(response.data, { animate: true });
            OCA.DrawIO.OpenEditor(response.data.id, isWB);
        }
        catch(err) 
        {
            showError(t(OCA.DrawIO.AppName, 'Error: Creating a new file failed.'), { timeout: 2500 });
            console.log('Fetch Error: ', err);
        };
    },

    NewFileMenu: {
        attach: function (menu) 
        {
            var fileList = menu.fileList;

            if (fileList.id !== 'files') 
            {
                return;
            }

            function addMenuEntry(ext, attr)
            {
                menu.addMenuEntry({
                    id: 'drawIoDiagram_' + ext,
                    displayName: t(OCA.DrawIO.AppName, attr.newStr),
                    templateName: t(OCA.DrawIO.AppName, attr.newStr) + '.' + ext,
                    iconClass: attr.css,
                    fileType: attr.mime,
                    actionHandler: function (fileName) 
                    {
                        OCA.DrawIO.CreateNewFile(fileName, fileList, ext);
                    }
                });
            }

            for (const ext in OCA.DrawIO.Mimes) 
            {
                addMenuEntry(ext, OCA.DrawIO.Mimes[ext]);
            }
        }
    },

    init: async function () 
    {
        if ($('#isPublic').val() === '1' && !$('#filestable').length) 
        {
            var fileName = $('#filename').val();
            var mimeType = $('#mimetype').val();
            var sharingToken = $('#sharingToken').val();
            var extension = fileName.substr(fileName.lastIndexOf('.') + 1).toLowerCase();
            var isWB = String(extension == 'dwb');

            if (!OCA.DrawIO.Mimes[extension] || OCA.DrawIO.Mimes[extension].mime != mimeType)
            {
                return;
            }

            var button = document.createElement('a');
            button.href = generateUrl('apps/' + OCA.DrawIO.AppName + '/edit?shareToken={shareToken}&isWB={isWB}&lightbox=true', {
                shareToken: sharingToken,
                isWB: isWB
            });
            button.className = 'button';
            button.innerText = t(OCA.DrawIO.AppName, 'Open in Draw.io');
            $('#preview').append(button);

            // If the file is editable, add a button to edit it
            var url = generateUrl('/apps/' + OCA.DrawIO.AppName + '/ajax/getFileInfo?shareToken={shareToken}', 
            {
                shareToken: sharingToken
            });
                
            var response = await axios.get(url);

            if (response.status == 200 && response.data.writeable) 
            {
                var editButton = document.createElement('a');
                editButton.href = generateUrl('apps/' + OCA.DrawIO.AppName + '/edit?shareToken={shareToken}&isWB={isWB}', {
                    shareToken: sharingToken,
                    isWB: isWB
                });
                editButton.className = 'button';
                editButton.innerText = t(OCA.DrawIO.AppName, 'Edit in Draw.io');
                $('#preview').append(editButton);
            }
        }
        else
        {
            OCA.DrawIO.registerFileActions();
            OCA.DrawIO.hackFileIcon();
        }
    }
};

OC.Plugins.register('OCA.Files.NewFileMenu', OCA.DrawIO.NewFileMenu);

$(function () 
{
    OCA.DrawIO.init();
});