<?php

class PS_AccessExternalResource
{
    public function getRivigoTracking($trackingId, $appVersion)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://zoom-api.rivigo.com/tracking/v2/cnote/{$trackingId}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'authority: zoom-api.rivigo.com',
                'accept: application/json, text/plain, */*',
                'accept-language: en-US,en;q=0.9,ur;q=0.8',
                'appname: zoom_ops',
                "appversion: {$appVersion}",
                'origin: https://zoom-ops.rivigo.com',
                'referer: https://zoom-ops.rivigo.com/',
                'sec-ch-ua: "Not?A_Brand";v="8", "Chromium";v="108", "Google Chrome";v="108"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Linux"',
                'sec-fetch-dest: empty',
                'sec-fetch-mode: cors',
                'sec-fetch-site: same-site',
                'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
            ),
        ));
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $response = [
            "data" => json_decode($response),
            "statusCode" => $statusCode,
        ];
        return $response;
    }
}
