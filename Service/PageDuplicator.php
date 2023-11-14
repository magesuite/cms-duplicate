<?php

namespace MageSuite\CmsDuplicate\Service;

class PageDuplicator
{
    /**
     * @var \Magento\Cms\Api\PageRepositoryInterface
     */
    protected $pageRepository;

    /**
     * @var \Magento\Cms\Api\BlockRepositoryInterface
     */
    protected $blockRepository;

    /**
     * @var \MageSuite\ContentConstructorAdmin\Repository\Xml\XmlToComponentConfigurationMapper
     */
    protected $xmlToComponentConfigurationMapper;

    /**
     * @var \MageSuite\ContentConstructorAdmin\Repository\Xml\ComponentConfigurationToXmlMapper
     */
    protected $componentConfigurationToXmlMapper;

    public function __construct(
        \Magento\Cms\Api\PageRepositoryInterface $pageRepository,
        \Magento\Cms\Api\BlockRepositoryInterface $blockRepository,
        \MageSuite\ContentConstructorAdmin\Repository\Xml\XmlToComponentConfigurationMapper $xmlToComponentConfigurationMapper,
        \MageSuite\ContentConstructorAdmin\Repository\Xml\ComponentConfigurationToXmlMapper $componentConfigurationToXmlMapper
    )
    {
        $this->pageRepository = $pageRepository;
        $this->blockRepository = $blockRepository;
        $this->xmlToComponentConfigurationMapper = $xmlToComponentConfigurationMapper;
        $this->componentConfigurationToXmlMapper = $componentConfigurationToXmlMapper;
    }

    public function duplicate($oldPageId, $newTitle, $newIdentifier, $blocksData = []) {
        $oldPage = $this->pageRepository->getById($oldPageId);

        $duplicatedPage = clone $oldPage;
        $duplicatedPage->setId(null);
        $duplicatedPage->setOrigData('identifier', '');
        $duplicatedPage->setIdentifier($newIdentifier);
        $duplicatedPage->setTitle($newTitle);

        if(!empty($blocksData)) {
            $duplicatedBlocks = $this->duplicateBlocks($blocksData);

            $this->assignDuplicatedBlocksToComponents($duplicatedBlocks, $duplicatedPage);
        }

        $duplicatedPage->save();

        return $duplicatedPage;
    }

    /**
     * @param array $blocks
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function duplicateBlocks(array $blocks)
    {
        $this->validateIfNewBlocksIdentifiersDoesNotExist($blocks);

        foreach($blocks as &$block) {
            $blockEntity = $this->blockRepository->getById($block['blockId']);

            $blockEntity->setId(null);
            $blockEntity->setIdentifier($block['blockIdentifier']);
            $blockEntity->setTitle($block['blockTitle']);

            $blockEntity->save();

            $block['newBlockId'] = $blockEntity->getId();
        }

        return $blocks;
    }

    /**
     * @param $blocksData
     * @param $oldPage
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function assignDuplicatedBlocksToComponents($duplicatedBlocks, $oldPage)
    {
        $oldLayoutUpdateXml = $oldPage->getLayoutUpdateXml();

        $oldComponentsConfiguration = $this->xmlToComponentConfigurationMapper->map($oldLayoutUpdateXml);

        foreach ($duplicatedBlocks as $newBlock) {
            foreach ($oldComponentsConfiguration as &$component) {
                if ($component['id'] != $newBlock['componentIdentifier']) {
                    continue;
                }

                $component = $this->mapNewBlockDataToComponent($newBlock, $component);
            }
        }

        $newLayoutUpdateXml = $this->componentConfigurationToXmlMapper->map($oldComponentsConfiguration, $oldLayoutUpdateXml);

        $oldPage->setLayoutUpdateXml($newLayoutUpdateXml);
    }


    /**
     * @param $blocksData
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateIfNewBlocksIdentifiersDoesNotExist($blocksData) {
        foreach($blocksData as $block) {
            $blockIdentifier = $block['blockIdentifier'];

            if(!$this->blockExist($blockIdentifier)) {
                continue;
            }

            throw new \Magento\Framework\Exception\LocalizedException(__('Block with identifier %1 already exist', $blockIdentifier));
        }
    }

    public function blockExist($blockIdentifier) {
        try {
            $this->blockRepository->getById($blockIdentifier);

            return true;
        }
        catch(\Magento\Framework\Exception\NoSuchEntityException $noSuchEntityException) {
            return false;
        }
    }

    /**
     * @param $component
     * @param $newBlock
     * @return mixed
     */
    protected function mapNewBlockDataToComponent($newBlock, $component)
    {
        if ($component['type'] == 'paragraph') {
            $component['data']['blockId'] = $newBlock['newBlockId'];
        }

        if ($component['type'] == 'static-cms-block') {
            $component['data']['identifier'] = $newBlock['blockIdentifier'];
        }

        $component['data']['title'] = $newBlock['blockTitle'];

        return $component;
    }
}
