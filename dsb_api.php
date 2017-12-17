<?php

class DSB {
    const USERNAME = "";
    const PASSWORD = "";
    
    const WEBSERVICE_METHOD = "https://mobile.dsbcontrol.de/JsonHandlerWeb.ashx/GetData";
    const APP_VERSION = "2.1";
    const LANGUAGE = "de";
    const USER_AGENT  = "";
    //const USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36";
    const APP_ID = "";
    const DEVICE = "WebApp";
    const BUNDLE_ID = "de.heinekingmedia.inhouse.dsbmobile.web";
    const HMDataType = 1;
    
    public static function getData($username = self::USERNAME, $password = self::PASSWORD) {
        // print date like JavaScript
        $date = date('D M d Y H:i:s O');

        $arguments = Array(
            "UserId"     => $username,
            "UserPw"     => $password,
            "Abos"       => Array(),
            "AppVersion" => self::APP_VERSION,
            "Language"   => self::LANGUAGE,
            "OsVersion"  => self::USER_AGENT,
            "AppId"      => self::APP_ID,
            "Device"     => self::DEVICE,
            "PushId"     => "",
            "BundleId"   => self::BUNDLE_ID,
            "Date"       => $date,
            "LastUpdate" => $date
        );

        // json encode
        $arguments = json_encode($arguments);

        // gzip encode
        $arguments = gzencode($arguments);

        // Base64 encode
        $arguments = base64_encode($arguments);

        // json encode
        $data = json_encode(
            Array(
                "req" => Array(
                    "Data" => $arguments,
                    "DataType" => self::HMDataType
                )
            )
        );

        $request = curl_init(self::WEBSERVICE_METHOD);

        curl_setopt_array($request, array(
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json;charset=utf-8",
                "Bundle_ID: " . self::BUNDLE_ID
            ),
            CURLOPT_REFERER => "https://www.dsbmobile.de/default.aspx",
            CURLOPT_POSTFIELDS => $data
        ));

        // send request
        $response = curl_exec($request);

        // check for errors
        if($response === false){
            error_log("Curl / DSB Api Error: " . curl_error($request));
            return false;
        }

        curl_close($request);

        // parse json
        $response = json_decode($response)->d;

        // decode Base64
        $response = base64_decode($response);

        // decode gzip
        $response = gzdecode($response);

        // parse json
        $response = json_decode($response);

        if ($response->Resultcode == 1) { // error
            error_log("DSB Api Error: " . $response->ResultStatusInfo);
            return false;
        } else { // success
            return $response;
        }
    }

    public static function getTopic($topicIndex = 0, $username = self::USERNAME, $password = self::PASSWORD) {
        // get DSB data
        $data = self::getData($username, $password);

        if ($data) {
            foreach ($data->ResultMenuItems[0]->Childs as $topic) {
                if ($topic->Index == $topicIndex) {
                    return $topic;
                }
            }

            // error
            return false;
        } else {
            // error
            return false;
        }
    }

    public static function getTopicChildUrl($topicIndex = 0, $childIndex = 0, $username = self::USERNAME, $password = self::PASSWORD) {
        // get DSB data
        $data = self::getData($username, $password);

        if ($data) {
            foreach ($data->ResultMenuItems[0]->Childs as $topic) {
                if ($topic->Index == $topicIndex) {
                    return $topic->Root->Childs[$childIndex]->Childs[0]->Detail;
                }
            }

            // error
            return false;
        } else {
            // error
            return false;
        }
    }
}
