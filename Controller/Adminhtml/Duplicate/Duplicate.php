<?php

namespace MageSuite\CmsDuplicate\Controller\Adminhtml\Duplicate;

class Duplicate extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Magento_Cms::save';

    const SUCCESS_MESSAGE = 'Page was successfully duplicated. You can edit it by <a href="%1">clicking here</a>.';
    const ERROR_MESSAGE = 'Error occured while trying to duplicate CMS page: %1';

    /**
     * @var \MageSuite\CmsDuplicate\Service\PageDuplicator
     */
    protected $pageDuplicator;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \MageSuite\CmsDuplicate\Service\PageDuplicator $pageDuplicator,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);

        $this->pageDuplicator = $pageDuplicator;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();

        if ($data) {
            try {
                $blockData = $this->getBlockData($data);

                $connection = $this->resourceConnection->getConnection();
                $connection->beginTransaction();

                $newPage = $this->pageDuplicator->duplicate($data['page_id'], $data['title'], $data['identifier'], $blockData);
                $editUrl = $this->getEditUrl($newPage);

                $connection->commit();

                $this->messageManager->addSuccess(__(self::SUCCESS_MESSAGE, $editUrl));

                $result->setData(['success' => true]);
            } catch (\Exception $e) {
                $connection->rollBack();

                $result->setData([
                    'success' => false,
                    'errorMessage' => __(self::ERROR_MESSAGE, $e->getMessage())
                ]);
            }
        }

        return $result;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }

    /**
     * @param $oldPage
     * @return string
     */
    protected function getEditUrl($oldPage)
    {
        return $this->_url->getUrl(
            \Magento\Cms\Ui\Component\Listing\Column\PageActions::CMS_URL_PATH_EDIT,
            ['page_id' => $oldPage->getId()]
        );
    }

    protected function getBlockData($data)
    {
        if (!isset($data['blockCopy']) or empty($data['blockCopy'])) {
            return [];
        }

        $blocks = [];

        foreach ($data['blockCopy'] as $componentIdentifier) {
            $blocks[] = [
                'componentIdentifier' => $componentIdentifier,
                'blockId' => $data['blockId'][$componentIdentifier],
                'blockIdentifier' => $data['blockIdentifier'][$componentIdentifier],
                'blockTitle' => $data['blockTitle'][$componentIdentifier],
            ];
        }

        return $blocks;
    }
}
