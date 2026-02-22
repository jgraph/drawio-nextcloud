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

use OCA\Drawio\Controller\SettingsController;


class Admin implements IDelegatedSettings {

    private SettingsController $settingsController;

    public function __construct(SettingsController $settingsController)
    {
        $this->settingsController = $settingsController;
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
        return $this->settingsController->index();
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
