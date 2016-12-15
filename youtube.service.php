<?php
require_once 'cache.php';
require_once 'youtube.class.php';

@session_start();

class YoutubeService {
    private $youtubeApi;
    private $youtubeData;
    private $cachePath;

    public function __construct(YoutubeApi $youtubeApi)
    {
        $this->youtubeApi = $youtubeApi;
        $this->youtubeData = false;
        $this->cachePath = 'cache';
    }

    public function getYoutubeData($playlistId, $maxVideos, $pageToken)
    {       
        $this->getDataFromCache($pageToken);

        if( !$this->youtubeData ) {
            $this->youtubeData = json_encode((array)$this->youtubeApi->getPlaylistItemsByPlaylistId($playlistId, $pageToken,  $maxVideos));
        }
        
        $this->saveDataInCache($pageToken);

        return $this->youtubeData;
    }

    private function getDataFromCache($pageToken)
    {
        if(!$pageToken) {
            $this->youtubeData = Cache::get($this->cachePath);
        }  else {
            $this->youtubeData = false;
        }
         
    }

    private function saveDataInCache($pageToken) {
        if(!$pageToken && $this->youtubeData) {
            Cache::put($this->cachePath, $this->youtubeData);
        }
    }
}
