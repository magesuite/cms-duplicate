<?php

namespace MageSuite\CmsDuplicate\Block\Adminhtml\Duplicate;

class Form extends \Magento\Backend\Block\Template
{
    protected $_template = 'MageSuite_CmsDuplicate::form.phtml';

    /**
     * @var \Magento\Cms\Api\PageRepositoryInterface
     */
    protected $pageRepository;

    /**
     * @var \Magento\Cms\Model\Page
     */
    protected $cmsPage;

    /**
     * @var \MageSuite\ContentConstructorAdmin\Repository\Xml\XmlToComponentConfigurationMapper
     */
    protected $xmlToComponentConfigurationMapper;

    /**
     * @var \Magento\Cms\Api\BlockRepositoryInterface
     */
    protected $blockRepository;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Cms\Api\PageRepositoryInterface $pageRepository,
        \MageSuite\ContentConstructorAdmin\Repository\Xml\XmlToComponentConfigurationMapper $xmlToComponentConfigurationMapper,
        \Magento\Cms\Api\BlockRepositoryInterface $blockRepository,
        array $data = []
    )
    {
        parent::__construct($context, $data);

        $this->pageRepository = $pageRepository;
        $this->xmlToComponentConfigurationMapper = $xmlToComponentConfigurationMapper;
        $this->blockRepository = $blockRepository;
    }

    /**
     * @return \Magento\Cms\Api\Data\PageInterface|\Magento\Cms\Model\Page
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPage() {
        if(!$this->cmsPage) {
            $pageId = $this->getRequest()->get('page_id');

            $this->cmsPage = $this->pageRepository->getById($pageId);
        }

        return $this->cmsPage;
    }

    public function getBlocksToDuplicate() {
        $page = $this->getPage();

        $layoutXml = $page->getLayoutUpdateXml();

        $components = $this->xmlToComponentConfigurationMapper->map($layoutXml);

        $blocks = [];

        foreach($components as $component) {
            if(!in_array($component['type'], ['paragraph','static-cms-block'])) {
                continue;
            }

            $blockData = $this->getBlockData($component);

            $blocks[] = $blockData;
        }

        return $blocks;
    }

    protected function getBlockData($componentConfiguration) {
        if($componentConfiguration['type'] == 'paragraph') {
            $blockId = $componentConfiguration['data']['blockId'];
        }
        else if($componentConfiguration['type'] == 'static-cms-block') {
            $blockId = $componentConfiguration['data']['identifier'];
        }

        $block = $this->blockRepository->getById($blockId);

        return [
            'componentId' => $componentConfiguration['id'],
            'id' => $block->getId(),
            'title' => $block->getTitle(),
            'identifier' => $block->getIdentifier()
        ];
    }

}
