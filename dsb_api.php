<?php
/**
* This library enables you to fetch content from DSBmobile.
*
* @author   Jonas Bögle
* @version  2.1.0
* @link     https://github.com/irgendwr/dsbmobile-php-api
* @license  https://github.com/irgendwr/dsbmobile-php-api/blob/master/LICENSE   MIT License
*/

/**
* Constants used by the library
*/
class Dsbmobile {
    const App = [
        'BundleId' => 'de.heinekingmedia.inhouse.dsbmobile.web',
        'Device'   => 'WebApp',
        'ID'       => '',
        'Version'  => '2.3'
    ];
    const Language  = 'de';
    const Requesttypes = [
        'Unknown'  => 0,
        'Data'     => 1,
        'Mail'     => 2,
        'Feedback' => 3,
        'Subjects' => 4
    ];
    const Success = 0;
    const UserAgent = '';
    const Webservice = 'http://www.dsbmobile.de/JsonHandlerWeb.ashx/GetData';
}

/**
* Account
*/
class DsbAccount {
    private $username;
    private $password;

    /**
     * @param string $username Username
     * @param string $password Password
     */
    function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Gets the data linked with the account
     * 
     * @since 2.0.0
     * @return object|boolean Returns data or false if an error occurred
     */
    public function getData() {
        // print date like JavaScript
        $date = date('D M d Y H:i:s O');

        $args = [
            'UserId'     => $this->username,
            'UserPw'     => $this->password,
            'Abos'       => [],
            'AppVersion' => Dsbmobile::App['Version'],
            'Language'   => Dsbmobile::Language,
            'OsVersion'  => Dsbmobile::UserAgent,
            'AppId'      => Dsbmobile::App['ID'],
            'Device'     => Dsbmobile::App['Device'],
            'PushId'     => '',
            'BundleId'   => Dsbmobile::App['BundleId'],
            'Date'       => $date,
            'LastUpdate' => $date
        ];

        // json encode
        $args = json_encode($args);

        // gzip encode
        $args = gzencode($args);

        // Base64 encode
        $args = base64_encode($args);

        // json encode
        $data = json_encode([
            'req' => [
                'Data' => $args,
                'DataType' => Dsbmobile::Requesttypes['Data']
            ]
        ]);

        $request = curl_init(Dsbmobile::Webservice);

        curl_setopt_array($request, [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json;charset=utf-8',
                'Bundle_ID: ' . Dsbmobile::App['BundleId']
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_REFERER => 'https://www.dsbmobile.de/default.aspx',
            CURLOPT_RETURNTRANSFER => true
        ]);

        // send request
        $response = curl_exec($request);

        // check for errors
        if($response === false) {
            error_log('DSB api curl error: ' . curl_error($request));
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

        // check if request was successfull
        if ($response->Resultcode != Dsbmobile::Success) {
            error_log('DSB api error: ' . $response->ResultStatusInfo);
            return false;
        } else {
            return $response;
        }
    }

    /**
     * Gets alls topics
     * 
     * @since 2.0.0
     * @return array|bool Returns array of topics or false if an error occurred
     */
    private function getTopics() {
        $data = $this->getData();

        // TODO: turn them into DsbTopic classes
        return $data ? $data->ResultMenuItems[0]->Childs : false;
    }

    /**
     * Gets a topic with a specified index
     * 
     * @since 2.0.0
     * @param int $index Index of the topic
     * @return DsbTopic Topic
     */
    public function getTopic($index = 0) {
        $topics = $this->getTopics();

        if ($topics) {
            foreach ($topics as $topic) {
                if ($topic->Index == $index) {
                    return new DsbTopic($topic);
                }
            }
        }
        
        // error
        return new DsbTopic(false);
    }
}

/**
* Topic
*/
class DsbTopic {
    private $topic;

    /**
     * @param object $data Topic data
     */
    function __construct($data) {
        $this->topic = $data;
    }

    /**
     * Checks whether object contains data
     * 
     * @since 2.0.0
     * @return boolean Returns true if no error occurred
     */
    function isValid() {
        return $this->topic != false;
    }

    /**
     * Gets the title
     * 
     * @since 2.0.0
     * @return string Title
     */
    public function getTitle() {
        return $this->isValid() ? $this->topic->Title : '';
    }

    /**
     * Gets an item with a specified index
     * 
     * @since 2.1.0
     * @param int $index Index of the item
     * @return DsbItem item
     */
    public function getItem($index = 0) {
        return new DsbItem($this->isValid() ? $this->topic->Root->Childs[$index] : false);
    }

    /**
     * Gets an item with a specified index
     * 
     * @since 2.0.0
     * @deprecated 2.1.0 Use getItem()
     * @see getItem()
     * 
     * @param int $index Index of the item
     * @return DsbItem item
     */
    public function getChild($index = 0) {
        return getItem($index);
    }
}

/**
* Item of a Topic
*/
class DsbItem {
    private $item;

    /**
     * @param object $data Item data
     */
    function __construct($data) {
        $this->item = $data;
    }

    /**
     * Checks whether object contains data
     * 
     * @since 2.0.0
     * @return boolean Returns true if no error occurred
     */
    function isValid() {
        return $this->item != false;
    }

    /**
     * Gets the Id
     * 
     * @since 2.0.0
     * @return string Id
     */
    public function getId() {
        return $this->isValid() ? $this->item->Id : '';
    }

    /**
     * Gets the Date
     * 
     * @since 2.0.0
     * @return string Date
     */
    public function getDate() {
        return $this->isValid() ? $this->item->Date : '';
    }

    /**
     * Gets the title
     * 
     * @since 2.0.0
     * @return string Title
     */
    public function getTitle() {
        return $this->isValid() ? $this->item->Title : '';
    }

    /**
     * Gets the Url to the content
     * 
     * @since 2.0.0
     * @return string Url
     */
    public function getUrl() {
        return $this->isValid() ? $this->item->Childs[0]->Detail : '';
    }

    /**
     * Gets the Html code of the content
     * WARNING: This can lead to XSS vulnerabilities so use this carefully!
     * 
     * @since 2.0.0
     * @return string Html code
     */
    public function getHtml() {
        $url = $this->getUrl();

        if (!($url && filter_var($url, FILTER_VALIDATE_URL))) {
            return '';
        }

        $headers = get_headers($url, 1);
        $contentType = isset($headers['Content-Type']) ? $headers['Content-Type'] : 'text/html';
        
        if ($contentType == 'text/html') {
            $html = file_get_contents($url);

            // get the base url
            preg_match('/^.+\//', $url, $match);
            $absoluteUrlPath = $match[0];

            // turn relative urls into absolute ones:
            preg_match_all('/(?:href|src)="(?<url>.+)"/', $html, $refs);
            foreach ($refs['url'] as $ref) {
                // skip if url is already valid
                if (!filter_var($ref, FILTER_VALIDATE_URL))
                    $html = str_replace('"'.$ref.'"', $absoluteUrlPath . $ref, $html);
            }

            return $html;
        } elseif (preg_match('/^image\/(jpeg|gif|png|svg)[;\w\=\+]*$/', $contentType)) {
            return '<p style="text-align:center;" class="dsb"><img src="' . $url . '" class="dsb"></p>';
        } else {
            // unknown content type
            return '';
        }
    }
}