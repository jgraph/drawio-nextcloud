<?php

namespace OCA\Drawio\Listeners;

use OCA\Drawio\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Template\RegisterTemplateCreatorEvent;
use OCP\Files\Template\TemplateFileCreator;
use OCP\IL10N;

final class RegisterTemplateCreatorListener implements IEventListener {
	public function __construct(
		private IL10N $l10n,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof RegisterTemplateCreatorEvent)) {
			return;
		}
		$event->getTemplateManager()->registerTemplateFileCreator(function () {
			$drawio = new TemplateFileCreator(Application::APP_ID, $this->l10n->t('New draw.io Diagram'), '.drawio');
			$drawio->addMimetype('application/x-drawio');
			$drawio->setIconSvgInline(file_get_contents(__DIR__ . '/../../img/drawio.svg'));
			$drawio->setActionLabel($this->l10n->t('New draw.io Diagram'));
			return $drawio;
		});
		$event->getTemplateManager()->registerTemplateFileCreator(function () {
			$dwb = new TemplateFileCreator(Application::APP_ID, $this->l10n->t('New draw.io Whiteboard'), '.dwb');
			$dwb->addMimetype('application/x-drawio-wb');
			$dwb->setIconSvgInline(file_get_contents(__DIR__ . '/../../img/dwb.svg'));
			$dwb->setActionLabel($this->l10n->t('New draw.io Whiteboard'));
			return $dwb;
		});
	}
}
