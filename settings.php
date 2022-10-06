<?php

namespace OCA\Drawio;

use OCA\Drawio\AppInfo\Application;


User::checkAdminUser();

$app = new Application();
$container = $app->getContainer();
$response = $container->query("\OCA\Drawio\Controller\SettingsController")->index();

return $response->render();
