<?php

/**
 *
 * @author Pawel Rojek <pawel at pawelrojek.com>
 * @author Ian Reinhart Geiser <igeiser at devonit.com>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 **/

namespace OCA\Drawio\AppInfo;

use OCP\AppFramework\App;
use OCP\Util;
use OCP\IPreview;
use OCP\Files\IMimeTypeDetector;

use OCA\Drawio\AppConfig;
use OCA\Drawio\Controller\DisplayController;
use OCA\Drawio\Controller\EditorController;
use OCA\Drawio\Controller\ViewerController;
use OCA\Drawio\Controller\SettingsController;
use OCA\Drawio\Preview\DrawioPreview;
use OCA\Drawio\Listeners\FileDeleteListener;
use OCA\Drawio\Listeners\RegisterTemplateCreatorListener;
use OCP\Files\Template\RegisterTemplateCreatorEvent;

use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\IAppData;
use Psr\Log\LoggerInterface;


class Application extends App {

    public const APP_ID = "drawio";

    public $appConfig;

    public function __construct(array $urlParams = [])
    {
        $appName = self::APP_ID;

        parent::__construct($appName, $urlParams);

        $this->appConfig = new AppConfig($appName);

        // Default script and style if configured
        if (!empty($this->appConfig->GetDrawioUrl()) && array_key_exists("REQUEST_URI", \OC::$server->getRequest()->server))
        {
            $url = \OC::$server->getRequest()->server["REQUEST_URI"];

            if (isset($url)) {
                if (preg_match("%/apps/files(/.*)?%", $url) || preg_match("%/s/.*%", $url)) // Files app and file sharing
                {
                    Util::addScript($appName, "main");
                    Util::addStyle($appName, "main");
                }
            }
        }
        
        $container = $this->getContainer();

        $container->registerService("L10N", function($c)
        {
            return $c->query("ServerContainer")->getL10N($c->query("AppName"));
        });

        $container->registerService("RootStorage", function($c)
        {
            return $c->query("ServerContainer")->getRootFolder();
        });

        $container->registerService("UserSession", function($c)
        {
            return $c->query("ServerContainer")->getUserSession();
        });

        $container->registerService("Logger", function($c)
        {
            return $c->query("ServerContainer")->get(LoggerInterface::class);
        });


        $container->registerService("SettingsController", function($c)
        {
            return new SettingsController(
                $c->query("AppName"),
                $c->query("Request"),
                $c->query("L10N"),
                $c->query("Logger"),
                $this->appConfig
            );
        });


        $container->registerService("EditorController", function($c)
        {
            return new EditorController(
                $c->query("AppName"),
                $c->query("Request"),
                $c->query("RootStorage"),
                $c->query("UserSession"),
                $c->query("ServerContainer")->getURLGenerator(),
                $c->query("L10N"),
                $c->query("Logger"),
                $this->appConfig,
                $c->query("IManager"),
                $c->query("Session"),
                \OC::$server->getLockingProvider(),
                \OC::$server->get(IAppData::class)
            );
        });
        
        $container->registerService("ViewerController", function($c)
        {
            return new ViewerController(
                $c->query("AppName"),
                $c->query("Request"),
                $c->query("RootStorage"),
                $c->query("UserSession"),
                $c->query("ServerContainer")->getURLGenerator(),
                $c->query("L10N"),
                $c->query("Logger"),
                $this->appConfig,
                $c->query("IManager"),
                $c->query("Session")
            );
        }); 
        
        $previewManager = $container->query(IPreview::class);
        $previewManager->registerProvider(DrawioPreview::getMimeTypeRegex(), function() use ($container) {
            return $container->query(DrawioPreview::class);
        });

        $detector = $container->query(IMimeTypeDetector::class);
        $detector->getAllMappings();
        $detector->registerType("drawio", "application/x-drawio");
        $detector->registerType("dwb", "application/x-drawio-wb");

        $server = $container->getServer();
        /** @var IEventDispatcher $eventDispatcher */
		$newEventDispatcher = $server->query(IEventDispatcher::class);
        $newEventDispatcher->addServiceListener(NodeDeletedEvent::class, FileDeleteListener::class);
        $newEventDispatcher->addServiceListener(RegisterTemplateCreatorEvent::class, RegisterTemplateCreatorListener::class);
    }
}
