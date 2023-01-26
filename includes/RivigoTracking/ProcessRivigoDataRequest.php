<?php

class ProcessRivigoDataRequest
{
    public $offset;
    public $data;
    public $fromLocation;
    public $toLocation;

    public function __construct($offset, $data)
    {
        $this->offset = $offset;
        $this->data = $data;
        $this->fromLocation = $data->payload->fromLocation->name;
        $this->toLocation = $data->payload->toLocation->name;
    }

    public function getAdditionalDaysTable()
    {
        if ($this->data->payload->additionalTat == null || $this->data->payload->additionalTat == 0) {
            return "";
        }
        $addtionalDaysData = "";
        foreach ($this->data->payload->cpdChangeDetailsDtoList as $additionalDay) {
            $addtionalDaysData .=
            "<li>
            <span>" . $additionalDay->message . " :</span>
            <span>+ " . $additionalDay->days . "</span>
            </li>";
        }

        $additionalDaysinfo = "
        <li>
                                <div>
                                    <span> Additional TAT :</span>
                                    <span>" . $this->data->payload->additionalTat . "</span>
                                </div>
                                <div>
                                    <ul>
                                    " .
            $addtionalDaysData
            . "
                                    </ul>
                                </div>
                            </li>
        ";

        return $additionalDaysinfo;
    }

    public function getTransitDataTable($transitHistory)
    {
        $transitHistoryData = "";
        $a = false;
        $transitHistoryObject = [];
        $addInArray = true;
        $deliveryAttemptCount = 0;
        $attemptCountString = [
            1 => "1st attempt",
            2 => "2nd attempt",
            3 => "3rd attempt",
            4 => "4th attempt",
            5 => "5th attempt",
            6 => "6th attempt",
            7 => "7th attempt",
        ];
        $lastOutKey = null;
        $count = 0;
        foreach ($transitHistory as $e) {
            if ("IN" != $e->activityType && "OUT" != $e->activityType || !("LOADED" == $e->status && $a || "UNLOADED" == $e->status)) {
                switch ($e->status) {
                    case "CREATED":
                    case "INTRANSIT_TO_OU":
                        $e->activity = "Pickup" === $e->activity ? "Pickup" : "";
                        break;
                    case "RECEIVED_AT_OU":
                        if ("Destination PC - IN" === $e->activity) {
                            $e->activity = "Arrived";
                        } else {
                            if ("Origin PC - IN" != $e->activity) {
                                $addInArray = false;
                            }
                            $e->activity = "Received";
                        }
                        break;
                    case "UNDELIVERED":
                        if ($lastOutKey != null && $e->alertReason != "" && $e->alertReason != null) {
                            $transitHistoryObject[$lastOutKey]->alertRemarks = $e->alertReason;
                        }
                        $addInArray = false;
                        break;
                    case "LOADED":
                        if (!$a) {
                            $e->activity = "Dispatched";
                            $a = true;
                        }
                        break;
                    case "DELIVERED_POD_PENDING":
                    case "DELIVERED":
                        $e->activity = "Delivered";
                        $this->toLocation = $e->city != null && $e->city != '' ? $e->city : $this->toLocation;
                        break;
                    case "OUT_FOR_DELIVERY":
                        $deliveryAttemptCount++;
                        $e->activity .= " - " . $attemptCountString[$deliveryAttemptCount];
                        $lastOutKey = $e->activity;
                        break;
                }
                if ($addInArray) {
                    $transitHistoryObject[$e->activity] = $e;
                    if ($count == 0) {
                        $this->fromLocation = $e->city != null && $e->city != '' ? $e->city : $this->fromLocation;
                        $count++;
                    }

                } else {
                    $addInArray = true;
                }
            }
        }

        foreach ($transitHistoryObject as $e) {
            $alertRemarks = $e->alertRemarks != null ? $e->alertRemarks : "--";
            $parcelTime = $e->dateAndTime != null ? (gmdate("d M Y, h:i A", ($e->dateAndTime * 0.001) + ($this->offset * 60))) : "--";
            $transitHistoryData .=
            "<tr>
            <td>" .
            $e->activity
            . "
                </td>
                <td>
                    " .
            $e->city
                . "
                </td>
                <td>
                    " .
                $parcelTime
                . "
                </td>
                <td>
                    " .
                $alertRemarks
                . "
                </td>
                </tr>";
        }
        if (count($transitHistoryObject) < 1) {
            return;
        }
        $tableStructure = "
        <div>
            <h3>
                Transit details
            </h3>
            <div id='transitDetails'>
                <table>
                    <tbody>
                        <tr>
                            <th>
                                Status
                            </th>
                            <th>
                                Location
                            </th>
                            <th>
                                Date & time
                            </th>
                            <th>
                                Remarks
                            </th>
                        </tr>
                        " .
            $transitHistoryData
            . "
                    </tbody>
                </table>
            </div>
        </div>
            ";

        return $tableStructure;
    }

    public function getUndeliveredDataTable($undeliveredData)
    {
        if (count($undeliveredData) < 1) {
            return;
        }
        $undeliveredReasons = "";
        foreach ($undeliveredData as $key => $data) {
            $undeliveredTime = $data->createdAt != null ? (gmdate("d M Y, h:i A", ($data->createdAt * 0.001) + ($this->offset * 60))) : "--";
            $reason = $data->reason != null || $data->reason != "" ? $data->reason : "--";
            $reason .= $data->subReason != null || $data->subReason != "" ? "(" . $data->subReason . ")" : "";
            $undeliveredReasons .=
                "<tr>
            <td id='deliveryAttemptNum'>" .
                ($key + 1)
                . "
                </td>
                <td>
                    " .
                $undeliveredTime
                . "
                </td>
                <td>
                    " .
                $reason
                . "
                </td>
                </tr>";

        }

        $tableStructure = "
        <div>
            <h3>
                Delivery Attempts
            </h3>
            <div id='deliveryAttempts'>
                <table>
                    <tbody>
                        <tr>
                            <th>
                                Attempt
                            </th>
                            <th>
                                Date
                            </th>
                            <th>
                                Failure Reason
                            </th>
                        </tr>
                        " .
            $undeliveredReasons
            . "
                    </tbody>
                </table>
            </div>
        </div>
            ";
        return $tableStructure;

    }

    public function populateHtml()
    {
        $bookingTime = $this->data->payload->consignmentDto->bookingDateTime != null ? (gmdate("d M Y, h:i A", ($this->data->payload->consignmentDto->bookingDateTime * 0.001) + ($this->offset * 60))) : "--";
        $promisedTime = $this->data->payload->consignmentDto->promisedDeliveryTime != null ? (gmdate("d M Y", ($this->data->payload->consignmentDto->promisedDeliveryTime * 0.001))) : "--";
        $deliveryDateTime = $this->data->payload->consignmentDto->deliveryDateTime != null ? (gmdate("d M Y, h:i A", ($this->data->payload->consignmentDto->deliveryDateTime * 0.001) + ($this->offset * 60))) : "--";
        $pickCompleteTime = $this->data->payload->pickupCompletionDateTime != null ? (gmdate("d M Y, h:i A", ($this->data->payload->pickupCompletionDateTime * 0.001) + ($this->offset * 60))) : "--";

        $transitDataTable = $this->getTransitDataTable($this->data->payload->transitHistory);
        $getUndeliveredDataTable = $this->getUndeliveredDataTable($this->data->payload->undeliveredConsignments);
        $getAdditionalDaysTable = $this->getAdditionalDaysTable($this->data);
        $trackingView = "
        <div>
    <style>
        #rivigotracking table {
            width: 100%;
            border-collapse: collapse;
        }

        #transitDetails td,
        th {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }

        #deliveryAttempts td,
        th {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }

        #trackingCityName
        {
            padding:10%;
        }

        #transitDetails tr:nth-child(even) {
            background-color: #ebe8e8;
        }

        #rivigoStatus,
        #shipmentDetails,
        #transitDetails,
        #deliveryAttempts {
            border-radius: 15px 50px;
            background-color: aliceblue;
            padding: 5%;
        }

        #shipmentDetails td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }

        #trackingNumber {
            width: 100%;
        }
    </style>

    <div id='rivigotracking'>
        <div id='rivigoStatus'>
            <div>
                <h3>" .
        $this->data->payload->displayStatusDto->primaryText
        . "
                </h3>
            </div>
            <hr>
            <div id='trackingNumber'>
                <h4>Tracking ID: " . $this->data->payload->cnote . "</h4>
            </div>
            <hr>
            <div id='cityDetails'>
                <table>
                    <tbody>
                        <tr>
                            <td id='trackingCityName'>
                                " .
        $this->fromLocation
        . "
                            </td>
                            <td>
                                <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor'
                                    class='bi bi-arrow-right' viewBox='0 0 16 16'>
                                    <path fill-rule='evenodd'
                                        d='M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z' />
                                </svg>
                            </td>
                            <td id='trackingCityName'>
                                " .
        $this->toLocation
        . "
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <hr>
            <div id='shipmentDetails'>
                <table>
                    <tbody>
                        <tr>
                            <td>Booking date & time</td>
                            <td>" .
        $bookingTime
        . "</td>
                        </tr>

                        <tr>
                            <td>
                                Pickup Completion Date
                            </td>
                            <td>
                                " .
        $pickCompleteTime
        . "
                            </td>

                        </tr>

                        <tr>
                            <td>
                                Packages
                            </td>
                            <td>
                                " .
        $this->data->payload->consignmentDto->totalBoxes
        . "
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <hr>
        </div>
        <div>
            <h3>
                Shipment info
            </h3>
            <div id='shipmentDetails'>
                <div>
                    <table>
                        <tbody>
                            <tr>
                                <td>
                                    Client Promised Delivery:
                                </td>
                                <td>
                                    " .
        $promisedTime
        . "
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Delivered on:
                                </td>
                                <td>
                                " .
        $deliveryDateTime
        . "
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div>
                    <h5>
                        DELIVERY TAT : " . ($this->data->payload->tat + $this->data->payload->additionalTat) . "
                    </h5>
                    <div>
                        <ol>
                            <li>
                                <span> Route TAT :</span>
                                <span>" . $this->data->payload->tat . "</span>
                            </li>
                            " .
            $getAdditionalDaysTable
            . "
                        </ol>
                    </div>
                </div>
            </div>
        </div>

         " .
            $transitDataTable
            . "
            " .
            $getUndeliveredDataTable
            . "
    </div>
</div>";

        return $trackingView;
    }
}
