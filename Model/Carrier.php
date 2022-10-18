<?php
/**
 * @category   Shipox - Carrier Type
 * @package    Delivery_Shipox
 * @author     Shipox Delivery - Furkat Djamolov
 * @website    www.shipox.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 **/

namespace Delivery\Shipox\Model;

use Delivery\Shipox\Helper\ShipoxLogger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;


class Carrier extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'delivery';
    protected $_result = null;
    protected $_logFile = 'shipox_carrier.log';
    protected $logger;

    protected $_scopeConfig;
    protected $_checkoutSession;
    protected $_client;
    protected $_dbclient;
    protected $_clientHelper;
    protected $_methodFactory;
    protected $_rateResultMethod;
    protected $_resultStatus;
    protected $_track;
    protected $_orderInterface;
    protected $_trackingFactory;
    protected $_transaction;
    protected $_invoceFactory;
    protected $_errorFactory;
    protected $_quote;
    protected $_resultFactory;


    public function __construct(
        ErrorFactory $errorFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Delivery\Shipox\Helper\Data $client,
        \Delivery\Shipox\Helper\Dbclient $dbclient,
        \Delivery\Shipox\Helper\Client $clientHelper,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $methodFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\Method $rateResultMethod,
        \Magento\Shipping\Model\Tracking\Result\Status $resultStatus,
        \Magento\Sales\Model\Convert\Order $orderInterface,
        \Magento\Sales\Model\Order\Shipment\Track $trackFactory,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Sales\Api\Data\InvoiceCommentInterfaceFactory $invoiceFactory,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Shipping\Model\Rate\ResultFactory $resultFactory,
        ShipoxLogger $shipoxLogger
    )
    {

        $this->_scopeConfig = $scopeConfig;
        $this->_checkoutSession = $checkoutSession;
        $this->_client = $client;
        $this->_dbclient = $dbclient;
        $this->_clientHelper = $clientHelper;
        $this->_methodFactory = $methodFactory;
        $this->_rateResultMethod = $rateResultMethod;
        $this->_resultStatus = $resultStatus;
        $this->_track = $trackFactory;
        $this->_orderInterface = $orderInterface;
        $this->_transaction = $transaction;
        $this->_invoceFactory = $invoiceFactory;
        $this->_errorFactory = $errorFactory;
        $this->_quote = $quote;
        $this->_resultFactory = $resultFactory;
        $this->logger = $shipoxLogger;

    }

    /**
     * Collect and get rates
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return \Magento\Shipping\Model\Rate\Result|bool|null
     */
    public function collectRates(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        if (!$this->_scopeConfig->getValue('carriers/' . $this->_code . '/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            return false;
        }

        $result = $this->getShipoxRates($request);

        return $result;

    }

    public function checkAvailableShipCountries(\Magento\Framework\DataObject $request)
    {
        return true;
    }

//
    public function processAdditionalValidation(\Magento\Framework\DataObject $request)
    {
        return true;
    }

    /**
     * @param $request
     * @return false|\Magento\Framework\Model\AbstractModel
     */
    public function getShipoxRates($request)
    {
        $this->logger->setFileName($this->_logFile);
        $shippingAddress = null;
        $priceArr = array();

        $shipoxHelper = $this->_client;
        $shipoxDBClient = $this->_dbclient;

        $quote = $this->_checkoutSession->getQuote();

        $packageWeight = $shipoxHelper->getPackageWeightForShipox($request->getPackageWeight());

//        if (!$shipoxHelper->isAllowedSystemCurrency()){
//            return false;
//        }

        $shippingAddress = $quote->getShippingAddress();

        if ($shippingAddress) {
            $countryCode = !empty($request->getDestCountryId())  ? $request->getDestCountryId() : $shippingAddress->getCountry();

            $countryId = $shipoxHelper->getCountryShipoxId($countryCode);
            if (!$shipoxHelper->isInternationalAvailable($countryId))
                return false;

//            $this->logger->message("Shipping Address");
//            $this->logger->message($request->getDestCountryId());

            if ($shipoxHelper->isAllowedCountry($countryId)) {
                {
                    $shippingAddressArray = array(
                        'countryCode' => $countryCode,
                        'region' => $request->getDestRegion(),
                        'city' => $request->getDestCity(),
                        'street' => $request->getDestStreet(),
                    );
                    $customerLatLonAddress = $shipoxHelper->getAddressLocation($shippingAddressArray, true);

                    if (!empty($customerLatLonAddress)) {
                        $isDomestic = $this->_client->isDomestic($countryId);

                        $additionalData = array('is_domestic' => $isDomestic, 'to_country_id' => $countryId);
                        $packagePrices = $shipoxHelper->getPackagePriceListNewModel($customerLatLonAddress, $additionalData, null, $quote);

                        if ($packagePrices) {
                            $data = array(
                                'quote_id' => $quote->getData('entity_id'),
                                'shipox_menu_id' => 1,
                                'destination' => $shipoxHelper->getFullDestination($shippingAddressArray, true),
                                'destination_latlon' => $customerLatLonAddress['lat'] . "," . $customerLatLonAddress['lon']
                            );

                            if ($shipoxDBClient->insertData($data)) {
                                foreach ($packagePrices as $listItem) {
                                    $packageId = $listItem['id'];
                                    $packagePriceId = $listItem['price']['id'];
                                    $methodType = 'package-' . $packageId . '_price-' . $packagePriceId . '_country-' . $countryId . '' . '_domestic-' . ($isDomestic ? '1' : '0');

                                    $name = $listItem['name'];
                                    $courierName = (isset($listItem['supplier'])) ? ' - ' . $listItem['supplier']['name'] : '';
                                    $price = $listItem['price']['total'];
                                    $response['type'] = 'success';

                                    $priceArr[$methodType] = array('label' => $name . $courierName, 'amount' => $price);
                                }
                            } else {
                                foreach ($packagePrices as $listItem) {
                                    $packageId = $listItem['id'];
                                    $packagePriceId = $listItem['price']['id'];
                                    $methodType = 'package-' . $packageId . '_price-' . $packagePriceId . '_country-' . $countryId . '' . '_domestic-' . ($isDomestic ? '1' : '0');

                                    $name = $listItem['name'];
                                    $courierName = (isset($listItem['supplier'])) ? ' - ' . $listItem['supplier']['name'] : '';
                                    $price = $listItem['price']['total'];
                                    $response['type'] = 'success';

                                    $priceArr[$methodType] = array('label' => $name . $courierName, 'amount' => $price);
                                }
                            }

                        }
                    }
                }
            }
        }

        if (!empty($priceArr)) {
            $result = $this->_resultFactory->create();
            foreach ($priceArr as $methodType => $values) {
                $method = $this->_methodFactory->create();
                $method->setCarrier($this->_code);
                $method->setCarrierTitle($this->_client->getCompanyName());
                $method->setMethod($methodType);
                $method->setMethodTitle($values['label']);
                $method->setPrice($values['amount']);
                $method->setCost($values['amount']);
                $result->append($method);
            }
            return $result;
        }
        return false;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * @return bool
     */
    public function isTrackingAvailable()
    {
        return true;
    }


    /**
     * @param $tracking
     * @return false|\Magento\Framework\Model\AbstractModel
     */
    public function getTrackingInfo($tracking)
    {
        $helper = $this->_client;
        $track = $this->_resultStatus;

        $track->setData('url', $helper->getShipoxSiteURL() . $this->_scopeConfig->getValue('tracking/myconfig/path', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) . $tracking);
        $track->setData('tracking', $tracking);
        $track->setData('carrier_title', $this->getData('title'));

        return $track;
    }

    public function setShipmentAndTrackingNumberOnShipmentV2($order, $shipoxOrderNumber)
    {

        $data = array();
        $data['carrier_code'] = $this->_code;
        $data['title'] = $this->getConfigData('title');
        $data['number'] = $shipoxOrderNumber;


        if ($order->canShip()) {
            $convertor = $this->_orderInterface;
            $shipment = $convertor->toShipment($order);

            foreach ($order->getAllItems() as $orderItem) {

                if (!$orderItem->getQtyToShip()) {
                    continue;
                }
                if ($orderItem->getIsVirtual()) {
                    continue;
                }
                $item = $convertor->itemToShipmentItem($orderItem);
                $qty = $orderItem->getQtyToShip();
                $item->setData('qty', $qty);
                $shipment->addItem($item);
            }

            $track = $this->_track->addData($data);
            $shipment->addTrack($track);

            try {
                $shipment->register();
            } catch (LocalizedException $e) {
            }
            $shipment->setEmailSent(true);
            $shipment->getOrder()->setIsVirtual(false);

            try {
                $transactionSave = $this->_transaction
                    ->addObject($shipment)
                    ->addObject($shipment->getOrder())
                    ->save();
            } catch (\Exception $e) {
            }
        } else {
            $shipment = $order->getShipmentsCollection()->getFirstItem();
            $track = $this->_track->addData($data);
            $shipment->addTrack($track);
            $shipment->save();
        }
    }

    /**
     * @param $order
     * @param $itemsQuantity
     */
    public function setShipmentAndTrackingNumberOnInvoice($order, $itemsQuantity)
    {

        if ($order->canInvoice()) {

            $this->_invoceFactory->create([$order->getIncrementId(), $itemsQuantity, $this->getData('title'), false, false]);
        }
    }

}