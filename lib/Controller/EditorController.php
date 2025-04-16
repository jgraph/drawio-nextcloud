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
use OCP\AppFramework\Http\Template\PublicTemplateResponse;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Controller;
use OCP\AutoloadNotAllowedException;
use OCP\Constants;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

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
use OCP\Federation\Exceptions\BadRequestException;


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
     * @param LoggerInterface $logger - logger
     * @param OCA\Drawio\AppConfig $config - app config
     */
    public function __construct($AppName,
                                IRequest $request,
                                IRootFolder $root,
                                IUserSession $userSession,
                                IURLGenerator $urlGenerator,
                                IL10N $trans,
                                LoggerInterface $logger,
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
     * @param string $fileId
     * @param string $revId
     * @return DataResponse
     */
	public function loadFileVersion($fileId, $revId) 
    {
        try {
            if (!isset($this->versionManager))
            {
                return new DataResponse(['message' => $this->trans->t('Versions plugin is not enabled')], Http::STATUS_BAD_REQUEST);
            }

			if (!empty($fileId) && !empty($revId))
            {
                $user = $this->userSession->getUser();
				/** @var File $file */
                $file = $this->getFileById($fileId);

				if ($file instanceof Folder) 
                {
					return new DataResponse(['message' => $this->trans->t('You can not open a folder')], Http::STATUS_BAD_REQUEST);
				}

                return new DataResponse($this->versionManager->getVersionFile($user, $file, $revId)->getContent(),
                    Http::STATUS_OK
                );
			} else {
				return new DataResponse(['message' => (string)$this->trans->t('Invalid fileId/revId supplied.')], Http::STATUS_BAD_REQUEST);
			}
        }
        catch (NotFoundException $e)
        {
            return $this->loadInternal($fileId, null, true);
		} catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ["message" => "Can't load file version: $fileId, $revId", "app" => $this->appName, 'level' => LogLevel::ERROR, 'exception' => $e]);
			$message = (string)$this->trans->t('An internal server error occurred.');
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
    }

    /**
     *
     * @NoAdminRequired
     *
     * @param string $fileId
     * @return DataResponse
     */
	public function getFileRevisions($fileId) 
    {
        try {
            if (!isset($this->versionManager))
            {
                return new DataResponse(['message' => $this->trans->t('Versions plugin is not enabled')], Http::STATUS_BAD_REQUEST);
            }

			if (!empty($fileId)) 
            {
                $user = $this->userSession->getUser();
				/** @var File $file */
                $file = $this->getFileById($fileId);

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
				return new DataResponse(['message' => (string)$this->trans->t('Invalid fileId supplied.')], Http::STATUS_BAD_REQUEST);
			}
		} catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ["message" => "Can't get file versions: $fileId", "app" => $this->appName, 'level' => LogLevel::ERROR, 'exception' => $e]);
			$message = (string)$this->trans->t('An internal server error occurred.');
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
    }

    /**
     *
     * @NoAdminRequired
     * @PublicPage
     *
     * @param string $fileId
     * @param string $shareToken
     * @return DataResponse
     */
	public function load($fileId, $shareToken) 
    {
        return $this->loadInternal($fileId, $shareToken, false);
    }

    private function loadInternal($fileId, $shareToken, $contentsOnly)
    {
        $locked = false;

		try 
        {
            list ($file, $writeable, $relativePath) = $this->getFile($fileId, $shareToken);
            
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
                    $contentsOnly? $fileContents: [
                        'xml' => $fileContents,
                        'id' => $fileId,
                        'size' => $file->getSize(),
                        'writeable' => $file->isUpdateable(),
                        'mime' => $file->getMimeType(),
                        'path' => $relativePath,
                        'name' => $file->getName(),
                        'owner' => $file->getOwner()->getUID(),
                        'etag' => $file->getEtag(),
                        'mtime' => $file->getMTime(),
                        'created' => $file->getCreationTime() + $file->getUploadTime(),
                        'shareToken' => $shareToken,
                        'versionsEnabled' => empty($shareToken) && isset($this->versionManager),
                        'ver' => 2,
                        'instanceId' => \OC_Util::getInstanceId()
                    ],
                    Http::STATUS_OK
                );
            }
            else
            {
                return new DataResponse(['message' => (string)$this->trans->t('Cannot read the file.')], Http::STATUS_FORBIDDEN);
            }
        }
        catch (BadRequestException $e)
        {
            return new DataResponse(['message' => (string)$this->trans->t('Invalid fileId/shareToken supplied.')], Http::STATUS_BAD_REQUEST);
        }
        catch (NotFoundException $e)
        {
            return new DataResponse(['message' => $this->trans->t('File not found.')], Http::STATUS_NOT_FOUND);
		} catch (LockedException $e) {
			$message = (string) $this->trans->t('The file is locked.');
			return new DataResponse(['message' => $message], Http::STATUS_CONFLICT);
		} catch (ForbiddenException $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
		} catch (HintException $e) {
			$message = (string)$e->getHint();
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ["message" => "Can't load file: $fileId , $shareToken", "app" => $this->appName, 'level' => LogLevel::ERROR, 'exception' => $e]);
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
     * @PublicPage
     *
     * @param string $fileId
     * @param string $shareToken
     * @return DataResponse
     */
	public function getFileInfo($fileId, $shareToken) 
    {
		try 
        {
            list ($file, $writeable, $relativePath) = $this->getFile($fileId, $shareToken);

            if ($file instanceof Folder) {
                return new DataResponse(['message' => $this->trans->t('You can not open a folder')], Http::STATUS_BAD_REQUEST);
            }

            return new DataResponse(
                [
                    'id' => $file->getId(),
                    'size' => $file->getSize(),
                    'writeable' => $writeable,
                    'mime' => $file->getMimeType(),
                    'path' => $relativePath,
                    'name' => $file->getName(),
                    'owner' => $file->getOwner()->getUID(),
                    'etag' => $file->getEtag(),
                    'mtime' => $file->getMTime(),
                    'created' => $file->getCreationTime() + $file->getUploadTime(),
                    'shareToken' => $shareToken,
                    'versionsEnabled' => empty($shareToken) && isset($this->versionManager),
                    'ver' => 2,
                    'instanceId' => \OC_Util::getInstanceId()
                ],
                Http::STATUS_OK
            );
        }
        catch (BadRequestException $e)
        {
            return new DataResponse(['message' => (string)$this->trans->t('Invalid fileId/shareToken supplied.')], Http::STATUS_BAD_REQUEST);
        }
        catch (NotFoundException $e)
        {
            return new DataResponse(['message' => $this->trans->t('File not found.')], Http::STATUS_NOT_FOUND);
        } catch (LockedException $e) {
			$message = (string) $this->trans->t('The file is locked.');
			return new DataResponse(['message' => $message], Http::STATUS_CONFLICT);
		} catch (ForbiddenException $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
		} catch (HintException $e) {
			$message = (string)$e->getHint();
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ["message" => "Can't get file info: $fileId , $shareToken", "app" => $this->appName, 'level' => LogLevel::ERROR, 'exception' => $e]);
			$message = (string)$this->trans->t('An internal server error occurred.');
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

    /**
     *
     * @NoAdminRequired
     * @PublicPage
     *
     * @param string $fileId
     * @param string $shareToken
     * @param string $fileContents
     * @param string $etag
     * @return DataResponse
     */
	public function save($fileId, $shareToken, $fileContents, $etag) 
    {
		try
        {
			if (!empty($fileContents) && !empty($etag))
            {
                list ($file, $writeable) = $this->getFile($fileId, $shareToken);

				if ($file instanceof Folder) {
					return new DataResponse(['message' => $this->trans->t('You can not write to a folder')], Http::STATUS_BAD_REQUEST);
				}
			
				if($writeable) 
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
					$this->logger->error('User does not have permission to write to file: ' . $fileId . ', ' . $shareToken,
						['app' => $this->appName]);
					return new DataResponse([ 'message' => $this->trans->t('Insufficient permissions')],
						Http::STATUS_FORBIDDEN);
				}
			} else if (!empty($fileContents)) {
				$this->logger->error('No file content supplied');
				return new DataResponse(['message' => $this->trans->t('File content not supplied')], Http::STATUS_BAD_REQUEST);
			} else {
				$this->logger->error('No file etag supplied', ['app' => $this->appName]);
				return new DataResponse(['message' => $this->trans->t('File etag not supplied')], Http::STATUS_BAD_REQUEST);
			}
		}
        catch (BadRequestException $e)
        {
            return new DataResponse(['message' => (string)$this->trans->t('Invalid fileId/shareToken supplied.')], Http::STATUS_BAD_REQUEST);
        }
        catch (NotFoundException $e)
        {
            return new DataResponse(['message' => $this->trans->t('File not found.')], Http::STATUS_NOT_FOUND);
		}
        catch (HintException $e)
        {
			$message = (string)$e->getHint();
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ["message" => "Can't save file: $fileId , $shareToken", "app" => $this->appName, 'level' => LogLevel::ERROR, 'exception' => $e]);
			$message = (string)$this->trans->t('An internal server error occurred.');
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

    /**
     * 
     * @NoAdminRequired
     * @PublicPage
     *
     * @param string $fileId
     * @param string $shareToken
     * @param string $previewContents
     * @return DataResponse
     */
    public function savePreview($fileId, $shareToken, $previewContents) 
    {
        try
        {
			if (!empty($previewContents)) 
            {
                list ($file, $writeable) = $this->getFile($fileId, $shareToken);
                $this->logger->debug("Saving preview for file: $fileId , $shareToken", array("app" => $this->appName));

                $prevFolder = null;

                try 
                {
                    $prevFolder = $this->appData->getFolder('previews');
                }
                catch (NotFoundException $e)
                {
                    $prevFolder = $this->appData->newFolder('previews');
                }

                if ($file instanceof Folder || !$writeable) 
                {
                    return new DataResponse(['message' => $this->trans->t('You cannot write to this path')], Http::STATUS_FORBIDDEN);
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
            $this->logger->error($e->getMessage(), ["message" => "Can't save preview for file: $fileId , $shareToken", "app" => $this->appName, 'level' => LogLevel::ERROR, 'exception' => $e]);
			$message = (string)$this->trans->t('An internal server error occurred.');
			return new DataResponse(['message' => $message], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
    }
    
    /**
     * @NoAdminRequired
     * @PublicPage
     */
    public function create($name, $dirId, $shareToken)
    {
        list ($folder, $writeable) = $this->getDir($dirId, $shareToken);

        if ($folder === NULL)
        {
            $this->logger->info("Folder for file creation was not found: " . $dirId, array("app" => $this->appName));
            return ["error" => $this->trans->t("The required folder was not found")];
        }

        if (!$writeable)
        {
            $this->logger->info("Folder for file creation without permission: " . $dirId, array("app" => $this->appName));
            return ["error" => $this->trans->t("You don't have enough permission to create file")];
        }

        $name = $folder->getNonExistingName($name);

        $template = " "; //"space" - empty file for drawio

        try 
        {
            $file = $folder->newFile($name, $template);
        }
        catch (NotPermittedException $e)
        {
            $this->logger->error($e->getMessage(), ["message" => "Can't create file: $name", "app" => $this->appName, 'level' => LogLevel::ERROR, 'exception' => $e]);
            return ["error" => $this->trans->t("Can't create file")];
        }

        $fileInfo = $file->getFileInfo();
        $result = Helper::formatFileInfo($fileInfo);
        return $result;
    }

    /**
     *
     * @param integer $fileId - file identifier
     *
     * @return TemplateResponse
     *
     * @PublicPage
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index($fileId, $shareToken = NULL, $lightbox = false, $isWB = false) 
    {
        if (empty($shareToken) && !$this->userSession->isLoggedIn()) 
        {
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

        if ("auto" === $lang)
        {
            $lang = \OC::$server->getL10NFactory("")->get("")->getLanguageCode();

            if (!empty($lang) && strpos($lang, "_"))
            {
                $lang = substr($lang, 0, strpos($lang, "_")); // Change to draw.io format
            }
        }

        if (empty($drawioUrl))
        {
            $this->logger->error("drawioUrl is empty", array("app" => $this->appName));
            return ["error" => $this->trans->t("Draw.io app not configured! Please contact admin.")];
        }

        $drawioUrlArray = explode("?",$drawioUrl);

        if (count($drawioUrlArray) > 1)
        {
            $drawioUrl = $drawioUrlArray[0];
            $drawioUrlArgs = $drawioUrlArray[1];
        }
        else
        {
            $drawioUrlArgs = "";
        }

        $params = [
            "drawioUrl" => $drawioUrl,
            "drawioUrlArgs" => $drawioUrlArgs,
            "drawioTheme" => $theme,
            "drawioDarkMode" => $darkMode,
            "drawioLang" => $lang,
            "drawioOfflineMode" => $offlineMode,
            "drawioAutosave" =>$this->config->GetAutosave(),
            "drawioLibraries" =>$this->config->GetLibraries(),
            "fileId" => $fileId,
            "shareToken" => $shareToken,
            "isWB" => $isWB,
            "drawioReadOnly" => $lightbox,
            "drawioPreviews" => $this->config->GetPreviews(),
            "drawioConfig" => $this->config->GetDrawioConfig(),
        ];

        if ($this->userSession->getUser() !== null)
        {
            $response = new TemplateResponse($this->appName, "editor", $params);
        }
        else
        {
            $response = new PublicTemplateResponse($this->appName, "editor", $params);
            $response->setFooterVisible(false);
        }

        $csp = new ContentSecurityPolicy();

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
     * Getting file by token
     *
     * @param string $shareToken - access token
     *
     * @return array
     */
    private function getNodeByToken($shareToken) 
    {
        $share = null;

        try 
        {
            $share = $this->shareManager->getShareByToken($shareToken);
        }
        catch (ShareNotFound $e)
        {
            throw new NotFoundException();
        }

        if ($share === null || $share === false || 
            ($share->getPassword() !== null && (!$this->session->exists("public_link_authenticated")
                || $this->session->get("public_link_authenticated") !== (string) $share->getId())) ||
            !$this->checkPermissions($share, Constants::PERMISSION_READ))
        {
            throw new ForbiddenException();
        }

        return [$share->getNode(), $share];
    }


    /**
     * Getting file by id
     *
     * @param $fileId - file identifier
     *
     */
    private function getFileById($fileId)
    {
        $files = $this->root->getById($fileId);

        if (empty($files))
        {
            throw new NotFoundException();
        }

        $file = $files[0];

        if (!$file->isReadable())
        {
            throw new ForbiddenException();
        }

        return $file;
    }

    /**
     * Getting file by id or token
     *
     * @param $fileId - file identifier
     * @param $shareToken - access token
     *
     * @return array
     */
    private function getFile($fileId, $shareToken)
    {
        /** @var File $file */
        $file = null;
        $writeable = false;
        $baseFolder = null;

        if (!empty($fileId) && $this->userSession->isLoggedIn()) 
        {
            $file = $this->getFileById($fileId);
            $uid = $this->userSession->getUser()->getUID();
            $baseFolder = $this->root->getUserFolder($uid);
        }
        else if (!empty($shareToken))
        {
            list ($file, $share) = $this->getNodeByToken($shareToken);
            
            if (!empty($fileId) && $file->getType() == FileInfo::TYPE_FOLDER) // File in a shared folder case
            {
                $file = $file->getById($fileId)[0];
            }
        }
        else
        {
            throw new BadRequestException(['fileId', 'shareToken']);
        }

        if ($file === null || $file === false)
        {
            throw new NotFoundException();
        }

        if (!empty($shareToken))
        {
            $writeable = $this->checkPermissions($share, Constants::PERMISSION_UPDATE);
        }
        else
        {
            $writeable = $file->isUpdateable();
        }

        return [$file, $writeable, $baseFolder != null? $baseFolder->getRelativePath($file->getPath()) : null];
    }

    /**
     * Getting directory by id or token
     *
     * @param $dirId - directory identifier
     * @param $shareToken - access token
     *
     * @return array
     */
    private function getDir($dirId, $shareToken)
    {
        /** @var Folder $dir */
        $dir = null;
        $isCreatable = false;

        if (!empty($dirId) && $this->userSession->isLoggedIn()) 
        {
            $dir = $this->root->getById($dirId)[0];
        }
        else if (!empty($shareToken))
        {
            list ($dir, $share) = $this->getNodeByToken($shareToken);
        }
        else
        {
            throw new BadRequestException(['fileId', 'shareToken']);
        }

        if ($dir === null || $dir === false)
        {
            throw new NotFoundException();
        }

        if (!empty($shareToken))
        {
            $isCreatable = $this->checkPermissions($share, Constants::PERMISSION_CREATE);
        }
        else
        {
            $isCreatable = $dir->isCreatable();
        }

        return [$dir, $isCreatable];
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

    protected function checkPermissions($share, $permissions) {
        return ($share->getPermissions() & $permissions) === $permissions;
    }
}
