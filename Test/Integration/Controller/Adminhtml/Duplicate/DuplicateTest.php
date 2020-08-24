<?php

namespace MageSuite\CmsDuplicate\Test\Integration\Controller\Adminhtml\Duplicate;

class DuplicateTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Cms\Api\Data\PageInterface
     */
    protected $page;

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->messageManager = $this->objectManager->get(\Magento\Framework\Message\ManagerInterface::class);
        $this->page = $this->objectManager->get(\Magento\Cms\Api\Data\PageInterface::class);

        parent::setUp();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoAppArea adminhtml
     * @magentoDataFixture loadPagesWithBlocks
     */
    public function testItDuplicatesPageWithBlocks()
    {
        $originalFirstBlock = $this->getBlock('block_1');

        $this->getRequest()->setMethod(\Magento\Framework\App\Request\Http::METHOD_POST);
        $this->getRequest()->setPostValue([
            'page_id' => $this->getOriginalPageId(),
            'identifier' => 'duplicated_page',
            'title' => 'Duplicated page title',
            'blockId' => [
                'componentd6cc' => $originalFirstBlock->getId(),
                'component0893' => $this->getBlock('block_2')->getId(),
            ],
            'blockCopy' => ['componentd6cc', 'component0893'],
            'blockIdentifier' => [
                'componentd6cc' => 'block_1_duplicate',
                'component0893' => 'block_2_duplicate',
            ],
            'blockTitle' => [
                'componentd6cc' => 'Block 1 duplicate',
                'component0893' => 'Block 2 duplicate',
            ]
        ]);


        $this->dispatch('backend/cmspageduplicate/duplicate/duplicate');

        $content = json_decode($this->getResponse()->getBody(), true);
        $duplicatedFirstBlock = $this->getBlock('block_1_duplicate');

        $this->assertTrue($content['success']);
        $this->assertNotEquals($originalFirstBlock->getId(), $duplicatedFirstBlock->getId());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoAppArea adminhtml
     * @magentoDataFixture loadPagesWithBlocks
     */
    public function testItThrowsErrorWhenIdentifierIsTheSame()
    {
        $this->getRequest()->setMethod(\Magento\Framework\App\Request\Http::METHOD_POST);
        $this->getRequest()->setPostValue([
            'page_id' => $this->getOriginalPageId(),
            'identifier' => 'page100',
            'title' => 'Duplicated page title',
        ]);

        $this->dispatch('backend/cmspageduplicate/duplicate/duplicate');

        $content = json_decode($this->getResponse()->getBody(), true);

        $assertContains = method_exists($this, 'assertStringContainsString') ? 'assertStringContainsString' : 'assertContains';

        $this->assertFalse($content['success']);
        $this->$assertContains('URL key for specified store already exists', $content['errorMessage']);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoAppArea adminhtml
     * @magentoDataFixture loadPagesWithBlocks
     */
    public function testItThrowsErrorWhenOldPageDoesNotExist()
    {
        $this->getRequest()->setMethod(\Magento\Framework\App\Request\Http::METHOD_POST);
        $this->getRequest()->setPostValue([
            'page_id' => 9999999999,
            'identifier' => 'duplicated_page',
            'title' => 'Duplicated page title',
        ]);

        $this->dispatch('backend/cmspageduplicate/duplicate/duplicate');

        $content = json_decode($this->getResponse()->getBody(), true);

        $this->assertFalse($content['success']);
        $this->assertNotEmpty($content['errorMessage']);
        $this->assertFalse($this->page->checkIdentifier('duplicated_page', 0));
    }

    protected function getOriginalPageId()
    {
        /** @var \Magento\Cms\Model\Page $page */
        $page = $this->objectManager->get(\Magento\Cms\Model\Page::class);

        return $page->load('page100', 'identifier')->getId();
    }

    public static function loadPagesWithBlocks()
    {
        include __DIR__ . '/../../../_files/pages_with_blocks.php';
    }

    protected function getBlock($identifier)
    {
        /** @var \Magento\Cms\Api\BlockRepositoryInterface $repository */
        $repository = $this->objectManager->create(\Magento\Cms\Api\BlockRepositoryInterface::class);

        return $repository->getById($identifier);
    }
}
