/**
 *
 * @author Pawel Rojek <pawel at pawelrojek.com>
 * @author Ian Reinhart Geiser <igeiser at devonit.com>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 **/

import { generateUrl } from '@nextcloud/router'
import { getSharingToken, isPublicShare } from '@nextcloud/sharing/public'
import * as $ from 'jquery';
import { translate as t } from '@nextcloud/l10n'
import { showError } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import {
	DefaultType,
	FileAction,
	addNewFileMenuEntry,
	registerFileAction,
	File,
	Permission,
	getNavigation,
} from '@nextcloud/files'
import { getCurrentUser } from '@nextcloud/auth'
import { emit } from '@nextcloud/event-bus'

// Some code is inspired by Mind Map app (https://github.com/ACTom/files_mindmap)

OCA.DrawIO = {
    AppName: 'drawio',
    frameSelector: null,
    AppSettings: null,
    Mimes: {
        'drawio': { 'mime': 'application/x-drawio', 'type': 'text', 'css': 'icon-drawio',
            'icon': '<svg xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:cc="http://creativecommons.org/ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" enable-background="new 0 0 306.185 120.296" viewBox="24 26 68 68" y="0px" x="0px" version="1.1"><g><circle r="1e5" fill="#F08705"/><line y2="72.394" x2="41.061" y1="43.384" x1="58.069" stroke-miterlimit="10" stroke-width="3.5528" stroke="#FFFFFF" fill="none" /><line y2="72.394" x2="75.076" y1="43.384" x1="58.068" stroke-miterlimit="10" stroke-width="3.5008" stroke="#FFFFFF" fill="none" /><g><path d="M52.773,77.084c0,1.954-1.599,3.553-3.553,3.553H36.999c-1.954,0-3.553-1.599-3.553-3.553v-9.379    c0-1.954,1.599-3.553,3.553-3.553h12.222c1.954,0,3.553,1.599,3.553,3.553V77.084z" fill="#FFFFFF" /></g><g id="g3419"><path d="M67.762,48.074c0,1.954-1.599,3.553-3.553,3.553H51.988c-1.954,0-3.553-1.599-3.553-3.553v-9.379    c0-1.954,1.599-3.553,3.553-3.553H64.21c1.954,0,3.553,1.599,3.553,3.553V48.074z" fill="#FFFFFF" /></g><g><path d="M82.752,77.084c0,1.954-1.599,3.553-3.553,3.553H66.977c-1.954,0-3.553-1.599-3.553-3.553v-9.379    c0-1.954,1.599-3.553,3.553-3.553h12.222c1.954,0,3.553,1.599,3.553,3.553V77.084z" fill="#FFFFFF" /></g></g></svg>',
            'newStr': t('drawio', 'New draw.io Diagram') },
        'dwb': { 'mime': 'application/x-drawio-wb', 'type': 'text', 'css': 'icon-whiteboard',
            'icon': '<svg xmlns="http://www.w3.org/2000/svg" width="54" height="54" viewBox="-0.5 -0.5 54 54" xmlns:v="https://vecta.io/nano"><g fill="none" stroke-linejoin="round" stroke-linecap="round" stroke-miterlimit="10"><path d="M.13 5.37C1.72 3.97 2.21 3.11 4.07.84M.13 5.37L4.07.84M.43 9.6C2.41 7.3 5.36 5.24 8.3.54M.43 9.6C3.49 6.28 6.23 2.43 8.3.54M.07 14.59C6.73 7.46 10.47 2.58 12.53.25M.07 14.59C4 11.76 6.2 8.3 12.53.25M.36 18.82C6.35 13.52 9.18 9.54 16.11.71M.36 18.82C5.94 11.38 12.69 4.64 16.11.71M.66 23.05C6.22 14.62 12.48 7.49 20.34.41M.66 23.05C6.91 16.65 12.94 7.86 20.34.41M.3 28.04C4.67 22 11.49 17.08 23.92.87M.3 28.04C5.84 21.41 12.07 13.76 23.92.87M.59 32.27C12.11 19.89 21.81 8.34 28.15.57M.59 32.27C8.66 22.64 17.5 12.62 28.15.57M.23 37.26C11.69 23.4 22.58 8.96 32.38.28M.23 37.26C7.55 27.76 16.74 18.73 32.38.28M.53 41.49C8.98 30.98 17.99 21.59 35.96.73M.53 41.49C9.56 31.34 18.2 22.88 35.96.73M.17 46.48C10.4 35.51 17.02 24.5 40.19.44M.17 46.48C11.92 33.21 21.72 20.76 40.19.44M.47 50.71L44.42.14M.47 50.71C16.62 34.32 30.79 16.99 44.42.14M2.07 53.43L48 .6M2.07 53.43C16.63 37.37 31.93 21.04 48 .6M6.31 53.14C23.49 36.87 39.08 18.69 52.23.31M6.31 53.14C16.91 42.09 25.82 31.07 52.23.31M9.88 53.59c13.65-13.91 25.59-29.13 41.99-48.3M9.88 53.59c9.41-11.64 19.53-22.81 41.99-48.3M14.11 53.3C24.82 40.44 37.15 29.26 52.17 9.53M14.11 53.3C26.18 40.16 36.23 27.53 52.17 9.53M18.35 53c9.69-13.96 23.09-25.78 33.46-38.49M18.35 53c9.29-10.17 18.47-21.44 33.46-38.49M21.92 53.46c9.95-13.37 24.75-27.29 30.18-34.72M21.92 53.46c6.57-9.16 15.43-16.22 30.18-34.72M26.16 53.17c6.87-6.37 13.92-16.36 26.24-30.19M26.16 53.17c6.98-7.04 12.21-14.7 26.24-30.19M29.73 53.62c6.36-7.08 9.31-12.8 22.31-25.66M29.73 53.62c6.22-6.53 13.09-14.99 22.31-25.66M33.96 53.33c4.84-6.31 11.29-15.96 18.37-21.13M33.96 53.33c6.17-7.82 11.8-13.57 18.37-21.13M37.54 53.79c4.41-3 9.62-9.16 14.43-16.61M37.54 53.79c3.51-3.64 7.73-9.41 14.43-16.61m-10.2 16.31c2.66-4.32 4.79-6.07 10.5-12.07m-10.5 12.07c2.22-3.22 4.17-5.03 10.5-12.07m-6.92 12.53c3.3-2.18 3.54-5.92 7.22-8.3m-7.22 8.3c1.25-1.68 3.48-3.85 7.22-8.3" stroke="#f08707" stroke-width="1.7" pointer-events="all"/><path d="M52.2 50.52c.1.11.61.52-1.54 1.6m1.54-1.6c.77.1-2.17-.34-1.54 1.6m0 0c-17.81-1.38-34.9-.22-49.27 0m49.27 0c-15.97 1-32.21.69-49.27 0m0 0C.01 51.51.01 50.51 0 50.72m1.39 1.4c.68-.59-2.4-1.85-1.39-1.4m0 0C-.71 40.58.69 30.53 0 1.71m0 49.01C.43 34.96.67 20.35 0 1.71m0 0C.9.97-.39 1.38 1.52 0M0 1.71c-.64.6.41.47 1.52-1.71m0 0c19.81-.23 35.31 1.02 49.45.04M1.52 0c14.11 1.29 26.61.53 49.45.04m0 0c2.22-1.35 1.39.35 1.23 1.56M50.97.04c-1.08-2.23 2.27-.27 1.23 1.56m0 0c-1.39 13.03-.88 26.57 0 48.92m0-48.92c.21 13.99-.53 28.22 0 48.92" stroke="#000" pointer-events="all"/></g><path d="M13.39 38.48l-4.91-1.35.28-5.94 7.63-.14 4.92-7.51-.93-2.9.93-7.25 9.79-1.83 1.14 10.08-2.52-.21 6.07 8.91 6.25-.58.7 7.99-11.87 1.49-2.6-8.94 3.43-.6-3.85-6.2-3.78-.6-2.6 8.22 2.28.19-.84 6.58-9 3.09" fill="#fff" pointer-events="all"/><path d="M12.12 40.18c-2.93 1.97-3.6-.24-1.87-1.9m1.87 1.9c-2.42-1.15-1-.34-1.87-1.9m0 0c.6-2.88-.57-5.1 0-6.84m0 6.84v-6.84m0 0c-.88-1.63 1.22-2.32 1.52-1.84m-1.52 1.84c.46-.15.45-2.61 1.52-1.84m0 0c1.36.3 3.1.14 5.38 0m-5.38 0c1.87.24 4.38-.04 5.38 0m0 0c1.07-2.33 3.15-5.44 4.8-7.95m-4.8 7.95l4.8-7.95m0 0c-.15.02-.34-.01-.64 0m.64 0c-.15.01-.37-.01-.64 0m0 0c-.76-1.24-2.69-1.85-1.51-1.53m1.51 1.53c.35.61-2.47-1.62-1.51-1.53m0 0c.49-3.58.09-6.13 0-7.31m0 7.31c.23-2.76.25-5.05 0-7.31m0 0c1.21 1.14-.55-3.59 1.79-1.86m-1.79 1.86c-.68.28.01-3.34 1.79-1.86m0 0c2.06.4 3.36-.7 8.7 0m-8.7 0c2.85-.25 5.37.17 8.7 0m0 0c-.53.68.2 2.13 2.08 1.94m-2.08-1.94c3.09 2.01 3.2 1.44 2.08 1.94m0 0c-.17 2.73-.21 6.08 0 7.02m0-7.02c-.12 2.3.17 4.64 0 7.02m0 0c.8-.33-2.49 3-1.5 1.74m1.5-1.74c1.5 2.24.51-.15-1.5 1.74m0 0h-.62m.62 0a3.21 3.21 0 0 0-.62 0m0 0c1.51 3.87 3.03 5.89 4.81 7.95m-4.81-7.95l4.81 7.95m0 0c1.59-.43 3.37.26 5.13 0m-5.13 0h5.13m0 0c2.57 1.19 2.4 2.02 1.8 1.66m-1.8-1.66c1.72.12 4.02 1.2 1.8 1.66m0 0c.24 2.83-.45 5.28 0 7.03m0-7.03c.14 2.33-.18 4.02 0 7.03m0 0c-1.7.86.55 1.74-1.82 1.89m1.82-1.89c-.54-.08 1.12 1.97-1.82 1.89m0 0c-2.81-.63-6.82-.9-8.64 0m8.64 0c-3.25-.17-6.27.34-8.64 0m0 0c-1.17 1.4-.63-2.14-2.07-1.8m2.07 1.8c-.19.04-3.99-2.32-2.07-1.8m0 0c.29-2.4.52-4.91 0-7.38m0 7.38c-.1-1.78-.2-3.35 0-7.38m0 0c1.42 1.22.36-2.1 1.67-1.4M29.46 31c.31.24.14.49 1.67-1.4m0 0c.66-.16.99-.1 1.62 0m-1.62 0c.33.05.76-.04 1.62 0m0 0c-1.16-1.21-1.3-3.29-4.63-7.95m4.63 7.95c-.54-1.42-2.34-2.99-4.63-7.95m0 0c-1.25.3-2.44.13-3.98 0m3.98 0c-.95.14-1.99-.05-3.98 0m0 0c-.64 2.57-1.35 3.87-4.6 7.95m4.6-7.95l-4.6 7.95m0 0c.55-.15 1.23-.14 1.77 0m-1.77 0h1.77m0 0c2.26-.37 3.4 2.34 1.44 1.63m-1.44-1.63c1.8.2.43 2.35 1.44 1.63m0 0c.76 2.5.25 4.04 0 7.48m0-7.48c.17 2.86-.06 5.56 0 7.48m0 0c1.3 2.22-1.32 1.52-1.52 1.47m1.52-1.47c1.7-.92-.52 1.09-1.52 1.47m0 0c-2.39-.25-4.74-.05-9.11 0m9.11 0c-2.2-.09-5.01.02-9.11 0" fill="none" stroke="#000" stroke-linejoin="round" stroke-linecap="round" stroke-miterlimit="10" pointer-events="all"/></svg>',
            'newStr': t('drawio', 'New draw.io Whiteboard') }
    },

    OpenEditor: function (fileId, isWB)
    {
        var shareToken = getSharingToken();
        var url = generateUrl('/apps/' + OCA.DrawIO.AppName + '/edit?' + 
                (fileId? 'fileId={fileId}' : '') + 
                (shareToken? '&shareToken={shareToken}' : '') +
                '&isWB=' + isWB, 
        {
            fileId: fileId,
            shareToken: shareToken
        });
        window.location.href = url;
    },

    registerFileActions: function () 
    {
        function registerAction(ext, attr)
        {
            registerFileAction(new FileAction({
                id: 'drawioOpen' + ext,
                displayName() {
                    t(OCA.DrawIO.AppName, 'Open in Draw.io')
                },
                enabled(nodes) {
                    return nodes.length === 1 && attr.mime === nodes[0].mime && (nodes[0].permissions & OC.PERMISSION_READ) !== 0
                },
                iconSvgInline: () => attr.icon,
                async exec(node, view) {
                    OCA.DrawIO.OpenEditor(node.fileid, ext == 'dwb');
                    return true;
                },
                default: DefaultType.HIDDEN
            }));
        }
        
        for (const ext in OCA.DrawIO.Mimes) 
        {
            registerAction(ext, OCA.DrawIO.Mimes[ext]);
        }
    },

    CreateNewFile: async function (name, folder, ext, mime) 
    {
        var isWB = ext == 'dwb';
        var url = generateUrl('apps/' + OCA.DrawIO.AppName + '/ajax/new');

        try
        {
            var response = await axios.post(url, {
                name: name,
                dirId: folder.fileid,
                shareToken: getSharingToken()
            });

            if (response.status !== 200) 
            {
                console.log('Fetch error. Status Code: ' + response.status);
                showError(t(OCA.DrawIO.AppName, 'Error: Creating a new file failed.'), { timeout: 2500 });
                return;
            }

            const file = new File({
				source: folder.source + '/' + name,
				id: response.data.id,
				mtime: new Date(),
				mime: mime,
				owner: getCurrentUser()?.uid || null,
				permissions: Permission.ALL,
				root: folder?.root || '/files/' + getCurrentUser()?.uid,
			})

			emit('files:node:created', file)
            OCA.DrawIO.OpenEditor(response.data.id, isWB);
        }
        catch(err) 
        {
            showError(t(OCA.DrawIO.AppName, 'Error: Creating a new file failed.'), { timeout: 2500 });
            console.log('Fetch Error: ', err);
        };
    },

    registerNewFileMenuPlugin: () => {
        function getUniqueName(name, ext, names) 
        {
            let newName;

            do
            {
                newName = name + '-' + Math.round(Math.random() * 1000000) + '.' + ext;
            }
            while (names.includes(newName)) 
            
            return newName;
        }

        function addMenuEntry(ext, attr)
        {
            addNewFileMenuEntry({
                id: 'drawIoDiagram_' + ext,
                displayName: attr.newStr,
                enabled(node) {
                    return (node.permissions & OC.PERMISSION_CREATE) !== 0;
                },
                iconClass: attr.css,
                async handler(context, content)
                {
                    const contentNames = content.map((node) => node.basename);
                    const fileName = getUniqueName(attr.newStr, ext, contentNames);
                    OCA.DrawIO.CreateNewFile(fileName, context, ext, attr.mime);
                }
            });
        }

        for (const ext in OCA.DrawIO.Mimes) 
        {
            addMenuEntry(ext, OCA.DrawIO.Mimes[ext]);
        }
    },

    init: async function () 
    {
        OCA.DrawIO.registerNewFileMenuPlugin();
        OCA.DrawIO.registerFileActions();
    }
};

$(function () 
{
    OCA.DrawIO.init();
});
