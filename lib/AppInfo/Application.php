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
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IConfig;
use OCP\Util;
use OCP\Files\IMimeTypeDetector;

use OCA\Drawio\AppConfig;
use OCA\Drawio\Preview\DrawioPreview;
use OCA\Drawio\Listeners\FileDeleteListener;

use OCP\Files\Events\Node\NodeDeletedEvent;
use Psr\Log\LoggerInterface;


class Application extends App implements IBootstrap {

    public function __construct(array $urlParams = [])
    {
        parent::__construct("drawio", $urlParams);
    }

    public function register(IRegistrationContext $context): void
    {
        $context->registerEventListener(NodeDeletedEvent::class, FileDeleteListener::class);

        $context->registerPreviewProvider(
            DrawioPreview::class,
            DrawioPreview::getMimeTypeRegex()
        );

        $context->registerService(AppConfig::class, function ($c) {
            return new AppConfig(
                'drawio',
                $c->get(IConfig::class),
                $c->get(LoggerInterface::class)
            );
        });
    }

    public function boot(IBootContext $context): void
    {
        Util::addInitScript("drawio", "main");
        Util::addStyle("drawio", "main");

        $container = $context->getAppContainer();
        $detector = $container->get(IMimeTypeDetector::class);
        $detector->getAllMappings();
        $detector->registerType("drawio", "application/x-drawio");
        $detector->registerType("dwb", "application/x-drawio-wb");
    }
}
