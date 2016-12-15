<?php

class YoutubeApi {
     public $APIs = array(
        'videos.list' => 'https://www.googleapis.com/youtube/v3/videos',
        'search.list' => 'https://www.googleapis.com/youtube/v3/search',
        'channels.list' => 'https://www.googleapis.com/youtube/v3/channels',
        'playlists.list' => 'https://www.googleapis.com/youtube/v3/playlists',
        'playlistItems.list' => 'https://www.googleapis.com/youtube/v3/playlistItems',
        'activities' => 'https://www.googleapis.com/youtube/v3/activities',
    );

    private $youtubeKey;

    /**
     * Constructor
     * $youtube = new Youtube(array('key' => 'KEY HERE'))
     *
     * @param array $params
     * @throws Exception
     */
    public function __construct($key)
    {
        if (is_string($key) && !empty($key)) {
            $this->youtubeKey = $key;
        } else {
            throw new Exception('Google API key is Required, please visit https://console.developers.google.com/');
        }
    }

    /**
     * @param string $username
     * @return \StdClass
     * @throws Exception
     */
    public function getChannelByName($username, $optionalParams = false, $part = ['id', 'snippet', 'contentDetails', 'statistics', 'invideoPromotion'])
    {
        $API_URL = $this->getApi('channels.list');
        $params = array(
            'forUsername' => $username,
            'part' => implode(', ', $part),
        );
        if ($optionalParams) {
            $params = array_merge($params, $optionalParams);
        }
        $apiData = $this->apiGet($API_URL, $params);
        return $this->decodeSingle($apiData);
    }

    /**
     * @param $id
     * @return \StdClass
     * @throws \Exception
     */
    public function getPlaylistById($id, $part = ['id', 'snippet', 'status'])
    {
        $API_URL = $this->getApi('playlists.list');
        $params = array(
            'id' => $id,
            'part' => implode(', ', $part),
        );
        $apiData = $this->apiGet($API_URL, $params);
        return $this->decodeSingle($apiData);
    }

    /**
     * @param string $playlistId
     * @param string $pageToken
     * @param int $maxResults
     * @param array $part
     * @return array
     * @throws \Exception
     */
    public function getPlaylistItemsByPlaylistId($playlistId, $pageToken = '', $maxResults = 12, $part = ['id', 'snippet', 'contentDetails', 'status'])
    {
        $API_URL = $this->getApi('playlistItems.list');
        $params = array(
            'playlistId' => $playlistId,
            'part' => implode(', ', $part),
            'maxResults' => $maxResults,
        );
        // Pass page token if it is given, an empty string won't change the api response
        $params['pageToken'] = $pageToken;
        $apiData = $this->apiGet($API_URL, $params);
        $result = ['results' => $this->decodeList($apiData)];
        $result['info']['totalResults'] =  (isset($this->page_info['totalResults']) ? $this->page_info['totalResults'] : 0);
        $result['info']['nextPageToken'] = (isset($this->page_info['nextPageToken']) ? $this->page_info['nextPageToken'] : false);
        $result['info']['prevPageToken'] = (isset($this->page_info['prevPageToken']) ? $this->page_info['prevPageToken'] : false);
        return $result;
    }

    /**
     * @param string $name
     * @return mixed
     */
    private function getApi($name)
    {
        return $this->APIs[$name];
    }

    /**
     * Using CURL to issue a GET request
     *
     * @param $url
     * @param $params
     * @return mixed
     * @throws Exception
     */
    private function apiGet($url, $params)
    {
        //set the youtube key
        $params['key'] = $this->youtubeKey;
        //boilerplates for CURL
        $tuCurl = curl_init();
        curl_setopt($tuCurl, CURLOPT_URL, $url . (strpos($url, '?') === false ? '?' : '') . http_build_query($params));
        if (strpos($url, 'https') === false) {
            curl_setopt($tuCurl, CURLOPT_PORT, 80);
        } else {
            curl_setopt($tuCurl, CURLOPT_PORT, 443);
        }
        curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);
        $tuData = curl_exec($tuCurl);
        if (curl_errno($tuCurl)) {
            throw new Exception('Curl Error : ' . curl_error($tuCurl));
        }
        return $tuData;
    }

    /**
     * Decode the response from youtube, extract the single resource object.
     * (Don't use this to decode the response containing list of objects)
     *
     * @param  string $apiData the api response from youtube
     * @throws Exception
     * @return \StdClass  an Youtube resource object
     */
    private function decodeSingle(&$apiData)
    {
        $resObj = json_decode($apiData);
        if (isset($resObj->error)) {
            $msg = "Error " . $resObj->error->code . " " . $resObj->error->message;
            if (isset($resObj->error->errors[0])) {
                $msg .= " : " . $resObj->error->errors[0]->reason;
            }
            throw new Exception($msg);
        } else {
            $itemsArray = $resObj->items;
            if (!is_array($itemsArray) || count($itemsArray) == 0) {
                return false;
            } else {
                return $itemsArray[0];
            }
        }
    }

    private function decodeList(&$apiData)
    {
        $resObj = json_decode($apiData);
        if (isset($resObj->error)) {
            $msg = "Error " . $resObj->error->code . " " . $resObj->error->message;
            if (isset($resObj->error->errors[0])) {
                $msg .= " : " . $resObj->error->errors[0]->reason;
            }
            throw new \Exception($msg);
        } else {
            $this->page_info = array(
                'resultsPerPage' => $resObj->pageInfo->resultsPerPage,
                'totalResults' => $resObj->pageInfo->totalResults,
                'kind' => $resObj->kind,
                'etag' => $resObj->etag,
                'prevPageToken' => null,
                'nextPageToken' => null,
            );
            if (isset($resObj->prevPageToken)) {
                $this->page_info['prevPageToken'] = $resObj->prevPageToken;
            }
            if (isset($resObj->nextPageToken)) {
                $this->page_info['nextPageToken'] = $resObj->nextPageToken;
            }
            $itemsArray = $resObj->items;
            if (!is_array($itemsArray) || count($itemsArray) == 0) {
                return false;
            } else {
                return $itemsArray;
            }
        }
    }

}
?>
