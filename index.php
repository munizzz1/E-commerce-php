<?php 
session_start();
require_once("vendor/autoload.php");


use \Slim\Slim;

$app = new Slim();

	$app->config('debug', true);

	require_once($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'functionsRoute.php');
	require_once($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'site.php');
	require_once($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'admin-categories.php');
	require_once($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'admin-login.php');
	require_once($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'admin-products.php');
	require_once($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'admin-users.php');

	$app->run();

 ?>