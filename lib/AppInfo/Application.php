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
use OCP\Files\IMimeTypeLoader;

use OCP\AppFramework\Services\IInitialState;

use OCA\Drawio\AppConfig;
use OCA\Drawio\Preview\DrawioPreview;
use OCA\Drawio\Listeners\FileDeleteListener;
use OCA\Drawio\Listeners\DrawioReferenceListener;
use OCA\Drawio\Listeners\RegisterTemplateCreatorListener;
use OCA\Drawio\Reference\DrawioReferenceProvider;

use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Template\RegisterTemplateCreatorEvent;
use Psr\Log\LoggerInterface;


class Application extends App implements IBootstrap {

    public function __construct(array $urlParams = [])
    {
        parent::__construct("drawio", $urlParams);
    }

    public function register(IRegistrationContext $context): void
    {
        $context->registerEventListener(NodeDeletedEvent::class, FileDeleteListener::class);
        $context->registerEventListener(RenderReferenceEvent::class, DrawioReferenceListener::class);
        $context->registerEventListener(RegisterTemplateCreatorEvent::class, RegisterTemplateCreatorListener::class);

        $context->registerReferenceProvider(DrawioReferenceProvider::class);

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

        $initialState = $container->get(IInitialState::class);
        $appConfig = $container->get(AppConfig::class);
        $initialState->provideInitialState('whiteboards', $appConfig->GetWhiteboards());
        $detector = $container->get(IMimeTypeDetector::class);
        $detector->getAllMappings();
        $detector->registerType("drawio", "application/x-drawio");
        $detector->registerType("dwb", "application/x-drawio-wb");

        $this->ensureMimeTypeAssets($container, $appConfig, $detector);
    }

    /**
     * Self-healing check: re-register MIME type assets if they were lost
     * (e.g., after a Nextcloud core upgrade that replaces core/ files).
     */
    private function ensureMimeTypeAssets($container, AppConfig $appConfig, IMimeTypeDetector $detector): void
    {
        $currentNcVersion = $container->get(\OCP\ServerVersion::class)->getVersionString();
        $storedNcVersion = $appConfig->GetNcVersion();

        $needsRepair = ($storedNcVersion !== $currentNcVersion);

        if (!$needsRepair) {
            $iconTarget = \OC::$SERVERROOT . '/core/img/filetypes/drawio.svg';
            if (!file_exists($iconTarget)) {
                $needsRepair = true;
            }
        }

        if (!$needsRepair) {
            return;
        }

        try {
            $logger = $container->get(LoggerInterface::class);
            $logger->info('Draw.io: Re-registering MIME type assets (NC version: ' .
                $storedNcVersion . ' -> ' . $currentNcVersion . ')', ['app' => 'drawio']);

            $mimeTypeLoader = $container->get(IMimeTypeLoader::class);
            $updateJS = new \OC\Core\Command\Maintenance\Mimetype\UpdateJS($detector);
            $mime = new \OCA\Drawio\Migration\RegisterMimeType($mimeTypeLoader, $updateJS);

            $output = new class($logger) implements \OCP\Migration\IOutput {
                private $logger;
                public function __construct($logger) { $this->logger = $logger; }
                public function info($message) { $this->logger->info($message, ['app' => 'drawio']); }
                public function warning($message) { $this->logger->warning($message, ['app' => 'drawio']); }
                public function startProgress(int $max = 0, string $description = ''): void {}
                public function advance(int $step = 1, string $description = ''): void {}
                public function finishProgress(): void {}
            };

            $mime->run($output);
            $appConfig->SetNcVersion($currentNcVersion);

            $logger->info('Draw.io: MIME type assets re-registered successfully', ['app' => 'drawio']);
        } catch (\Exception $e) {
            $logger = $container->get(LoggerInterface::class);
            $logger->warning('Draw.io: Failed to re-register MIME type assets: ' . $e->getMessage(),
                ['app' => 'drawio', 'exception' => $e]);
        }
    }
}
