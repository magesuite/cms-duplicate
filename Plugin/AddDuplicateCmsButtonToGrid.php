<?php

namespace MageSuite\CmsDuplicate\Plugin;

class AddDuplicateCmsButtonToGrid
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    public function __construct(\Magento\Framework\UrlInterface $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }

    public function afterPrepareDataSource(\Magento\Cms\Ui\Component\Listing\Column\PageActions $subject, $result)
    {
        if (!isset($result['data']['items'])) {
            return $result;
        }

        foreach ($result['data']['items'] as & $item) {
            $name = $subject->getData('name');

            if (!isset($item['identifier'])) {
                continue;
            }

            $formUrl = $this->urlBuilder->getUrl('cmspageduplicate/duplicate/form', ['page_id' => $item['page_id']]);

            $params = [
                $item['page_id'],
                $this->quoteValue($formUrl)
            ];

            $item[$name]['duplicate'] = [
                'href' => 'javascript: openDuplicateCmsPageModal(' . implode(',', $params) . ')',
                'label' => __('Duplicate')
            ];
        }


        return $result;
    }

    protected function quoteValue($value) {
        return sprintf("'%s'", $value);
    }
}