<?php

namespace OCA\Drawio\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Template\RegisterTemplateCreatorEvent;
use OCP\Files\Template\TemplateFileCreator;
use OCP\IL10N;

class RegisterTemplateCreatorListener implements IEventListener {

    public function __construct(
        private IL10N $l10n,
    ) {
    }

    public function handle(Event $event): void {
        if (!($event instanceof RegisterTemplateCreatorEvent)) {
            return;
        }

        $event->getTemplateManager()->registerTemplateFileCreator(function () {
            $drawio = new TemplateFileCreator('drawio', $this->l10n->t('New Diagram'), '.drawio');
            $drawio->addMimetype('application/x-drawio');
            $drawio->setIconSvgInline(file_get_contents(__DIR__ . '/../../img/drawio.svg'));
            $drawio->setActionLabel($this->l10n->t('New Diagram'));
            return $drawio;
        });

        $event->getTemplateManager()->registerTemplateFileCreator(function () {
            $dwb = new TemplateFileCreator('drawio', $this->l10n->t('New Whiteboard'), '.dwb');
            $dwb->addMimetype('application/x-drawio-wb');
            $dwb->setIconSvgInline(file_get_contents(__DIR__ . '/../../img/dwb.svg'));
            $dwb->setActionLabel($this->l10n->t('New Whiteboard'));
            return $dwb;
        });
    }
}
