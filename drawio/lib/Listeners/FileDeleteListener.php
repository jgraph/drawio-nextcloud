<?php

namespace OCA\Drawio\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Folder;
use OCP\Files\IAppData;
use OCP\ILogger;

class FileDeleteListener implements IEventListener {

    /** @var ILogger */
    private $logger;

    /** @var IAppData */
    private $appData;

	public function __construct(ILogger $logger, IAppData $appData)
    {
        $this->logger = $logger;
        $this->appData = $appData;
    }

	public function handle(Event $event): void {
		if (!($event instanceof NodeDeletedEvent)) {
			return;
		}

		$node = $event->getNode();

		if ($node instanceof Folder) {
			return;
		}

        try 
        {
            $prevFolder = $this->appData->getFolder('previews');
            $prevFolder->getFile($node->getId() . '.png')->delete();
            $this->logger->error('File preview deleted for: ' . $node->getPath(),
                    ['app' => 'drawio']);
        }
        catch (\Exception $e)
        {
            // ignore
            $this->logger->logException($e, ["message" => "Can't delete preview for file: " . $node->getPath(), "app" => 'drawio']);
        }
	}
}