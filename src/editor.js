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
import { getCurrentUser } from '@nextcloud/auth'
import { showInfo, showError } from '@nextcloud/dialogs'
import '@nextcloud/dialogs/style.css';

(function (OCA) {

    // ADD SUPPORT TO IE
    if (!String.prototype.includes) {
        String.prototype.includes = function(search, start) {
            if (typeof start !== 'number') {
                start = 0;
            }
            if (start + search.length > this.length) {
                return false;
            } else {
                return this.indexOf(search, start) !== -1;
            }
        };
    }

    OCA.DrawIO = OCA.DrawIO || {}
    
    if (!OCA.DrawIO.AppName) 
    {
        OCA.DrawIO = {
            AppName: 'drawio'
        };
    }

    OCA.DrawIO.DisplayError = function (error) 
    {
        $('#app-content').text(error).addClass('error');
    };

    // fileOp: 1 = load, 2 = getInfo, 3 = getFileRevisions, 4 = loadFileVer, 5 = save
    async function fileOpInt(params, fileOp, success, error)
    {
        try
        {
            var response = null;

            if (fileOp < 5)
            {
                var op = null;

                switch (fileOp)
                {
                    case 1:
                        op = 'load';
                        break;
                    case 2:
                        op = 'getFileInfo';
                        break;
                    case 3:
                        op = 'getFileRevisions';
                        break;
                    case 4:
                        op = 'loadFileVersion';
                        break;
                }

                var url = generateUrl('/apps/' + OCA.DrawIO.AppName + '/ajax/' + op + 
                    '?fileId={fileId}' + (fileOp < 3? '&shareToken={shareToken}' : '') + 
                    (fileOp == 4? '&revId={revId}' : ''), params);
                
                response = await axios.get(url);
            }
            else if (fileOp == 5)
            {
                var url = generateUrl('/apps/' + OCA.DrawIO.AppName + '/ajax/save');
                
                response = await axios.put(url, params);
            }
            else if (fileOp == 6)
            {
                var url = generateUrl('/apps/' + OCA.DrawIO.AppName + '/ajax/savePreview');
                
                response = await axios.post(url, params);
            }

            if (response.status == 200)
            {
                if (success)
                {
                    success(response.data);
                }
                else
                {
                    return response.data;
                }
            }
            else
            {
                if (error)
                {
                    error(response);
                }
                else
                {
                    throw new Error(response);
                }
            }
        }
        catch (err)
        {
            if (error)
            {
                error(err);
            }
            else
            {
                throw err;
            }
        }
    };

    OCA.DrawIO.loadFile = async function (fileId, shareToken, success, error)
    {
        return await fileOpInt({fileId, shareToken}, 1, success, error);
    };

    OCA.DrawIO.getFileInfo = async function (fileId, shareToken, success, error)
    {
        return await fileOpInt({fileId, shareToken}, 2, success, error);
    };

    OCA.DrawIO.getFileRevisions = async function (fileId, success, error)
    {
        return await fileOpInt({fileId}, 3, success, error);
    };

    OCA.DrawIO.loadFileVersion = async function (fileId, revId, success, error)
    {
        return await fileOpInt({fileId, revId}, 4, success, error);
    };

    OCA.DrawIO.saveFile = async function (fileId, shareToken, fileContents, etag, success, error)
    {
        OCA.DrawIO.fileSaved = true;
        return await fileOpInt({fileId, shareToken, fileContents, etag}, 5, success, error);
    };

    OCA.DrawIO.savePreview = async function (fileId, shareToken, previewContents, success, error)
    {
        return await fileOpInt({fileId, shareToken, previewContents}, 6, success, error);
    };

    OCA.DrawIO.getCurrentUser = function ()
    {
        OCA.DrawIO.pluginLoaded = true;
        return getCurrentUser();
    }
    
    OCA.DrawIO.Cleanup = async function (receiver, fileId, shareToken, delayed) 
    {
        if (delayed)
        {
            await new Promise(r => setTimeout(r, 5000));
        }

        window.removeEventListener('message', receiver);

        try
        {
            var data = await OCA.DrawIO.getFileInfo(fileId, shareToken);
            var filePath = data.path, url;

            if (filePath)
            {
                const matches = [...filePath.matchAll(/\/\.attachments\.(\d+)\//g)];
                if (matches.length) {
                    const match = matches[matches.length - 1];
                    url = generateUrl('/apps/files/files/{fileId}?dir={currentDirectory}&openfile=true', {
                        currentDirectory: filePath.substring(0, filePath.lastIndexOf(match[0])),
                        fileId: match[1],
                    });
                } else {
                    url = generateUrl('/apps/files/?dir={currentDirectory}', {
                        currentDirectory: filePath.substring(0, filePath.lastIndexOf('/')),
                        fileId: data.id
                    });
                }
            }
            else // ShareToken case
            {
                url = generateUrl('/s/{shareToken}', data);
            }

            window.location.href = url;
        }
        catch (error)
        {
            console.log(error);
            var url = generateUrl('/apps/files');
            window.location.href = url;
        }
    };

    OCA.DrawIO.EditFile = function (editWindow, origin, autosave, isWB, previews, configObj) 
    {
        var autosaveEnabled = autosave;
        var fileId = $('#iframeEditor').data('id');
        var shareToken = $('#iframeEditor').data('sharetoken');
        var currentFile = null;

        if (!fileId && !shareToken)
        {
            OCA.DrawIO.DisplayError(t(OCA.DrawIO.AppName, 'FileId is empty'));
            return;
        }

        function startEditor() 
        {
            var receiver = async function (evt) 
            {
                if (evt.data.length > 0 && origin.includes(evt.origin)) 
                {
                    var payload = JSON.parse(evt.data);

                    if (payload.event === 'template') 
                    {
                        // Not used
                    }
                    else if (payload.event === 'load')
                    {
                        if (!isWB && OCA.DrawIO.NewFileMode) 
                        {
                            editWindow.postMessage(JSON.stringify({
                                    action: 'template'
                            }), '*');
                        }
                    }
                    else if (payload.event === 'export')
                    {
                        // Save preview and exit
                        var imageData = payload.data.substring(payload.data.indexOf(',') + 1);
                        editWindow.postMessage(JSON.stringify({action: 'spinner',
                            show: true, messageKey: 'updatingPreview'}), '*');
                        await OCA.DrawIO.savePreview(fileId, shareToken, imageData);
                        OCA.DrawIO.Cleanup(receiver, fileId, shareToken);
                    } 
                    else if (payload.event === 'autosave' || payload.event === 'save')
                    {
                        if (!OCA.DrawIO.pluginLoaded)
                        {
                            try
                            {
                                var resp = await OCA.DrawIO.saveFile(fileId, shareToken, payload.xml, currentFile.etag);
                                currentFile.etag = resp.etag;

                                editWindow.postMessage(JSON.stringify({
                                    action: 'status',
                                    message: (payload.event === 'save'? 'Saved' : 'Autosaved') + 
                                        ' successfully at ' + (new Date()).toLocaleTimeString(),
                                    modified: false
                                }), '*');
                            }
                            catch (error)
                            {
                                console.log(error);
                                var errMsg = 'Error: ' + (error.response && error.response.data ? 
                                                error.response.data.message : error.message) + 
                                                '\nUse Export to save changes';
                                showError(errMsg, { timeout: 2500 });
                                editWindow.postMessage(JSON.stringify({
                                    action: 'status',
                                    message: errMsg,
                                    modified: true
                                }), '*');
                            }
                        }
                    }
                    else if (payload.event === 'exit')
                    {
                        // Generate preview image on exit
                        if (previews && OCA.DrawIO.fileSaved)
                        {
                            editWindow.postMessage(JSON.stringify({action: 'export',
                                    format: 'png', spinKey: 'updatingPreview', scale: 1}), '*');
                        }
                        else
                        {
                            OCA.DrawIO.Cleanup(receiver, fileId, shareToken);
                        }
                    }
                    else if (payload.event == 'remoteInvoke')
                    {
                        OCA.DrawIO.handleRemoteInvoke(payload);
                    }
                    else if (payload.event == 'remoteInvokeResponse')
                    {
                        OCA.DrawIO.handleRemoteInvokeResponse(payload);
                    }
                    else
                    {
                        console.log('DrawIO Integration: unknown event ' + payload.event);
                        console.dir(payload);
                    }
                }
                else 
                {
                    console.log('DrawIO Integration: bad origin ' + evt.origin);
                }
            }

            window.addEventListener('message', receiver);
            editWindow.postMessage(JSON.stringify({action: 'remoteInvokeReady'}), '*');
            OCA.DrawIO.remoteWin = editWindow;
        };

        var loadMsg = showInfo(t(OCA.DrawIO.AppName, 'Loading, please wait.'));

        var initHandler = async function(evt)
        {
            if (evt.data.length > 0 && origin.includes(evt.origin))
            {
                var msg;
                
                try
                {
                    msg = JSON.parse(evt.data);
                }
                catch (e)
                {
                    msg = {}; //Ignore this message
                }
                
                if (msg.event == 'configure')
                {
                    // Configure must be sent even if JSON invalid
                    configObj = configObj || {};
                    editWindow.postMessage(JSON.stringify({action: 'configure',
                        config: configObj}), '*');
                }
                else if (msg.event == 'init')
                {
                    try
                    {
                        var data = await OCA.DrawIO.loadFile(fileId, shareToken);

                        var contents = data.xml;
                        currentFile = data;
                        delete currentFile.xml;

                        //[workaround] 'loading' file without content, to display 'template' later in 'load' callback event without another filename prompt
                        if (contents === ' ') 
                        {
                            OCA.DrawIO.NewFileMode = true;

                            // Whiteboard must have a valid content since no templates are shown (#59)
                            if (isWB)
                            {
                                // Empty diagram XML
                                contents = '<mxGraphModel><root><mxCell id="0"/><mxCell id="1" parent="0"/></root></mxGraphModel>'; 
                            }
                        }
                        
                        editWindow.postMessage(JSON.stringify({
                            action: 'load',
                            autosave: autosaveEnabled, title: currentFile.name,
                            xml: contents,
                            desc: currentFile, disableAutoSave: !autosaveEnabled
                        }), '*');

                        window.removeEventListener('message', initHandler);
                        document.body.style.backgroundImage = 'none';
                        startEditor();
                    }
                    catch (error)
                    {
                        showError(t(OCA.DrawIO.AppName, 'Error loading the file') + ' (' + (error.data || error.message) + ')', { timeout: 2500 });
                        console.log('Status Error: ' + error.status);
                        OCA.DrawIO.Cleanup(initHandler, fileId, shareToken, true);
                    }
                    finally
                    {
                        loadMsg.hideToast();
                    }
                }
            }
        };

        window.addEventListener('message', initHandler);
    }

    //White-listed functions and some info about it
    OCA.DrawIO.remoteInvokableFns = {
        getFileInfo: {isAsync: true},
        loadFile: {isAsync: true},
        saveFile: {isAsync: true},
        getFileRevisions: {isAsync: true},
        loadFileVersion: {isAsync: true},
        getCurrentUser: {isAsync: false}
    };

    OCA.DrawIO.remoteInvokeCallbacks = [];

    OCA.DrawIO.handleRemoteInvokeResponse = function(msg)
    {
        var msgMarkers = msg.msgMarkers;
        var callback = OCA.DrawIO.remoteInvokeCallbacks[msgMarkers.callbackId];
        
        if (msg.error)
        {
            if (callback.error) callback.error(msg.error.errResp);
        }
        else if (callback.callback)
        {
            callback.callback.apply(this, msg.resp);
        }
        
        OCA.DrawIO.remoteInvokeCallbacks[msgMarkers.callbackId] = null; //set it to null only to keep the index
    };

    //Here, the editor is ready before sending init even which starts everything, so no need for waiting for ready message. Init is enough
    OCA.DrawIO.remoteInvoke = function(remoteFn, remoteFnArgs, msgMarkers, callback, error)
    {
        msgMarkers = msgMarkers || {};
        msgMarkers.callbackId = OCA.DrawIO.remoteInvokeCallbacks.length;
        OCA.DrawIO.remoteInvokeCallbacks.push({callback: callback, error: error});
        OCA.DrawIO.remoteWin.postMessage(JSON.stringify({action: 'remoteInvoke', funtionName: remoteFn, functionArgs: remoteFnArgs, msgMarkers: msgMarkers}), '*');
    };

    OCA.DrawIO.handleRemoteInvoke = function(msg)
    {
        function sendResponse(resp, error)
        {
            var respMsg = {action: 'remoteInvokeResponse', msgMarkers: msg.msgMarkers};
            
            if (error != null)
            {
                respMsg.error = {errResp: error};
            }
            else if (resp != null) 
            {
                respMsg.resp = resp;
            }
            
            OCA.DrawIO.remoteWin.postMessage(JSON.stringify(respMsg), '*');
        }
        
        try
        {
            //Remote invoke are allowed to call functions in DrawIO
            var funtionName = msg.funtionName;
            var functionInfo = OCA.DrawIO.remoteInvokableFns[funtionName];
            
            if (functionInfo != null && typeof OCA.DrawIO[funtionName] === 'function')
            {
                var functionArgs = msg.functionArgs;
                
                //Confirm functionArgs are not null and is array, otherwise, discard it
                if (!Array.isArray(functionArgs))
                {
                    functionArgs = [];
                }
                
                //for functions with callbacks (async) we assume last two arguments are success, error
                if (functionInfo.isAsync)
                {
                    //success
                    functionArgs.push(function() 
                    {
                        sendResponse(Array.prototype.slice.apply(arguments));
                    });
                    
                    //error
                    functionArgs.push(function(err) 
                    {
                        sendResponse(null, err || 'Unknown Error');
                    });
                    
                    OCA.DrawIO[funtionName].apply(this, functionArgs);
                }
                else
                {
                    var resp = OCA.DrawIO[funtionName].apply(this, functionArgs);
                    
                    sendResponse([resp]);
                }
            }
            else
            {
                sendResponse(null, 'Invalid Call. Function "' + funtionName + '" Not Found.');
            }
        }
        catch(e)
        {
            sendResponse(null, 'Invalid Call. Error Occured: ' + e.message);
            console.log(e);
        }
    };

})(OCA);

$(async function () {
    var drawioData = JSON.parse(atob($('#drawioData').text()));

    if (drawioData['error'])
    {
        OCA.DrawIO.DisplayError(drawioData['error']);
    }
    else
    {
        var iframe = document.getElementById('iframeEditor');
        var originUrl = drawioData['drawioUrl'];
        var drawIoUrl = drawioData['drawioUrl'] + drawioData['frame_params'];
        var autosave = drawioData['finalAutosave'] == 'yes';
        var isWB = drawioData['isWB'] == 'true';
        var previews = drawioData['drawioPreviews'] == 'yes';

        if (drawioData['drawioDarkMode'] == 'auto')
        {
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
        }

        var config = {};

        try
        {
            config = JSON.parse(drawioData['drawioConfig']);
        }
        catch (e){}

        function startEditor()
        {
            OCA.DrawIO.EditFile(iframe.contentWindow, originUrl, autosave, isWB, previews, config);
            iframe.setAttribute('src', drawIoUrl);
        }
        
        var shareToken = $('#iframeEditor').data('sharetoken');

        // Find out if file is read-only
        if (shareToken)
        {
            var fileId = $('#iframeEditor').data('id');
            var data = await OCA.DrawIO.getFileInfo(fileId || '', shareToken);
            
            if (data && !data.writeable)
            {
                drawIoUrl += '&chrome=0';
                autosave = false;
            }

            startEditor();
        }
        else
        {
            startEditor();
        }
    }
});