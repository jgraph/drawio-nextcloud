<?php
/**
 *
 * @author Pawel Rojek <pawel at pawelrojek.com>
 * @author Ian Reinhart Geiser <igeiser at devonit.com>
 * @author Arno Welzel <privat at arnowelzel.de>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 **/

namespace OCA\Drawio\Controller;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Controller;
use OCP\AutoloadNotAllowedException;
use OCP\Constants;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;

use OC\Files\Filesystem;
use OC\Files\View;
use OC\User\NoUserException;
use OCP\Lock\ILockingProvider;

use OCA\Files\Helper;
use OCP\Files\IAppData;
use OCA\Files_Versions\Storage;
use OCA\Files_Versions\Versions\IVersionManager;
use OCA\Files_Versions\Versions\IVersion;
use OCA\Viewer\Event\LoadViewer;

use OCA\Drawio\AppConfig;


use OC\HintException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\ForbiddenException;
use OCP\Files\GenericFileException;
use OCP\Files\NotFoundException;
use OCP\Lock\LockedException;


class EditorController extends Controller
{

    private $userSession;
    private $root;
    private $urlGenerator;
    private $trans;
    private $logger;
    private $config;
    /**
     * Session
     *
     * @var ISession
     */
    private $session;
    /**
     * Share manager
     *
     * @var IManager
     */
    private $shareManager;

    /**
	 * @var \OCP\Lock\ILockingProvider
	 */
	protected $lockingProvider;

    /**
	 * @var OCA\Files_Versions\Versions\IVersionManager
	 */
	protected $versionManager;
    
    /** @var IAppData */
    private $appData;

    /**
     * @param string $AppName - application name
     * @param IRequest $request - request object
     * @param IRootFolder $root - root folder
     * @param IUserSession $userSession - current user session
     * @param IURLGenerator $urlGenerator - url generator service
     * @param IL10N $trans - l10n service
     * @param ILogger $logger - logger
     * @param OCA\Drawio\AppConfig $config - app config
     */
    public function __construct($AppName,
                                IRequest $request,
                                IRootFolder $root,
                                IUserSession $userSession,
                                IURLGenerator $urlGenerator,
                                IL10N $trans,
                                ILogger $logger,
                                AppConfig $config,
                                IManager $shareManager,
                                ISession $session,
                                ILockingProvider $lockingProvider,
                                IAppData $appData
                                )
    {
        parent::__construct($AppName, $request);

        $this->userSession = $userSession;
        $this->root = $root;
        $this->urlGenerator = $urlGenerator;
        $this->trans = $trans;
        $this->logger = $logger;
        $this->config = $config;
        $this->shareManager = $shareManager;
        $this->session = $session;
        $this->lockingProvider = $lockingProvider;
        $this->appData = $appData;

        try
        {
            $this->versionManager = \OC::$server->get(IVersionManager::class);
        }
        catch (\Exception $e)
        {
            $this->logger->info('VersionManager not found, Versions plugin may not be enabled.', ['app' => $this->appName]);
        }
    }

    /**
     *
     * @NoAdminRequired
     *
     * @param string $path
     * @return DataResponse
     */
	public function loadFileVersion($path, $revId) 
    {
        try {
            if (!isset($this->versionManager))
            {
                return new DataResponse(['message' => $this->trans->t('Versions plugin is not enabled')], Http::STATUS_BAD_REQUEST);
            }

			if (!empty($path) && !empty($revId))
            {
                $user = $this->userSession->getUser();
                $userId = $user->getUID();
                $userFolder = $this->root->getUserFolder($userId);
				/** @var File $file */
				$file = $userFolder->get($path);

				if ($file instanceof Folder) 
                {
					return new DataResponse(['message' => $this->trans->t('You can not open a folder')], Http::STATUS_BAD_REQUEST);
				}

                return new DataResponse($this->versionManager->getVersionFile($user, $file, $revId)->getContent(),
                    Http::STATUS_OK
                );
			} else {
				return new DataResponse(['message' => (string)$this->l->t('Invalid file path/revId supplied.')], Http::STATUS_BAD_REQUEST);
			}
		} catch (\Exception $e) {
            $this->logger->logException($e, ["message" => "Can't load file version: $path, $revId", "app" => $this->appName]);
			$message = (string)$this->trans->t('An internal server error occurred.');
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
    }

    /**
     *
     * @NoAdminRequired
     *
     * @param string $path
     * @return DataResponse
     */
	public function getFileRevisions($path) 
    {
        try {
            if (!isset($this->versionManager))
            {
                return new DataResponse(['message' => $this->trans->t('Versions plugin is not enabled')], Http::STATUS_BAD_REQUEST);
            }

			if (!empty($path)) 
            {
                $user = $this->userSession->getUser();
                $userId = $user->getUID();
                $userFolder = $this->root->getUserFolder($userId);
				/** @var File $file */
				$file = $userFolder->get($path);

				if ($file instanceof Folder) 
                {
					return new DataResponse(['message' => $this->trans->t('You can not open a folder')], Http::STATUS_BAD_REQUEST);
				}

                $versions = $this->versionManager->getVersionsForFile($user, $file);

                return new DataResponse(
                    array_map(function (IVersion $version) {
                        return [
                            'revId' => $version->getRevisionId(),
                            'timestamp' => $version->getTimestamp()
                        ];
                    }, $versions, []),
                    Http::STATUS_OK
                );
			} else {
				return new DataResponse(['message' => (string)$this->l->t('Invalid file path supplied.')], Http::STATUS_BAD_REQUEST);
			}
		} catch (\Exception $e) {
            $this->logger->logException($e, ["message" => "Can't get file versions: $path", "app" => $this->appName]);
			$message = (string)$this->trans->t('An internal server error occurred.');
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
    }

    /**
     *
     * @NoAdminRequired
     *
     * @param string $path
     * @return DataResponse
     */
	public function load($path) {
        $locked = false;
        $fileId = null;

		try {
			if (!empty($path)) 
            {
                $userId = $this->userSession->getUser()->getUID();
                $userFolder = $this->root->getUserFolder($userId);
				/** @var File $file */
				$file = $userFolder->get($path);

				if ($file instanceof Folder) 
                {
					return new DataResponse(['message' => $this->trans->t('You can not open a folder')], Http::STATUS_BAD_REQUEST);
				}

				// default of 100MB. TODO Review this
				$maxSize = 104857600;
				if ($file->getSize() > $maxSize) 
                {
					return new DataResponse(['message' => (string)$this->trans->t('This file is too big to be opened. Please download the file instead.')], Http::STATUS_BAD_REQUEST);
				}

                $fileId = $file->getId();
                $this->lockingProvider->acquireLock('drawio_'.$fileId, ILockingProvider::LOCK_SHARED);
                $locked = true;
				$fileContents = $file->getContent();

				if ($fileContents !== false) 
                {
					return new DataResponse(
						[
							'xml' => $fileContents,
                            'id' => $fileId,
                            'size' => $file->getSize(),
							'writeable' => $file->isUpdateable(),
							'mime' => $file->getMimeType(),
                            'path' => $path,
                            'name' => $file->getName(),
                            'owner' => $file->getOwner()->getUID(),
                            'etag' => $file->getEtag(),
                            'mtime' => $file->getMTime(),
                            'created' => $file->getCreationTime() + $file->getUploadTime(),
                            'versionsEnabled' => isset($this->versionManager)
						],
						Http::STATUS_OK
					);
				} else {
					return new DataResponse(['message' => (string)$this->trans->t('Cannot read the file.')], Http::STATUS_FORBIDDEN);
				}
			} else {
				return new DataResponse(['message' => (string)$this->l->t('Invalid file path supplied.')], Http::STATUS_BAD_REQUEST);
			}

		} catch (LockedException $e) {
			$message = (string) $this->trans->t('The file is locked.');
			return new DataResponse(['message' => $message], Http::STATUS_CONFLICT);
		} catch (ForbiddenException $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
		} catch (HintException $e) {
			$message = (string)$e->getHint();
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (\Exception $e) {
			$message = (string)$this->trans->t('An internal server error occurred.');
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
        finally
        {
            if ($locked)
            {
                $this->lockingProvider->releaseLock('drawio_'.$fileId, ILockingProvider::LOCK_SHARED);
            }
        }
	}

    /**
     *
     * @NoAdminRequired
     *
     * @param string $path
     * @return DataResponse
     */
	public function getFileInfo($path) {
		try {
			if (!empty($path)) {
                $userId = $this->userSession->getUser()->getUID();
                $userFolder = $this->root->getUserFolder($userId);
				/** @var File $file */
				$file = $userFolder->get($path);

				if ($file instanceof Folder) {
					return new DataResponse(['message' => $this->trans->t('You can not open a folder')], Http::STATUS_BAD_REQUEST);
				}

                return new DataResponse(
                    [
                        'id' => $file->getId(),
                        'size' => $file->getSize(),
                        'writeable' => $file->isUpdateable(),
                        'mime' => $file->getMimeType(),
                        'path' => $path,
                        'name' => $file->getName(),
                        'owner' => $file->getOwner()->getUID(),
                        'etag' => $file->getEtag(),
                        'mtime' => $file->getMTime(),
                        'created' => $file->getCreationTime() + $file->getUploadTime()
                    ],
                    Http::STATUS_OK
                );
			} else {
				return new DataResponse(['message' => (string)$this->l->t('Invalid file path supplied.')], Http::STATUS_BAD_REQUEST);
			}

		} catch (LockedException $e) {
			$message = (string) $this->trans->t('The file is locked.');
			return new DataResponse(['message' => $message], Http::STATUS_CONFLICT);
		} catch (ForbiddenException $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
		} catch (HintException $e) {
			$message = (string)$e->getHint();
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (\Exception $e) {
			$message = (string)$this->trans->t('An internal server error occurred.');
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

    /**
     *
     * @NoAdminRequired
     *
     * @param string $path
     * @param string $fileContents
     * @return DataResponse
     */
	public function save($path, $fileContents, $etag) {
		try {
			if (!empty($path) && !empty($fileContents) && !empty($etag)) {
                $userId = $this->userSession->getUser()->getUID();
                $userFolder = $this->root->getUserFolder($userId);
				/** @var File $file */
				$file = $userFolder->get($path);

				if ($file instanceof Folder) {
					return new DataResponse(['message' => $this->trans->t('You can not write to a folder')], Http::STATUS_BAD_REQUEST);
				}
			
				if($file->isUpdateable()) 
                {
                    $locked = false;
                    $fileId = $file->getId();

					try 
                    {
                        // TODO Could not get the locking to work, two browsers can edit the same file at the same time
                        $this->lockingProvider->acquireLock('drawio_'.$fileId, ILockingProvider::LOCK_EXCLUSIVE);
                        $locked = true;

                        // Check if file was changed in the meantime
                        if ($etag != $file->getEtag()) 
                        {
                            return new DataResponse([ 'message' => $this->trans->t('The file you are working on was updated in the meantime.')], Http::STATUS_CONFLICT);
                        }

						$file->putContent($fileContents);
                        // Clear statcache
                        clearstatcache();
                        // Get new eTag
                        $newEtag = $file->getEtag();
                        $newSize = $file->getSize();
                        $newMtime = $file->getMTime();
					} 
                    catch (LockedException $e) 
                    {
						$message = (string) $this->trans->t('The file is locked.');
						return new DataResponse(['message' => $message], Http::STATUS_CONFLICT);
					}
                    catch (ForbiddenException $e)
                    {
						return new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
					}
                    catch (GenericFileException $e)
                    {
						return new DataResponse(['message' => $this->trans->t('Could not write to file.')], Http::STATUS_INTERNAL_SERVER_ERROR);
					}
                    finally
                    {
                        if ($locked)
                        {
                            $this->lockingProvider->releaseLock('drawio_'.$fileId, ILockingProvider::LOCK_EXCLUSIVE);
                        }
                    }

					return new DataResponse(['etag' => $newEtag, 'size' => $newSize, 'mtime' => $newMtime], Http::STATUS_OK);
				} else {
					// Not writeable!
					$this->logger->error('User does not have permission to write to file: ' . $path,
						['app' => $this->appName]);
					return new DataResponse([ 'message' => $this->trans->t('Insufficient permissions')],
						Http::STATUS_FORBIDDEN);
				}
			} else if (!empty($path)) {
				$this->logger->error('No file path supplied');
				return new DataResponse(['message' => $this->trans->t('File path not supplied')], Http::STATUS_BAD_REQUEST);
			} else if (!empty($fileContents)) {
				$this->logger->error('No file content supplied');
				return new DataResponse(['message' => $this->trans->t('File content not supplied')], Http::STATUS_BAD_REQUEST);
			} else {
				$this->logger->error('No file etag supplied', ['app' => $this->appName]);
				return new DataResponse(['message' => $this->trans->t('File etag not supplied')], Http::STATUS_BAD_REQUEST);
			}
		} catch (HintException $e) {
			$message = (string)$e->getHint();
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (\Exception $e) {
            $this->logger->logException($e, ["message" => "Can't save file: $path", "app" => $this->appName]);
            //$this->logger->error("Download Not permitted: $fileId " . $e->getMessage(), array("app" => $this->appName));
			$message = (string)$this->trans->t('An internal server error occurred.');
			//$message = $path;
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

    /**
     * 
     * @NoAdminRequired
     *
     * @param string $path
     * @param string $previewContents
     * @return DataResponse
     */
    public function savePreview($path, $previewContents) 
    {
        try {
			if (!empty($path) && !empty($previewContents)) 
            {
                $this->logger->debug("Saving preview for file: $path", array("app" => $this->appName));

                $prevFolder = null;

                try 
                {
                    $prevFolder = $this->appData->getFolder('previews');
                }
                catch (NotFoundException $e)
                {
                    $prevFolder = $this->appData->newFolder('previews');
                }

                $userId = $this->userSession->getUser()->getUID();
                $userFolder = $this->root->getUserFolder($userId);
                /** @var File $file */
                $file = $userFolder->get($path);

                if ($file instanceof Folder || !$file->isUpdateable()) {
                    return new DataResponse(['message' => $this->trans->t('You can not write to this path')], Http::STATUS_FORBIDDEN);
                }

                $prevFolder->newFile($file->getId() . '.png', base64_decode($previewContents));

                return new DataResponse('OK', Http::STATUS_OK);
			}
            else
            {
				$this->logger->error('Incorrect parameters for savePreview', ['app' => $this->appName]);
				return new DataResponse(['message' => $this->trans->t('Incorrect parameters')], Http::STATUS_BAD_REQUEST);
			}
        }
        catch (\Exception $e) 
        {
            $this->logger->logException($e, ["message" => "Can't save preview for file: $path", "app" => $this->appName]);
			$message = (string)$this->trans->t('An internal server error occurred.');
			//$message = $path;
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
    }
    
    /**
     * @NoAdminRequired
     */
    public function create($name, $dir, $shareToken = NULL)
    {
       //[todo] shareToken support for new files
       //if (empty($shareToken)) {
       $userId = $this->userSession->getUser()->getUID();
       $userFolder = $this->root->getUserFolder($userId);
       //} else { .. }


       $folder = $userFolder->get($dir);

       if ($folder === NULL)
       {
           $this->logger->info("Folder for file creation was not found: " . $dir, array("app" => $this->appName));
           return ["error" => $this->trans->t("The required folder was not found")];
       }
       if (!$folder->isCreatable())
       {
           $this->logger->info("Folder for file creation without permission: " . $dir, array("app" => $this->appName));
           return ["error" => $this->trans->t("You don't have enough permission to create file")];
       }

       $name = $userFolder->getNonExistingName($name);

       $template = " "; //"space" - empty file for drawio

       try {
           if (\version_compare(\implode(".", \OCP\Util::getVersion()), "19", "<")) {
               $file = $folder->newFile($name);
               $file->putContent($template);
           } else {
               $file = $folder->newFile($name, $template);
           }
       } catch (NotPermittedException $e) {
           $this->logger->logException($e, ["message" => "Can't create file: $name", "app" => $this->appName]);
           return ["error" => $this->trans->t("Can't create file")];
       }

       $fileInfo = $file->getFileInfo();

       $result = Helper::formatFileInfo($fileInfo);
       return $result;
   }

     /**
     * This comment is very important, CSRF fails without it
     *
     * @param integer $fileId - file identifier
     *
     * @return TemplateResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index($fileId, $shareToken = NULL, $filePath = NULL, $isWB = false) {
        //$this->logger->warning("Open: $fileId $shareToken $filePath", array("app" => $this->appName));
        if (empty($shareToken) && !$this->userSession->isLoggedIn()) {
            $redirectUrl = $this->urlGenerator->linkToRoute("core.login.showLoginForm", [
                "redirect_url" => $this->request->getRequestUri()
            ]);
            return new RedirectResponse($redirectUrl);
        }

        $drawioUrl = $this->config->GetDrawioUrl();
        $theme = $this->config->GetTheme();
        $darkMode = $this->config->GetDarkMode();
	    $offlineMode = $this->config->GetOfflineMode();
        $lang = $this->config->GetLang();
        $lang = trim(strtolower($lang));

        $eventDispatcher = \OC::$server->getEventDispatcher();

        if ("auto" === $lang)
        {
            $lang = \OC::$server->getL10NFactory("")->get("")->getLanguageCode();
        }

        if (empty($drawioUrl))
        {
            $this->logger->error("drawioUrl is empty", array("app" => $this->appName));
            return ["error" => $this->trans->t("Draw.io app not configured! Please contact admin.")];
        }

        $drawioUrlArray = explode("?",$drawioUrl);

        if (count($drawioUrlArray) > 1){
            $drawioUrl = $drawioUrlArray[0];
            $drawioUrlArgs = $drawioUrlArray[1];
        } else {
            $drawioUrlArgs = "";
        }

        if( $fileId ) 
        {
            list ($file, $error) = $this->getFile($fileId);

            if (isset($error))
            {
                $this->logger->error("Load: " . $fileId . " " . $error, array("app" => $this->appName));
                return ["error" => $error];
            }

            $uid = $this->userSession->getUser()->getUID();
            $baseFolder = $this->root->getUserFolder($uid);
            $relativePath = $baseFolder->getRelativePath($file->getPath());
        }
        else 
        {
            list ($file, $error) = $this->getFileByToken($fileId, $shareToken);
            $relativePath = $file->getPath();
            //$relativePath = "/s/$shareToken/download";//$file->getPath();
        }

        $params = [
            "drawioUrl" => $drawioUrl,
            "drawioUrlArgs" => $drawioUrlArgs,
            "drawioTheme" => $theme,
            "drawioDarkMode" => $darkMode,
            "drawioLang" => $lang,
            "drawioOfflineMode" => $offlineMode,
            "drawioFilePath" => rawurlencode($relativePath),
            "drawioAutosave" =>$this->config->GetAutosave(),
            "drawioLibraries" =>$this->config->GetLibraries(),
            "fileId" => $fileId,
            "filePath" => $filePath,
            "shareToken" => $shareToken,
            "isWB" => $isWB,
            "drawioReadOnly" => false,
            "drawioPreviews" => $this->config->GetPreviews()
        ];

        //viewer code
        if (class_exists(LoadViewer::class)) {
            $eventDispatcher->addListener(LoadViewer::class,
                function () {
                        Util::addScript("drawio", "viewer");
                        $csp = new ContentSecurityPolicy();
                        $csp->addAllowedFrameDomain("'self'");
                        $cspManager = $this->getContainer()->getServer()->getContentSecurityPolicyManager();
                        $cspManager->addDefaultPolicy($csp);
                });
        }
        //viewer code

        $response = new TemplateResponse($this->appName, "editor", $params);

        $csp = new ContentSecurityPolicy();
        $csp->allowInlineScript(true);

        if (isset($drawioUrl) && !empty($drawioUrl))
        {
            $csp->addAllowedScriptDomain($drawioUrl);
            $csp->addAllowedFrameDomain($drawioUrl);
            $csp->addAllowedFrameDomain("blob:");
            $csp->addAllowedChildSrcDomain($drawioUrl);
            $csp->addAllowedChildSrcDomain("blob:");
        }
        $response->setContentSecurityPolicy($csp);

        return $response;
    }

    /**
     * Print public editor section
     *
     * @param integer $fileId - file identifier
     * @param string $shareToken - access token
     *
     * @return TemplateResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function PublicPage($fileId, $shareToken) {
        return $this->index($fileId, $shareToken);
    }

    /**
     * Collecting the file parameters for the DrawIo application
     *
     * @param integer $fileId - file identifier
     * @param string $filePath - file path
     * @param string $shareToken - access token
     *
     * @return DataDownloadResponse
     *
     * @NoAdminRequired
     * @PublicPage
     */
    public function PublicFile($fileId, $filePath = NULL, $shareToken = NULL) {
        if (empty($shareToken)) {
            return ["error" => $this->trans->t("Not permitted")];
        }

        $user = $this->userSession->getUser();
        $userId = NULL;
        if (!empty($user)) {
            $userId = $user->getUID();
        }

        list ($file, $error) = $this->getFileByToken($fileId, $shareToken);;

        if (isset($error)) {
            $this->logger->error("Config: $fileId $error", array("app" => $this->appName));
            return ["error" => $error];
        }

        $fileName = $file->getName();
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $format = $this->config->formats[$ext];

        if (!isset($format)) {
            $this->logger->info("Format is not supported for editing: $fileName", array("app" => $this->appName));
            return ["error" => $this->trans->t("Format is not supported")];
        }

        try {
                return new DataDownloadResponse($file->getContent(), $file->getName(), $file->getMimeType());
            } catch (NotPermittedException  $e) {
                $this->logger->error("Download Not permitted: $fileId " . $e->getMessage(), array("app" => $this->appName));
                return new JSONResponse(["message" => $this->trans->t("Not permitted")], Http::STATUS_FORBIDDEN);
            }
            return new JSONResponse(["message" => $this->trans->t("Download failed")], Http::STATUS_INTERNAL_SERVER_ERROR);
    }

    /**
     * @NoAdminRequired
    */
    private function getFile($fileId)
    {
        if (empty($fileId))
        {
            return [null, $this->trans->t("FileId is empty")];
        }

        $files = $this->root->getById($fileId);
        if (empty($files))
        {
            return [null, $this->trans->t("File not found")];
        }
        $file = $files[0];

        if (!$file->isReadable())
        {
            return [null, $this->trans->t("You do not have enough permissions to view the file")];
        }
        return [$file, null];
    }

    /**
     * Getting file by token
     *
     * @param integer $fileId - file identifier
     * @param string $shareToken - access token
     *
     * @return array
     */
    private function getFileByToken($fileId, $shareToken) {
        list ($node, $error, $share) = $this->getNodeByToken($shareToken);

        if (isset($error)) {
            return [NULL, $error, NULL];
        }

        if ($node instanceof Folder) {
            try {
                $files = $node->getById($fileId);
            } catch (\Exception $e) {
                $this->logger->error("getFileByToken: $fileId " . $e->getMessage(), array("app" => $this->appName));
                return [NULL, $this->trans->t("Invalid request"), NULL];
            }

            if (empty($files)) {
                $this->logger->info("Files not found: $fileId", array("app" => $this->appName));
                return [NULL, $this->trans->t("File not found"), NULL];
            }
            $file = $files[0];
        } else {
            $file = $node;
        }

        return [$file, NULL, $share];
    }

    /**
     * Getting file by token
     *
     * @param string $shareToken - access token
     *
     * @return array
     */
    private function getNodeByToken($shareToken) {
        list ($share, $error) = $this->getShare($shareToken);

        if (isset($error)) {
            return [NULL, $error, NULL];
        }

        if (($share->getPermissions() & Constants::PERMISSION_READ) === 0) {
            return [NULL, $this->trans->t("You do not have enough permissions to view the file"), NULL];
        }

        try {
            $node = $share->getNode();
        } catch (NotFoundException $e) {
            $this->logger->error("getFileByToken error: " . $e->getMessage(), array("app" => $this->appName));
            return [NULL, $this->trans->t("File not found"), NULL];
        }

        return [$node, NULL, $share];
    }
    /**
     * Getting share by token
     *
     * @param string $shareToken - access token
     *
     * @return array
     */
    private function getShare($shareToken) {
        if (empty($shareToken)) {
            return [NULL, $this->trans->t("FileId is empty")];
        }

        $share;
        try {
            $share = $this->shareManager->getShareByToken($shareToken);
        } catch (ShareNotFound $e) {
            $this->logger->error("getShare error: " . $e->getMessage(), array("app" => $this->appName));
            $share = NULL;
        }

        if ($share === NULL || $share === false) {
            return [NULL, $this->trans->t("You do not have enough permissions to view the file")];
        }

        if ($share->getPassword()
            && (!$this->session->exists("public_link_authenticated")
                || $this->session->get("public_link_authenticated") !== (string) $share->getId())) {
            return [NULL, $this->trans->t("You do not have enough permissions to view the file")];
        }

        return [$share, NULL];
    }

    /**
     * Print error page
     *
     * @param string $error - error message
     * @param string $hint - error hint
     *
     * @return TemplateResponse
     */
    private function renderError($error, $hint = "") {
        return new TemplateResponse("", "error", array(
                "errors" => array(array(
                "error" => $error,
                "hint" => $hint
            ))
        ), "error");
    }

}
