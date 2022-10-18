<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 27.07.2018
 * Time: 8:40
 */

namespace Delivery\Shipox\Block\Adminhtml\Sales\Order\Shipment\Info;
use \Magento\Framework\View\Element\Template;

class Block extends Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
    }

    public function getOrder() {

        return "Shipment Block";
    }
}