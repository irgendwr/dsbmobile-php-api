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
     * @return array Returns array of topics
     */
    private function getTopics() {
        $data = $this->getData();

        // TODO: turn them into DsbTopic classes
        return $data ? $data->ResultMenuItems[0]->Childs : [];
    }

    /**
     * Gets a topic with a specified index
     * 
     * @since 2.0.0
     * @param int $index Index of the topic
     * @return DsbTopic Topic
     */
    public function getTopic($index = 0) {
        foreach ($this->getTopics() as $topic) {
            if ($topic->Index == $index) {
                return new DsbTopic($topic);
            }
        }
        
        // topic was not found
        return new DsbTopic(false);
    }

    /**
     * Gets a topic with a specified method
     * 
     * @since 2.0.0
     * @param string $method Method of the topic
     * @return DsbTopic Topic
     */
    public function getTopicByMethod($method) {
        foreach ($this->getTopics() as $topic) {
            if ($topic->MethodName == $method) {
                return new DsbTopic($topic);
            }
        }
        
        // topic was not found
        return new DsbTopic(false);
    }

    /**
     * Gets the topic containing timetables
     * 
     * @since 2.1.0
     * @return DsbTopic Topic
     */
    public function getTimetables() {
        return $this->getTopicByMethod('timetable');
    }

    /**
     * Gets the topic containing tiles
     * 
     * @since 2.1.0
     * @return DsbTopic Topic
     */
    public function getTiles() {
        return $this->getTopicByMethod('tiles');
    }

    /**
     * Gets the topic containing news
     * 
     * @since 2.1.0
     * @return DsbTopic Topic
     */
    public function getNews() {
        return $this->getTopicByMethod('news');
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
     * Gets the index
     * 
     * @since 2.1.0
     * @return int Index
     */
    public function getIndex() {
        return $this->isValid() ? $this->Index : 0;
    }

    /**
     * Gets the url of the icon
     * 
     * @since 2.1.0
     * @return string Url to icon
     */
    public function getIcon() {
        return $this->isValid() ? $this->IconLink : '';
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
        return $this->getItem($index);
    }
}

// TODO: add getters for tile items

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
     * Gets either the Url if item is a timetable or the text if item is a news entry
     * 
     * @since 2.1.0
     * @return string Url or Text
     */
    public function getDetail() {
        return $this->isValid() ? $this->item->Childs[0]->Detail : '';
    }

    /**
     * Gets Preview
     * 
     * @since 2.1.0
     * @return string Url or Text
     */
    public function getPreview() {
        return $this->isValid() ? $this->item->Childs[0]->Preview : '';
    }

    /**
     * Gets tags
     * 
     * @since 2.1.0
     * @return string Tags
     */
    public function getTags() {
        return $this->isValid() ? $this->item->Tags : '';
    }

    /**
     * Gets content type
     * 
     * @since 2.1.0
     * @return int Content Type
     */
    public function getConType() {
        return $this->isValid() ? $this->item->ConType : 0;
    }

    /**
     * Gets prio
     * 
     * @since 2.1.0
     * @return int Prio
     */
    public function getPrio() {
        return $this->isValid() ? $this->item->Prio : 0;
    }

    /**
     * Gets index
     * 
     * @since 2.1.0
     * @return int Index
     */
    public function getIndex() {
        return $this->isValid() ? $this->item->Index : 0;
    }

    /**
     * Gets an item with a specified index
     * 
     * @since 2.1.0
     * @param int $index Index of the item
     * @return DsbItem item
     */
    public function getItem($index = 0) {
        return new DsbItem($this->isValid() ? $this->Childs[$index] : false);
    }

    /**
     * Gets the Url of a timetable
     * 
     * @since 2.0.0
     * @see getDetail()
     * @return string Url
     */
    public function getUrl() {
        $url = $this->getDetail();

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
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