<?php
namespace Delivery\Shipox\Block;
class Success  extends \Magento\Framework\View\Element\Template
{
    protected $scopeConfig;
    protected $_helperData;
    protected $_customerSession;
    protected $_track;
//    protected $checkoutSession;
//    protected $_orderFactory;
//    protected $orderRepository;
//    protected $renderer;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Delivery\Shipox\Helper\Data $dataHelper,
        \Magento\Checkout\Model\Session $customerSession,
        \Delivery\Shipox\Model\Tracking $track
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_helperData = $dataHelper;
        $this->_customerSession = $customerSession;
        $this->_track = $track;
         parent::__construct($context);
    }

    /**
     * Track Order
     * @return mixed|null
     */
    public function trackOrder()
    {
        $orderId = $this->_customerSession->getLastRealOrder()->getRealOrderId();
        $shipoxOrder = $this->_track->getOrderTrackingDataByIncrementId($orderId);
        $trackingUrl  = $this->scopeConfig->getValue('tracking/myconfig/path', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (isset($shipoxOrder['shipox_order_number'])) {
            $shipoxOrderNumber = $shipoxOrder['shipox_order_number'];
        }

        if($shipoxOrder) {
            $siteUrl = $this->_helperData->getShipoxSiteURL();
            $shipoxOrder['shipox_order_url'] = $siteUrl . $trackingUrl . $shipoxOrderNumber;
        }

        return $shipoxOrder;
    }

    /**
     * Get Service Title Name
     * @return mixed
     */
    public function getServiceTitle()
    {
        return $this->_helperData->getCompanyName();
//        return $this->_scopeConfig->getValue('delivery_shipox/service/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Site Host Domain name
     * @return mixed
     */
    public function getSiteHost() {
        return $this->_scopeConfig->getValue('delivery_shipox/merchant/host', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Site Tracking URL
     * @return string
     */
    public function getSiteUrl()
    {
        return 'https://' . $this->_scopeConfig->getValue('delivery_shipox/merchant/host', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}