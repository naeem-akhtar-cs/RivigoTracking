<?php

require_once __DIR__ . '/PS_AccessExternalResource.php';
require_once __DIR__ . '/ProcessRivigoDataRequest.php';
require_once __DIR__ . '/RT_AccessDataBase.php';

function trackRivigoParcel($request)
{
    $html = "";
    $statusCode = 205;
    try {
        $data = json_decode(base64_decode($request["data"]));
        $trackingId = $data->trackingId;
        $timeOffset = $data->timeOffset;

        $accessdataBase = new RT_AccessDataBase();
        $accessExternalresource = new PS_AccessExternalResource();
        $appVersion = $accessdataBase->getAppVersion();

        $response = null;
        while ($statusCode == 205) {
            $response = $accessExternalresource->getRivigoTracking($trackingId, $appVersion);
            $statusCode = $response["statusCode"];
            if ($statusCode == 205) {
                $appVersion++;
            }
        }
        $accessdataBase->updateAppVersion($appVersion);

        if ($response["data"]->status != "SUCCESS") {
            throw new Exception("Could not get data");
        }
        $processRequest = new ProcessRivigoDataRequest($timeOffset, $response["data"]);
        $html = $processRequest->populateHtml();

    } catch (\Throwable$th) {
        $html = "<div>Could not get data. Please check the cnote number entered.</div>";
        $statusCode = 500;
    } finally {
        $response = new WP_REST_Response(base64_encode($html));
        $response->set_status($statusCode);
        return $response;
    }
}
