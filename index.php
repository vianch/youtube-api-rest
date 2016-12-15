<?php
/**
URL                                            HTTP   Method  Operation
/api/playlist                                  GET    Returns error notFound
/api/playlist/:playlistId                      GET    Returns PlayList video list max 50 videos
/api/playlist/:playlistId/:max                 GET    Specify the max number of videos returned
/api/playlist/:playlistId/:max/:page_token     GET    the max number of videos per page
**/

require_once 'Slim/Slim.php';
require_once 'youtube.class.php';
require_once 'youtube.service.php';

header('content-type: application/json; charset=utf-8');
header("access-control-allow-origin: *");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Activamos las sesiones para el funcionamiento de flash['']
@session_start();

const YOUTUBE_ID = "AIzaSyBQzL6IElm-384tQtGh_c8V2piE-DUWQcc";

// El framework Slim tiene definido un namespace llamado Slim
// Por eso aparece \Slim\ antes del nombre de la clase.
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
$youtubeApi = new YoutubeApi(YOUTUBE_ID);
$youtubeService = new YoutubeService($youtubeApi);

$app->add(new \Slim\Middleware\SessionCookie(array(
    'expires' => '20 minutes',
    'path' => '/',
    'domain' => null,
    'secure' => false,
    'httponly' => false,
    'name' => 'gallery',
    'secret' => md5('gallery'),
    'cipher' => MCRYPT_RIJNDAEL_256,
    'cipher_mode' => MCRYPT_MODE_CBC
)));

$app->contentType('application/json; charset=utf-8');

$app->notFound(function () {
   echo "{ \"ERROR\": \"NO A VALID ENDPOINT\" }";
});

$app->get('/playlist/:playlistId', function($playlistId) use ($youtubeService) {
    try {
         print($youtubeService->getYoutubeData($playlistId,50,''));
    } catch (Exception $e) {
        echo 'Exception: ',  $e->getMessage(), "\n";
    } 
    
});

$app->get('/playlist/:playlistId/:max', function($playlistId, $max) use ($youtubeService) {
    try {
        print($youtubeService->getYoutubeData($playlistId, $max,''));
    } catch (Exception $e) {
        echo 'Exception: ',  $e->getMessage(), "\n";
    } 
});

$app->get('/playlist/:playlistId/:max/:page_token', function($playlistId, $max, $pageToken) use ($youtubeService) {
    try {
        print($youtubeService->getYoutubeData($playlistId, $max,$pageToken));
    } catch (Exception $e) {
        echo 'Exception: ',  $e->getMessage(), "\n";
    } 
});

$app->error(function (\Exception $e) {
    echo 'Exception: ',  $e->getMessage(), "\n";
});

$app->run();
