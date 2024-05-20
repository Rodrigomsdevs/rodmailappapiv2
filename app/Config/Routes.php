<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

//auth
$routes->get('api/login', 'AuthController::login');
$routes->post('api/register', 'AuthController::register');

//lista de email

$routes->get('api/email/list', 'EmailController::listUserEmails');
$routes->delete('api/email/delete/(:num)', 'EmailController::deleteEmail/$1');
$routes->post('api/email/markAsRead/(:num)', 'EmailController::markAsRead/$1');
$routes->get('api/email/(:num)', 'EmailController::showEmail/$1');


$routes->get('api/email/accounts/list', 'EmailController::listEmail');
$routes->delete('api/email/account/delete/(:num)', 'EmailController::deleteAccountEmail/$1');
$routes->post('api/email/account/add', 'EmailController::addEmail');
$routes->post('api/email/account/markAllAsRead', 'EmailController::markAllAsRead');


$routes->post('api/validate-token', 'AuthController::validateToken');
$routes->post('api/fcm/saveToken', 'FCMTokenController::saveToken');


$routes->get('api/test/(:any)/(:num)', 'EmailController::test/$1/$2');