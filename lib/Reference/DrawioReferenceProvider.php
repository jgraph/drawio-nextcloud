<?php

namespace OCA\Drawio\Reference;

use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\ISearchableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\Reference;
use OCP\Files\IRootFolder;
use OCP\Files\FileInfo;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Share\IManager as IShareManager;
use Psr\Log\LoggerInterface;

class DrawioReferenceProvider extends ADiscoverableReferenceProvider implements ISearchableReferenceProvider {

    private const RICH_OBJECT_TYPE = 'drawio_diagram';

    public function __construct(
        private IL10N $l10n,
        private IURLGenerator $urlGenerator,
        private IRootFolder $rootFolder,
        private IUserSession $userSession,
        private IShareManager $shareManager,
        private LoggerInterface $logger,
    ) {
    }

    public function getId(): string {
        return 'drawio-diagram';
    }

    public function getTitle(): string {
        return $this->l10n->t('Draw.io Diagrams');
    }

    public function getOrder(): int {
        return 10;
    }

    public function getIconUrl(): string {
        return $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->imagePath('drawio', 'app.svg')
        );
    }

    public function getSupportedSearchProviderIds(): array {
        return ['files'];
    }

    public function matchReference(string $referenceText): bool {
        $baseUrl = $this->urlGenerator->getAbsoluteURL('/apps/drawio/edit');
        $baseUrlIndex = $this->urlGenerator->getAbsoluteURL('/index.php/apps/drawio/edit');

        return str_starts_with($referenceText, $baseUrl)
            || str_starts_with($referenceText, $baseUrlIndex);
    }

    public function resolveReference(string $referenceText): ?IReference {
        if (!$this->matchReference($referenceText)) {
            return null;
        }

        $params = $this->parseUrlParams($referenceText);
        $fileId = $params['fileId'] ?? null;
        $shareToken = $params['shareToken'] ?? null;

        if ($fileId === null) {
            return null;
        }

        try {
            if (!empty($shareToken)) {
                return $this->resolveWithShareToken($referenceText, (int)$fileId, $shareToken);
            }

            $user = $this->userSession->getUser();
            if ($user === null) {
                return null;
            }

            $userFolder = $this->rootFolder->getUserFolder($user->getUID());
            $files = $userFolder->getById((int)$fileId);

            if (empty($files)) {
                return null;
            }

            return $this->buildReference($referenceText, $files[0]);
        } catch (\Exception $e) {
            $this->logger->debug('Could not resolve draw.io reference: ' . $e->getMessage(), ['app' => 'drawio']);
            return null;
        }
    }

    public function getCachePrefix(string $referenceText): string {
        $params = $this->parseUrlParams($referenceText);
        return ($params['fileId'] ?? '') . '-' . ($params['shareToken'] ?? '');
    }

    public function getCacheKey(string $referenceText): ?string {
        return null;
    }

    private function parseUrlParams(string $url): array {
        $query = parse_url($url, PHP_URL_QUERY) ?? '';
        parse_str($query, $params);
        return $params;
    }

    private function resolveWithShareToken(string $referenceText, int $fileId, string $shareToken): ?IReference {
        try {
            $share = $this->shareManager->getShareByToken($shareToken);
        } catch (\Exception $e) {
            return null;
        }

        $node = $share->getNode();

        if ($node->getType() === FileInfo::TYPE_FOLDER) {
            $files = $node->getById($fileId);
            if (empty($files)) {
                return null;
            }
            $node = $files[0];
        }

        return $this->buildReference($referenceText, $node);
    }

    private function buildReference(string $referenceText, $file): IReference {
        $reference = new Reference($referenceText);
        $reference->setTitle($file->getName());
        $reference->setDescription($this->l10n->t('Draw.io diagram'));

        $previewUrl = $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->linkToRoute('core.Preview.getPreviewByFileId', [
                'fileId' => $file->getId(),
                'x' => 600,
                'y' => 400,
                'a' => true,
            ])
        );
        $reference->setImageUrl($previewUrl);

        $reference->setRichObject(self::RICH_OBJECT_TYPE, [
            'id' => $file->getId(),
            'name' => $file->getName(),
            'mime' => $file->getMimeType(),
            'previewUrl' => $previewUrl,
            'editUrl' => $referenceText,
        ]);

        return $reference;
    }
}
