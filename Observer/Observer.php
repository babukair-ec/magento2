<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 26.07.2018
 * Time: 21:21
 */

namespace Delivery\Shipox\Observer;


use Delivery\Shipox\Helper\Client;
use Delivery\Shipox\Helper\ShipoxLogger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use \Delivery\Shipox\Helper\Data;
use \Delivery\Shipox\Helper\Dbclient;
use \Delivery\Shipox\Model\Carrier;
use \Delivery\Shipox\Model\Shipox;

class Observer implements ObserverInterface {
    protected $_code = 'delivery';
    protected $_logFile = 'shipox_observer.log';
    protected $scopeConfig;
    protected $_client;
    protected $_dbClient;
    protected $_carrier;
    protected $_timezone;
    protected $_storeManager;
    protected $_data;
    protected $_shipox;
    protected $_helperClient;
    protected $_shipoxLogger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $client,
        Dbclient $dbclient,
        Carrier $carrier,
        TimezoneInterface $timezone,
        StoreManagerInterface $storeManager,
        Data $data,
        Shipox $shipox,
        Client $helperClient,
        ShipoxLogger $shipoxLogger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_client = $client;
        $this->_dbClient = $dbclient;
        $this->_carrier = $carrier;
        $this->_timezone = $timezone;
        $this->_storeManager = $storeManager;
        $this->_data = $data;
        $this->_shipox = $shipox;
        $this->_helperClient = $helperClient;
        $this->_shipoxLogger = $shipoxLogger;
    }

    public function paymentMethodIsActive(EventObserver $observer)
    {

    }

    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function pushOrderAfterShipmentCreation(EventObserver $observer)
    {
        $this->_shipoxLogger->setFileName("shipox_observer");

        $shipoxDBClient = $this->_dbClient;
        $shipoxHelper = $this->_client;
        $shipoxCarrier = $this->_carrier;
        $date = $this->_timezone->date()->getTimestamp();
        $tokenDate = $this->scopeConfig->getValue('delivery_shipox/auth/token_time', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $timeDiff = $date - $tokenDate;
        $hours = floor($timeDiff / 3600);
        if ($hours > 23) {
            $userCred = array(
                'username' => $this->scopeConfig->getValue('general/auth/user_name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'password' => $this->scopeConfig->getValue('general/auth/password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            );

            $responsedData = $this->_helperClient->authenticate($userCred);

            if ($responsedData['status'] == 'success') {
                $data = $responsedData['data'];
                $this->_configWriter->save('delivery_shipox/auth/jwt_token', $data['id_token']);
                $this->_configWriter->save('delivery_shipox/auth/token_time', $this->_timezone->date()->getTimestamp());
                $this->_helperClient->updateCustomerMarketplace();
            }
        }


        $orderId = $observer->getEvent()->getOrderIds();
        $order = $this->_order->load($orderId);

        if ($order->getExportProcessed()) { //check if flag is already set.
            return $this;
        }

        $order->setExportProcessed(true);

        $this->_shipoxLogger->setFileName($this->_logFile);
        $packageId = $packagePriceId = $isDomestic = $toCountry = 0;

        $quoteId = $order->getQuoteId();

        $items = $order->getAllItems();

        $weight = 0;
//        foreach ($items as $item) {
//            $weight += ($item->getWeight() * $item->getQty());
//        }

        $country_code = $order->getShippingAddress()->getCountryId();
        $shippingMethod = $order->getShippingMethod();

        $shippingMethodArray = explode("_", $shippingMethod);
        $shipoxOrderModel = $shipoxDBClient->getData($quoteId);
        $quote = $this->quoteRepository->get($quoteId);

        if ($shippingMethodArray[0] == $this->_code) {
            $packageString = $shippingMethodArray[1];
            $packagePriceString = $shippingMethodArray[2];
            $toCountryString = $shippingMethodArray[3];
            $domesticString = $shippingMethodArray[4];

            if (strpos($packageString, 'package-') > -1) {
                $packageId = explode("-", $packageString)[1];
            }
            if (strpos($packagePriceString, 'price-') > -1) {
                $packagePriceId = explode("-", $packagePriceString)[1];
            }
            if (strpos($domesticString, 'domestic-') > -1) {
                $isDomestic = explode("-", $domesticString)[1];
            }
            if (strpos($toCountryString, 'country-') > -1) {
                $toCountry = explode("-", $toCountryString)[1];
            }

            if ($packageId > 0 && $packagePriceId > 0 && $toCountry > 0) {
                $customerLatLonAddress = $shipoxHelper->extractLatLonArrayFromString($shipoxOrderModel->getDestinationLatlon());
                if ($customerLatLonAddress) {

                    $packageData = array(
                        'package_id' => $packageId,
                        'package_price_id' => $packagePriceId,
                        'to_location' => $shipoxOrderModel->getDestinationLatlon(),
                        'weight' => ($weight != 0) ? $weight : $this->scopeConfig->getValue('carriers/' . $this->_code . '/default_weight', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                        'domestic' => $isDomestic,
                        'to_country' => $toCountry,
                    );

                    $responseOrder = $shipoxHelper->pushShipoxOrderV2($order, $packageData, $customerLatLonAddress, $shipoxOrderModel->getData());

                    if (!empty($responseOrder)) {
                        $shipoxOrderModel->setShipoxPackageId($packageId);
                        $shipoxOrderModel->setShipoxOrderId($responseOrder['id']);
                        $shipoxOrderModel->setOrderId($order->getId());
                        $shipoxOrderModel->setShipoxOrderNumber($responseOrder['order_number']);
                        $shipoxOrderModel->setShipoxOrderStatus($responseOrder['status']);
                        $shipoxOrderModel->setCompletedAt($date);
                        $shipoxOrderModel->setIsCompleted(1);
                        $shipoxOrderModel->setActiveOrder(1);

                        $shipoxOrderModel->save();

                        $itemsQuantity = $shipoxHelper->generateProductQuantityArray($order);
                        $shipoxCarrier->setShipmentAndTrackingNumberOnShipmentV2($order, $responseOrder['order_number']);
                        $shipoxCarrier->setShipmentAndTrackingNumberOnInvoice($order, $itemsQuantity);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @param EventObserver $observer
     */
    public function customerOrderShipoxTrackerBlock(EventObserver $observer)
    {
        $block = $observer->getEvent()->getBlock();

        if (($block->getNameInLayout() == 'sales.order.info') && ($child = $block->getChild('shipox.order.info.customer'))) {
            $transport = $observer->getEvent()->getData();
            if ($transport) {
                $html = $transport->getHtml();
                $html .= $child->toHtml();
                $transport->setHtml($html);
            }
        }
    }

    /**
     * Order info block for Admin Panel
     * @param EventObserver $observer
     */
    public function getSalesOrderViewInfo(EventObserver $observer)
    {
        $block = $observer->getEvent()->getBlock();

        if (($block->getNameInLayout() == 'order_info') && ($child = $block->getChild('shipox.order.info.block'))) {
            $transport = $observer->getData();
            if ($transport) {
                $html = $transport->getHtml();
                $html .= $child->toHtml();
                $transport->setHtml($html);
            }
        }

        if (($block->getNameInLayout() == 'order_info') && ($child = $block->getChild('shipox.order.shipment.info.block'))) {
            $transport = $observer->getData();
            if ($transport) {
                $html = $transport->getHtml();
                $html .= $child->toHtml();
                $transport->setHtml($html);
            }
        }
    }

    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function getOrderWhenCancelled(EventObserver $observer)
    {
        $order = $observer->getData('order');

        if (!$order->getId()) {
            return $this;
        }

        $oldStatus = $order->getOrigData('status');
        $newStatus = $order->getStatus();

        if(($oldStatus != $newStatus) && ($newStatus == 'canceled')) {
            $shipoxHelper = $this->_data;

            $order = $observer->getData('order');
            $shipoxHelper->cancelShipoxOrder($order, 'Magento Order cancelled event fired. Merchant cancelling the Order');
        }
        return $this;
    }

    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        // TODO: Implement execute() method.

//        $this->pushOrderAfterShipmentCreation($observer);
    }
}