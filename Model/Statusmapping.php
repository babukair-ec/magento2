<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 22.07.2018
 * Time: 14:21
 */
namespace Delivery\Shipox\Model;
use Magento\Framework\Model\AbstractModel;

class Statusmapping extends AbstractModel {
    public function statusList()
    {
        $array = array();

        $array['unassigned'] = array('label' => 'New', 'cancellable' => true);
        $array['assigned_to_courier'] = array('label' => 'Assigned To Courier', 'cancellable' => true);
        $array['accepted'] = array('label' => 'Assigned To Driver', 'cancellable' => true);
        $array['on_his_way'] = array('label' => 'Driver On Pickup', 'cancellable' => true);
        $array['arrived'] = array('label' => 'Parcel in Sorting Facility', 'cancellable' => true);
        $array['first_pickup_attempt'] = array('label' => 'First Pick Up Attempt', 'cancellable' => true);

        $array['picked_up'] = array('label' => 'Parcel Picked Up', 'cancellable' => false);
        $array['picked_auto'] = array('label' => 'Parcel Picked Up', 'cancellable' => false);

        $array['bad_sender_address'] = array('label' => 'Bad Sender Address', 'cancellable' => true);
        $array['sender_not_available'] = array('label' => 'Sender Not Available', 'cancellable' => true);
        $array['parcel_not_ready'] = array('label' => 'Parcel Not Ready', 'cancellable' => true);
        $array['sender_mobile_switched_off'] = array('label' => 'Sender Mobile Switched Off', 'cancellable' => true);
        $array['sender_mobile_wrong'] = array('label' => 'Sender Mobile Wrong', 'cancellable' => true);
        $array['sender_mobile_no_response'] = array('label' => 'Sender Mobile No Response', 'cancellable' => true);
        $array['out_of_pickup_area'] = array('label' => 'Out of Pickup Area', 'cancellable' => true);
        $array['future_pickup_requested'] = array('label' => 'Future Pickup Requested', 'cancellable' => true);
        $array['sender_address_change_requested'] = array('label' => 'Sender Address Change Requested', 'cancellable' => true);
        $array['unable_to_access_sender_premises'] = array('label' => 'Unable to Access Sender Premises', 'cancellable' => true);
        $array['prohibited_items'] = array('label' => 'Prohibited Items', 'cancellable' => true);
        $array['incorrect_packing'] = array('label' => 'Incorrect Packing', 'cancellable' => true);
        $array['no_awb_printed'] = array('label' => 'No AWB Printed', 'cancellable' => true);
        $array['pickup_delay_due_to_late_booking'] = array('label' => 'Pickup Delay Due to Late Booking', 'cancellable' => true);
        $array['bad_weather_during_pickup'] = array('label' => 'Bad Weather During Pickup', 'cancellable' => true);
        $array['sender_name_missing'] = array('label' => 'Sender Name Missing', 'cancellable' => true);
        $array['pickup_rejected'] = array('label' => 'Pickup Rejected', 'cancellable' => true);
        $array['no_capacity'] = array('label' => 'No Capacity', 'cancellable' => true);
        $array['pickup_on_hold'] = array('label' => 'Pickup On Hold', 'cancellable' => true);
        $array['pickup_confirmed'] = array('label' => 'Pickup Confirmed', 'cancellable' => true);
        $array['pick_up_failed'] = array('label' => 'Pickup Failed', 'cancellable' => true);

        $array['in_sorting_facility'] = array('label' => 'Parcel in Sorting Facility', 'cancellable' => false);
        $array['lost_or_damaged'] = array('label' => 'Lost Or Damaged', 'cancellable' => false);
        $array['in_transit'] = array('label' => 'In Transit', 'cancellable' => false);
        $array['out_for_delivery'] = array('label' => 'Out For Delivery', 'cancellable' => false);
        $array['arrived_at_delivery'] = array('label' => 'Arrived At Delivery', 'cancellable' => false);
        $array['arrived_at_delivery'] = array('label' => 'Arrived At Delivery', 'cancellable' => false);
        $array['on_hold'] = array('label' => 'On Hold', 'cancellable' => false);
        $array['first_delivery_attempt'] = array('label' => 'First Delivery Attempt', 'cancellable' => false);
        $array['completed'] = array('label' => 'Parcel Delivered', 'cancellable' => false);
        $array['bad_recipient_address'] = array('label' => 'Bad Recipient Address', 'cancellable' => false);
        $array['recipient_not_available'] = array('label' => 'Recipient Not Available', 'cancellable' => false);
        $array['recipient_mobile_switched_off'] = array('label' => 'Recipient Mobile Switched Off', 'cancellable' => false);
        $array['recipient_mobile_wrong'] = array('label' => 'Recipient Mobile Wrong', 'cancellable' => false);
        $array['recipient_mobile_no_response'] = array('label' => 'Recipient Mobile No Response', 'cancellable' => false);
        $array['recipient_address_change_requested'] = array('label' => 'Recipient Address Change Requested', 'cancellable' => false);
        $array['cod_not_ready'] = array('label' => 'COD Not Ready', 'cancellable' => false);
        $array['future_delivery_requested'] = array('label' => 'Future Delivery Requested', 'cancellable' => false);
        $array['out_of_delivery_area'] = array('label' => 'Out of Delivery Area', 'cancellable' => false);
        $array['unable_to_access_recipient_premises'] = array('label' => 'Unable to Access Recipient Premises', 'cancellable' => false);
        $array['no_capacity_for_delivery'] = array('label' => 'No Capacity for Delivery', 'cancellable' => false);
        $array['id_or_document_required_missing'] = array('label' => 'ID or Document Required Missing', 'cancellable' => false);
        $array['bad_weather_during_delivery'] = array('label' => 'Bad Weather During Delivery', 'cancellable' => false);
        $array['recipient_name_missing'] = array('label' => 'Recipient Name Missing', 'cancellable' => false);
        $array['collection_arranged_or_requested'] = array('label' => 'Collection Arranged or Requested', 'cancellable' => false);
        $array['wrong_shipment'] = array('label' => 'Wrong Shipment', 'cancellable' => false);
        $array['incomplete_parcel'] = array('label' => 'Incomplete Parcel', 'cancellable' => false);
        $array['delivery_delay_due_to_late_booking'] = array('label' => 'Delivery Delay Due to Late Booking', 'cancellable' => false);
        $array['no_time_for_delivery'] = array('label' => 'No Time for Delivery', 'cancellable' => false);
        $array['delivery_rejected'] = array('label' => 'Delivery Rejected', 'cancellable' => false);
        $array['delivery_scheduled'] = array('label' => 'Delivery Scheduled', 'cancellable' => false);
        $array['delivery_failed'] = array('label' => 'Delivery Failed', 'cancellable' => false);
        $array['to_be_returned_to_wing'] = array('label' => 'To Be Returned To Shipox', 'cancellable' => false);
        $array['returning_to_wing'] = array('label' => 'Returning To Shipox', 'cancellable' => false);
        $array['returned_to_wing'] = array('label' => 'Returned To Shipox', 'cancellable' => false);
        $array['to_be_returned'] = array('label' => 'To Be Returned', 'cancellable' => false);
        $array['out_of_return'] = array('label' => 'Out of Return', 'cancellable' => false);
        $array['destroyed_on_customer_request'] = array('label' => 'Destroyed On Customer Request', 'cancellable' => false);
        $array['returned_to_origin'] = array('label' => 'Returned to Origin', 'cancellable' => false);

        $array['driver_cancelled'] = array('label' => 'Assigned to Driver', 'cancellable' => true);

        $array['cancelled'] = array('label' => 'Cancelled', 'cancellable' => false);
        $array['cancelled_due_to_out_of_delivery_area'] = array('label' => 'Cancelled Due To out of Delivery Area', 'cancellable' => false);

        $array['rejected'] = array('label' => 'Rejected', 'cancellable' => true);
        $array['cancelled_by_driver'] = array('label' => 'Assigned to Driver', 'cancellable' => true);

        return $array;
    }


    /**
     * @param $status
     * @return mixed|null
     */
    public function getOrderStatus($status) {
        $statusList = $this->statusList();
        if(array_key_exists($status, $statusList)) {
            return $statusList[$status];
        }
        return null;
    }


    /**
     * @param $status
     * @return null
     */
    public function isOrderStatusCancellable($status) {
        $statusItem = $this->getOrderStatus($status);
        if($statusItem)
            return $statusItem['cancellable'];

        return null;
    }
}