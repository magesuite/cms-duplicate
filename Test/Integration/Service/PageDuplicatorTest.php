<?php

namespace MageSuite\CmsDuplicate\Test\Integration\Service;

class PageDuplicatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \MageSuite\CmsDuplicate\Service\PageDuplicator
     */
    protected $pageDuplicator;

    /**
     * @var \MageSuite\ContentConstructorAdmin\Repository\Xml\XmlToComponentConfigurationMapper
     */
    protected $xmlToComponentConfiguration;

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();

        $this->pageDuplicator = $this->objectManager->get(\MageSuite\CmsDuplicate\Service\PageDuplicator::class);
        $this->xmlToComponentConfiguration = $this->objectManager->get(\MageSuite\ContentConstructorAdmin\Repository\Xml\XmlToComponentConfigurationMapper::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadPagesWithBlocks
     */
    public function testItCorrectlyCopiesDataFromOldToNewPage()
    {
        $oldPageId = $this->getOriginalPageId();

        $newPage = $this->pageDuplicator->duplicate($oldPageId, 'New title', 'new_identifier');

        $this->assertEquals('New title', $newPage->getTitle());
        $this->assertEquals('new_identifier', $newPage->getIdentifier());
        $this->assertEquals('<h1>Cms Page 100 Title</h1>', $newPage->getContent());
        $this->assertEquals('1column', $newPage->getPageLayout());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadPagesWithBlocks
     */
    public function testItThrowsExceptionWhenNewIdentifierAlreadyExist()
    {
        try {
            $oldPageId = $this->getOriginalPageId();

            $newPage = $this->pageDuplicator->duplicate($oldPageId, 'New title', 'page100');

            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals('URL key for specified store already exists.', $e->getMessage());
        }
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadPagesWithBlocks
     * @expectedExceptionMessage CMS Page with id "9999999999" does not exist.
     */
    public function testItThrowsExceptionWhenOldPageDoesNotExist()
    {
        try {
            $oldPageId = 9999999999;

            $newPage = $this->pageDuplicator->duplicate($oldPageId, 'New title', 'page100');

            $this->fail();
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadPagesWithBlocks
     */
    public function testItDuplicatesBlocks()
    {
        $oldPageId = $this->getOriginalPageId();

        $firstBlock = $this->getBlock('block_1');
        $secondBlock = $this->getBlock('block_2');

        $newPage = $this->pageDuplicator->duplicate($oldPageId, 'New title', 'new_identifier', [
            [
                'componentIdentifier' => 'componentd6cc',
                'blockId' => $firstBlock->getId(),
                'blockIdentifier' => 'block_1_duplicated',
                'blockTitle' => 'Block 1 duplicated',
            ],
            [
                'componentIdentifier' => 'component0893',
                'blockId' => $secondBlock->getId(),
                'blockIdentifier' => 'block_2_duplicated',
                'blockTitle' => 'Block 2 duplicated',
            ],
        ]);

        $newFirstBlock = $this->getBlock('block_1_duplicated');
        $newSecondBlock = $this->getBlock('block_2_duplicated');

        $this->assertNotEquals($firstBlock->getId(), $newFirstBlock->getId());
        $this->assertNotEquals($secondBlock->getId(), $newSecondBlock->getId());

        $newPageComponents = $this->xmlToComponentConfiguration->map($newPage->getLayoutUpdateXml());

        $this->assertEquals($newFirstBlock->getId(), $newPageComponents[0]['data']['blockId']);
        $this->assertEquals('Block 1 duplicated', $newPageComponents[0]['data']['title']);
        $this->assertEquals($newSecondBlock->getIdentifier(), $newPageComponents[1]['data']['identifier']);
        $this->assertEquals('Block 2 duplicated', $newPageComponents[1]['data']['title']);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadPagesWithBlocks
     */
    public function testNewParagraphsAreMigratedAsIs()
    {
        $oldPageId = $this->getOriginalPageId();

        $newPage = $this->pageDuplicator->duplicate($oldPageId, 'New title', 'new_identifier');

        $newPageComponents = $this->xmlToComponentConfiguration->map($newPage->getLayoutUpdateXml());

        $this->assertEquals('paragraph', $newPageComponents[2]['type']);
        $this->assertEquals('paragraph content', $newPageComponents[2]['data']['content']);
        $this->assertEquals('1', $newPageComponents[2]['data']['migrated']);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadPagesWithBlocks
     */
    public function testItDuplicatesOnlyPassedBlocks()
    {
        $oldPageId = $this->getOriginalPageId();

        $firstBlock = $this->getBlock('block_1');
        $secondBlock = $this->getBlock('block_2');

        $newPage = $this->pageDuplicator->duplicate($oldPageId, 'New title', 'new_identifier', [
            [
                'componentIdentifier' => 'componentd6cc',
                'blockId' => $firstBlock->getId(),
                'blockIdentifier' => 'block_1_duplicated',
                'blockTitle' => 'Block 1 duplicated',
            ]
        ]);

        $newFirstBlock = $this->getBlock('block_1_duplicated');

        $newPageComponents = $this->xmlToComponentConfiguration->map($newPage->getLayoutUpdateXml());

        $this->assertEquals($newFirstBlock->getId(), $newPageComponents[0]['data']['blockId']);
        $this->assertEquals('Block 1 duplicated', $newPageComponents[0]['data']['title']);

        $this->assertEquals($secondBlock->getIdentifier(), $newPageComponents[1]['data']['identifier']);
        $this->assertEquals('Content 2', $newPageComponents[1]['data']['title']);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadPagesWithBlocks
     */
    public function testItThrowsExceptionWhenTryingToDuplicateAlreadyExistingBlock()
    {
        try {
            $oldPageId = $this->getOriginalPageId();

            $firstBlock = $this->getBlock('block_1');

            $newPage = $this->pageDuplicator->duplicate($oldPageId, 'New title', 'new_identifier', [
                [
                    'componentIdentifier' => 'componentd6cc',
                    'blockId' => $firstBlock->getId(),
                    'blockIdentifier' => 'block_2',
                    'blockTitle' => 'Block 2',
                ]
            ]);

            $this->fail();
        } catch (\Exception $e) {
            $this->assertEquals('Block with identifier block_2 already exist', $e->getMessage());
        }
    }

    protected function getOriginalPageId()
    {
        /** @var \Magento\Cms\Model\Page $page */
        $page = $this->objectManager->get(\Magento\Cms\Model\Page::class);

        return $page->load('page100', 'identifier')->getId();
    }

    protected function getBlock($identifier)
    {
        /** @var \Magento\Cms\Api\BlockRepositoryInterface $repository */
        $repository = $this->objectManager->create(\Magento\Cms\Api\BlockRepositoryInterface::class);

        return $repository->getById($identifier);
    }

    public static function loadPagesWithBlocks()
    {
        include __DIR__ . '/../_files/pages_with_blocks.php';
    }
}
