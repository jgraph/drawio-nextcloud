<?php

/**
 *
 * @author Pawel Rojek <pawel at pawelrojek.com>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 * Based on Keeweb solution
 *
 * NOTE: This class and its subclasses use internal Nextcloud APIs:
 * - OC\Core\Command\Maintenance\Mimetype\UpdateJS
 * - \OC::$SERVERROOT and \OC::$configDir
 * There is no public OCP API for registering custom MIME types.
 * This is a known Nextcloud limitation shared by other apps (Keeweb, Mind Map).
 * These usages should be reviewed if Nextcloud provides a public MIME type registration API.
 *
 **/

namespace OCA\Drawio\Migration;

use OCP\Files\IMimeTypeLoader;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OC\Core\Command\Maintenance\Mimetype\UpdateJS;

abstract class MimeTypeMigration implements IRepairStep
{
    const CUSTOM_MIMETYPEMAPPING = 'mimetypemapping.json';
    const CUSTOM_MIMETYPEALIASES = 'mimetypealiases.json';

    protected $mimeTypeLoader;
    protected $updateJS;

    public function __construct(IMimeTypeLoader $mimeTypeLoader, UpdateJS $updateJS)
    {
        $this->mimeTypeLoader = $mimeTypeLoader;
        $this->updateJS = $updateJS;
    }
}
