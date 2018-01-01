<?php
/**
* This library enables you to fetch content from DSBmobile.
*
* @author   Jonas Bögle
* @version  2.0
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
    const Success = 1;
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
        if ($response->Resultcode == Dsbmobile::Success) {
            error_log('DSB api error: ' . $response->ResultStatusInfo);
            return false;
        } else {
            return $response;
        }
    }

    /**
     * Gets alls topics
     * 
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
     * @return boolean Returns true if no error occurred
     */
    function isValid() {
        return $this->topic != false;
    }

    /**
     * Gets a child with a specified index
     * 
     * @param int $index Index of the child
     * @return DsbTopicChild Child
     */
    public function getChild($index = 0) {
        return new DsbTopicChild($this->isValid() ? $this->topic->Root->Childs[$index] : false);
    }
}

/**
* Child of a Topic
*/
class DsbTopicChild {
    private $child;

    /**
     * @param object $data Child data
     */
    function __construct($data) {
        $this->child = $data;
    }

    /**
     * Checks whether object contains data
     * 
     * @return boolean Returns true if no error occurred
     */
    function isValid() {
        return $this->child != false;
    }

    /**
     * Gets the Url to the content of the topic child
     * 
     * @return string|boolean Url of the content or false if an error occurred
     */
    public function getUrl() {
        return $this->isValid() ? $this->child->Childs[0]->Detail : false;
    }

    /**
     * Gets the Html code of the content
     * WARNING: This can lead to XSS vulnerabilities so use this carefully!
     * 
     * @return string|boolean Html code of the content or false if an error occurred
     */
    public function getHtml() {
        $url = $this->getUrl();

        if (!($url && filter_var($url, FILTER_VALIDATE_URL))) {
            return false;
        }

        $headers = get_headers($url, 1);
        $contentType = isset($headers['Content-Type']) ? $headers['Content-Type'] : 'text/html';
        
        if ($contentType == 'text/html') {
            $html = file_get_contents($url);

            // turn relative urls into absolute ones:

            $absoluteUrlPath = getAbsBaseUrl($url);

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
            return false;
        }
    }
}

function getAbsBaseUrl($url) {
    $parsed_url = parse_url($url);
    $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
    $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
    $pass     = ($user || $pass) ? "$pass@" : '';
    $path     = '';
    if (isset($parsed_url['path'])) {
        preg_match('/^.+\//', $parsed_url['path'], $match);
        $path = $match[0];
    }
    return "$scheme$user$pass$host$port$path";
}