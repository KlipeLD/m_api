<?php

namespace api;

require "../start.php";

use Src\Product;
use Firebase\JWT\JWT;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );

if( !isset($_SERVER ['PHP_AUTH_USER']))
{
  header("WWW-Authenticate: Basic realm=\"Private Area\"");
  header("HTTP/1.1 401 Unauthorized");
  print json_encode(array('message' => 'Sorry, you need proper credentials'));
  exit();
}
else
{
  if(!($_SERVER['PHP_AUTH_USER'] == $_ENV['API_USER'] && ($_SERVER['PHP_AUTH_PW'] == $_ENV['API_PASSWORD'])))
  {
    header("WWW-Authenticate: Basic realm=\"Private Area\"");
    header("HTTP/1.1 401 Unauthorized");
    print json_encode(array('message' => 'Sorry, you need proper credentials'));
    exit();
  }
}

if ($uri[1] !== 'product') {
  if($uri[1] !== 'products'){
    header("HTTP/1.1 404 Not Found");
    exit();
  }
}

if ($uri[1] == 'products' and isset($uri[2])) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

$productId = null;
if (isset($uri[2])) {
    $productId = (int) $uri[2];
}

$requestMethod = $_SERVER["REQUEST_METHOD"];

$controller = new Product($dbConnection, $requestMethod, $productId);
$controller->processRequest();