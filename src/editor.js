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
import '@nextcloud/dialogs/styles/toast.scss'

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
        $('#app').text(error).addClass('error');
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

                var url = generateUrl('/apps/' + OCA.DrawIO.AppName + '/ajax/' + op + '?path={path}' + 
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

    OCA.DrawIO.loadFile = async function (path, success, error)
    {
        return await fileOpInt({path}, 1, success, error);
    };

    OCA.DrawIO.getFileInfo = async function (path, success, error)
    {
        return await fileOpInt({path}, 2, success, error);
    };

    OCA.DrawIO.getFileRevisions = async function (path, success, error)
    {
        return await fileOpInt({path}, 3, success, error);
    };

    OCA.DrawIO.loadFileVersion = async function (path, revId, success, error)
    {
        return await fileOpInt({path, revId}, 4, success, error);
    };

    OCA.DrawIO.saveFile = async function (path, fileContents, etag, success, error)
    {
        OCA.DrawIO.fileSaved = true;
        return await fileOpInt({path, fileContents, etag}, 5, success, error);
    };

    OCA.DrawIO.savePreview = async function (path, previewContents, success, error)
    {
        return await fileOpInt({path, previewContents}, 6, success, error);
    };

    OCA.DrawIO.getCurrentUser = function ()
    {
        OCA.DrawIO.pluginLoaded = true;
        return getCurrentUser();
    }
    
    OCA.DrawIO.Cleanup = async function (receiver, filePath) 
    {
        window.removeEventListener('message', receiver);

        try
        {
            var data = await OCA.DrawIO.getFileInfo(filePath);

            var url = generateUrl('/apps/files/?dir={currentDirectory}&fileid={fileId}', {
                currentDirectory: filePath.substring(0, filePath.lastIndexOf('/')),
                fileId: data.id
            });
            window.location.href = url;
        }
        catch (error)
        {
            console.log(error);
            var url = generateUrl('/apps/files');
            window.location.href = url;
        }
    };

    OCA.DrawIO.EditFile = function (editWindow, filePath, origin, autosave, isWB, previews) 
    {
        var autosaveEnabled = autosave === 'yes';
        var fileId = $('#iframeEditor').data('id');
        var shareToken = $('#iframeEditor').data('sharetoken');
        var currentFile = null;

        if (!fileId && !shareToken) 
        {
            displayError(t(OCA.DrawIO.AppName, 'FileId is empty'));
            return;
        }

        if (shareToken) 
        {
            var fileUrl = generateUrl('apps/' + OCA.DrawIO.AppName + '/ajax/shared/{fileId}', { fileId: fileId || 0 });
            var params = [];

            if (filePath) 
            {
                params.push('filePath=' + encodeURIComponent(filePath));
            }

            if (shareToken) 
            {
                params.push('shareToken=' + encodeURIComponent(shareToken));
            }

            if (params.length) 
            {
                fileUrl += '?' + params.join('&');
            }
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
                        await OCA.DrawIO.savePreview(filePath, imageData);
                        OCA.DrawIO.Cleanup(receiver, filePath);
                    } 
                    else if (payload.event === 'autosave' || payload.event === 'save')
                    {
                        if (!OCA.DrawIO.pluginLoaded)
                        {
                            try
                            {
                                var resp = await OCA.DrawIO.saveFile(filePath, payload.xml, currentFile.etag);
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
                            OCA.DrawIO.Cleanup(receiver, filePath);
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
                    /* TODO Add later if needed
                    // Configure must be sent even if JSON invalid
                    configObj = configObj || {};
                    editor.contentWindow.postMessage(JSON.stringify({action: 'configure',
                        config: configObj}), '*');
                    */
                }
                else if (msg.event == 'init')
                {
                    try
                    {
                        if(!fileId) 
                        {
                            var response = await axios.get(fileUrl);

                            editWindow.postMessage(JSON.stringify({
                                action: 'load',
                                xml: response.data
                            }), '*');
                        }
                        else
                        {
                            var data = await OCA.DrawIO.loadFile(filePath);

                            var contents = data.xml;
                            currentFile = data;
                            delete currentFile.xml;

                            if (contents === ' ') 
                            {
                                OCA.DrawIO.NewFileMode = true; //[workaround] 'loading' file without content, to display 'template' later in 'load' callback event without another filename prompt
                                editWindow.postMessage(JSON.stringify({
                                    action: 'load', autosave: autosaveEnabled, title: currentFile.name,
                                    desc: currentFile, disableAutoSave: !autosaveEnabled
                                }), '*');
                            } 
                            else
                            {
                                OCA.DrawIO.NewFileMode = false;
                                editWindow.postMessage(JSON.stringify({
                                    action: 'load',
                                    autosave: autosaveEnabled, title: currentFile.name,
                                    xml: contents,
                                    desc: currentFile, disableAutoSave: !autosaveEnabled
                                }), '*');
                            }
                        }

                        window.removeEventListener('message', initHandler);
                        document.body.style.backgroundImage = 'none';
                        startEditor();
                    }
                    catch (error)
                    {
                        showError(t(OCA.DrawIO.AppName, 'Error loading the file') + ' (' + (error.data || error.message) + ')', { timeout: 2500 });
                        console.log('Status Error: ' + error.status);
                        OCA.DrawIO.Cleanup(initHandler, filePath);
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
