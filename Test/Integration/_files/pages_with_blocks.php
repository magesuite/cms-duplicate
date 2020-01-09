<?php

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Cms\Model\Block $block */
$block = $objectManager->create(\Magento\Cms\Model\Block::class);
$block->setTitle('Block 1')
    ->setContent('Content 1')
    ->setIdentifier('block_1')
    ->save();

$firstBlockId = $block->getId();
$firstBlockIdentifier = $block->getIdentifier();

$block = $objectManager->create(\Magento\Cms\Model\Block::class);
$block->setTitle('Block 2')
    ->setContent('Content 2')
    ->setIdentifier('block_2')
    ->save();

$secondBlockId = $block->getId();
$secondBlockIdentifier = $block->getIdentifier();

/** @var \MageSuite\ContentConstructorAdmin\Repository\Xml\ComponentConfigurationToXmlMapper $componentConfigurationToXmlMapper */
$componentConfigurationToXmlMapper = $objectManager->create(\MageSuite\ContentConstructorAdmin\Repository\Xml\ComponentConfigurationToXmlMapper::class);

$xml = $componentConfigurationToXmlMapper->map([
    [
        'id' => 'componentd6cc',
        'section' => 'content',
        'type' => 'paragraph',
        'data' => [
            'blockId' => $firstBlockId,
            'title' => 'Content 1',
            'columns' => 'none',
        ],
    ],
    [
        'id' => 'component0893',
        'section' => 'content',
        'type' => 'static-cms-block',
        'data' => [
            'identifier' => $secondBlockIdentifier,
            'title' => 'Content 2',
        ],
    ],
    [
        'id' => 'componentd464',
        'section' => 'content',
        'type' => 'paragraph',
        'name' => 'Paragraph',
        'data' =>
            [
                'title' => '',
                'columns' => 'none',
                'scenarios' =>
                    [
                        'reading' =>
                            [
                            ],
                    ],
                'content' => 'paragraph content',
                'migrated' => '1',
                'componentVisibility' =>
                    [
                        'mobile' => '1',
                        'desktop' => '1',
                    ],
            ],
    ],
]);

/** @var $page \Magento\Cms\Model\Page */
$page = $objectManager->create(\Magento\Cms\Model\Page::class);
$page->setTitle('Cms Page 100')
    ->setIdentifier('page100')
    ->setStores([0])
    ->setIsActive(1)
    ->setContent('<h1>Cms Page 100 Title</h1>')
    ->setPageLayout('1column')
    ->setLayoutUpdateXml($xml)
    ->save();

/** @var $page \Magento\Cms\Model\Page */
$page = $objectManager->create(\Magento\Cms\Model\Page::class);
$page->setTitle('Cms Page Design Blank')
    ->setIdentifier('page_design_blank')
    ->setStores([0])
    ->setIsActive(1)
    ->setContent('<h1>Cms Page Design Blank Title</h1>')
    ->setPageLayout('1column')
    ->setCustomTheme('Magento/blank')
    ->save();
