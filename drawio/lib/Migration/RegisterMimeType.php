<?php

/**
 *
 * @author Pawel Rojek <pawel at pawelrojek.com>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 * Based on Keeweb solution and Mind Map App
 *
 **/

namespace OCA\Drawio\Migration;

require \OC::$SERVERROOT . "/3rdparty/autoload.php";

use OCP\Files\IMimeTypeLoader;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OC\Core\Command\Maintenance\Mimetype\UpdateJS;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class RegisterMimeType extends MimeTypeMigration
{
    public function getName()
    {
        return 'Register MIME types for Draw.io';
    }

    private function registerForExistingFiles()
    {
        $mimeTypeId = $this->mimeTypeLoader->getId('application/x-drawio');
        $this->mimeTypeLoader->updateFilecache('drawio', $mimeTypeId);

        $mimeTypeId = $this->mimeTypeLoader->getId('application/x-drawio-wb');
        $this->mimeTypeLoader->updateFilecache('dwb', $mimeTypeId);
    }

    private function registerForNewFiles()
    {
        $configDir = \OC::$configDir;
        $mimetypealiasesFile = $configDir . self::CUSTOM_MIMETYPEALIASES;
        $mimetypemappingFile = $configDir . self::CUSTOM_MIMETYPEMAPPING;

        $this->appendToFile($mimetypealiasesFile, ['application/x-drawio' => 'drawio', 'application/x-drawio-wb' => 'dwb']);
        $this->appendToFile($mimetypemappingFile, ['drawio' => ['application/x-drawio'], 'dwb' => ['application/x-drawio-wb']]);
    }

    private function copyIcons()
    {
        $icons = ['drawio', 'dwb'];

        foreach ($icons as $icon) 
        {
            $source = __DIR__ . '/../../img/' . $icon . '.svg';
            $target = \OC::$SERVERROOT . '/core/img/filetypes/' . $icon . '.svg';
            if (!file_exists($target) || md5_file($target) !== md5_file($source)) 
            {
                copy($source, $target);
            }
        }
    }

    public function run(IOutput $output)
    {
        $output->info('Registering the mimetype...');

        // Register the mime type for existing files
        $this->registerForExistingFiles();

        // Register the mime type for new files
        $this->registerForNewFiles();

        $output->info('The mimetype was successfully registered.');

        $output->info('Copy drawio icons to core/img directory.');
        $this->copyIcons();

        $this->updateJS->run(new StringInput(''), new ConsoleOutput());
    }

    private function appendToFile(string $filename, array $data) {
        $obj = [];
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            $obj = json_decode($content, true);
        }
        foreach ($data as $key => $value) {
            $obj[$key] = $value;
        }
        file_put_contents($filename, json_encode($obj,  JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }
}
