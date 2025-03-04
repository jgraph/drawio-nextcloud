<?php

namespace OCA\Drawio\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Folder;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class FileDeleteListener implements IEventListener {

    /** @var LoggerInterface */
    private $logger;

    /** @var IAppData */
    private $appData;

	public function __construct(LoggerInterface $logger, IAppData $appData)
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
            $this->appData->getFolder('previews')->getFile($node->getId() . '.png')->delete();
        }
        catch (NotFoundException $e)
        {
            // ignore
            return;
        }
        catch (\Exception $e)
        {
            // ignore
            $this->logger->error($e->getMessage(), ["message" => "Can't delete preview for file: " . $node->getPath(), "app" => 'drawio', 'level' => LogLevel::ERROR, 'exception' => $e]);
        }
	}
}
