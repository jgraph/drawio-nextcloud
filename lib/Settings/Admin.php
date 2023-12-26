<?php

/**
 *
 * @author Pawel Rojek <pawel at pawelrojek.com>
 * @author Ian Reinhart Geiser <igeiser at devonit.com>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 **/

namespace OCA\Drawio\Settings;

use OCP\Settings\IDelegatedSettings;

use OCA\Drawio\AppInfo\Application;


class Admin implements IDelegatedSettings {

    public function __construct()
    {
    }

    public function getName(): ?string {
        return null;
    }

    public function getAuthorizedAppConfig(): array {
        return [
            'drawio' => ['/drawio.*/'],
        ];
    }

    public function getForm()
    {
        $app = new Application();
        $container = $app->getContainer();
        $response = $container->query("\OCA\Drawio\Controller\SettingsController")->index();
        return $response;
    }

    public function getSection()
    {
        return "drawio";
    }

    public function getPriority()
    {
        return 60;
    }
}
