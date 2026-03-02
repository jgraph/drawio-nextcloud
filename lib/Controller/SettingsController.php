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

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IMimeTypeDetector;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

use OCA\Drawio\AppConfig;

class SettingsController extends Controller
{

    private $trans;
    private $logger;
    private $config;
    private $mimeTypeLoader;
    private $mimeTypeDetector;


    /**
     * @param string $AppName - application name
     * @param IRequest $request - request object
     * @param IL10N $trans - l10n service
     * @param LoggerInterface $logger - logger
     * @param AppConfig $config - application configuration
     * @param IMimeTypeLoader $mimeTypeLoader - MIME type loader
     * @param IMimeTypeDetector $mimeTypeDetector - MIME type detector
     */
    public function __construct($AppName,
                                IRequest $request,
                                IL10N $trans,
                                LoggerInterface $logger,
                                AppConfig $config,
                                IMimeTypeLoader $mimeTypeLoader,
                                IMimeTypeDetector $mimeTypeDetector
                                )
    {
        parent::__construct($AppName, $request);

        $this->trans = $trans;
        $this->logger = $logger;
        $this->config = $config;
        $this->mimeTypeLoader = $mimeTypeLoader;
        $this->mimeTypeDetector = $mimeTypeDetector;
    }


    /**
     * Config page
     *
     * @return TemplateResponse
     */
    public function index() {
        $data = [
            "drawioUrl" => $this->config->GetDrawioUrl(),
            "drawioOfflineMode" => $this->config->GetOfflineMode(),
            "drawioTheme" => $this->config->GetTheme(),
            "drawioLang" => $this->config->GetLang(),
            "drawioAutosave" => $this->config->GetAutosave(),
            "drawioLibraries" => $this->config->GetLibraries(),
            "drawioDarkMode" => $this->config->GetDarkMode(),
            "drawioPreviews" => $this->config->GetPreviews(),
            "drawioConfig" => $this->config->GetDrawioConfig(),
            "drawioWhiteboards" => $this->config->GetWhiteboards(),
        ];
        return new TemplateResponse($this->appName, "settings", $data, "blank");
    }

	/**
	 * Save settings
	 *
	 * @AuthorizedAdminSetting(settings=OCA\Drawio\Settings\Admin)
	 */
    public function settings()
    {
        $drawio = trim($this->request->getParam('drawioUrl', ''));
        $offlinemode = trim($this->request->getParam('offlineMode', ''));
        $theme = trim($this->request->getParam('theme', ''));
        $lang = trim($this->request->getParam('lang', ''));
        $autosave = trim($this->request->getParam('autosave', ''));
        $libraries = trim($this->request->getParam('libraries', ''));
        $darkmode = trim($this->request->getParam('darkMode', ''));
        $previews = trim($this->request->getParam('previews', ''));
        $drawioConfig = trim($this->request->getParam('drawioConfig', ''));
        $whiteboards = trim($this->request->getParam('whiteboards', ''));

        $this->config->SetDrawioUrl($drawio);
        $this->config->SetOfflineMode($offlinemode);
        $this->config->SetTheme($theme);
        $this->config->SetLang($lang);
        $this->config->SetAutosave($autosave);
        $this->config->SetLibraries($libraries);
        $this->config->SetDarkMode($darkmode);
        $this->config->SetPreviews($previews);
        $this->config->SetDrawioConfig($drawioConfig);
        $this->config->SetWhiteboards($whiteboards);

        $checkmime = new \OCA\Drawio\Migration\CheckMimeType();
        $registered = $checkmime->run();

        if ($registered == false) {
            // NOTE: UpdateJS is an internal Nextcloud class (OC\Core\Command\Maintenance\Mimetype\UpdateJS).
            // There is no public OCP API for MIME type JS regeneration. This is unavoidable and shared
            // by all NC apps that register custom MIME types (Keeweb, Mind Map, etc.).
            $updateJS = new \OC\Core\Command\Maintenance\Mimetype\UpdateJS($this->mimeTypeDetector);
            $mime = new \OCA\Drawio\Migration\RegisterMimeType($this->mimeTypeLoader, $updateJS);
            $output = new class($this->logger, $this->appName) implements \OCP\Migration\IOutput {
                private $logger;
                private $appName;
                public function __construct($logger, $appName) { $this->logger = $logger; $this->appName = $appName; }
                public function debug(string $message): void { $this->logger->debug($message, ['app' => $this->appName]); }
                public function info($message) { $this->logger->info($message, ['app' => $this->appName]); }
                public function warning($message) { $this->logger->warning($message, ['app' => $this->appName]); }
                public function startProgress($max = 0): void {}
                public function advance($step = 1, $description = ''): void {}
                public function finishProgress(): void {}
            };
            $mime->run($output);
        }

        return [
            "drawioUrl" => $this->config->GetDrawioUrl(),
            "offlineMode" => $this->config->GetOfflineMode(),
            "theme" => $this->config->GetTheme(),
            "lang" => $this->config->GetLang(),
            "drawioAutosave" =>$this->config->GetAutosave(),
            "drawioLibraries" =>$this->config->GetLibraries(),
            "drawioDarkMode" =>$this->config->GetDarkMode(),
            "drawioPreviews" =>$this->config->GetPreviews(),
            "drawioConfig" =>$this->config->GetDrawioConfig(),
            "drawioWhiteboards" =>$this->config->GetWhiteboards(),
        ];
    }

}
