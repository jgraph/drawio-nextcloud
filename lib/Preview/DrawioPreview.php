<?php

namespace OCA\Drawio\Preview;

use OC\Files\View;
use OC\Preview\Provider;

use OCP\AppFramework\QueryException;
use OCP\Files\FileInfo;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Image;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use OCA\Drawio\AppConfig;

class DrawioPreview extends Provider
{
    protected $appConfig;
    protected $logger;
    protected $appName;
    /** @var IAppData */
    protected $appData;

    /**
     * Capabilities mimetype
     *
     * @var Array
     */
    public static $capabilities = [
        "application/x-drawio",
        "application/x-drawio-wb"
    ];

    public function __construct(LoggerInterface $logger, IAppData $appData)
    {
        $this->logger = $logger;
        $this->appData = $appData;
        $this->appName = 'drawio';
        $this->appConfig = new AppConfig($this->appName);
    }

    /**
     * Return mime type
     */
    public static function getMimeTypeRegex() {
        $mimeTypeRegex = "";
        foreach (self::$capabilities as $format) {
            if (!empty($mimeTypeRegex)) {
                $mimeTypeRegex = $mimeTypeRegex . "|";
            }
            $mimeTypeRegex = $mimeTypeRegex . str_replace("/", "\/", $format);
        }
        $mimeTypeRegex = "/" . $mimeTypeRegex . "/";

        return $mimeTypeRegex;
    }

    public function getMimeType(): string
    {
        $m = self::getMimeTypeRegex();
        return $m;
    }

    public function isAvailable(FileInfo $file): bool
    {
        $prevFile = $this->getPreviewFile($file->getId());
        return ($this->appConfig->GetPreviews() === 'yes') && $prevFile !== false &&
            $prevFile->getMtime() >= $file->getMtime();
    }

    public function getThumbnail($path, $maxX, $maxY, $scalingup, $view)
    {
        $thumbnail = $this->getPreviewFile($view->getFileInfo($path)->getId());

        if ($this->appConfig->GetPreviews() === 'no' || $thumbnail === false) {
            return false;
        }

        $image = new Image();
        $image->loadFromData($thumbnail->getContent());

        if ($image->valid()) {
            $image->scaleDownToFit($maxX, $maxY);
            return $image;
        }
        
        return false;
    }

    private function getPreviewFile($fileId)
    {
        try 
        {
            return $this->appData->getFolder('previews')->getFile($fileId . '.png');
        }
        catch (NotFoundException $e)
        {
            // ignore
            return false;
        }
        catch (\Exception $e)
        {
            $this->logger->error($e->getMessage(), ["message" => "Can't get preview file", "app" => $this->appName, 'level' => LogLevel::ERROR, 'exception' => $e]);
            return false;
        }
    }
}
