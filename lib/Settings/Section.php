<?php

namespace OCA\Drawio\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class Section implements IIconSection {
	/** @var IURLGenerator */
	private $url;
	/** @var IL10N */
	private $l10n;

	public function __construct(IURLGenerator $url, IL10N $l10n) {
		$this->url = $url;
		$this->l10n = $l10n;
	}

	public function getID() {
		return 'drawio';
	}

	public function getName() {
		return $this->l10n->t('Draw.io');
	}

	public function getPriority() {
		return 75;
	}

	public function getIcon() {
		return $this->url->imagePath('drawio', 'app-dark.svg');
	}
}
