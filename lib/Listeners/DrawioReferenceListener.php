<?php

namespace OCA\Drawio\Listeners;

use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class DrawioReferenceListener implements IEventListener {

    public function handle(Event $event): void {
        if (!$event instanceof RenderReferenceEvent) {
            return;
        }

        Util::addScript('drawio', 'drawio-reference');
    }
}
