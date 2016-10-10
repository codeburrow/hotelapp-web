<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/setup.php';

use HotelApp\Controllers;
use HotelApp\Router;

/** Load .env variables in development environment **/
//$dotenv = new Dotenv\Dotenv(__DIR__ . '/../app/');
//$dotenv->load();


$router = new Router\Router();

/******** GET ********/
//Public
$router->get('/', 'MainController', 'index');
//$router->get('/professor/[-\w\d\?\!\.]+', 'MainController', 'professor');
$router->get('/login', 'MainController', 'login');
$router->get('/logout', 'MainController', 'logout');
$router->get('/register', 'MainController', 'register');
$router->get('/contact', 'MainController', 'contact');
$router->get('/about', 'MainController', 'about');
$router->get('/push', 'MainController', 'push');

//Admin
$router->get('/admin/dashboard', 'AdminController', 'dashboard');
$router->get('/admin/login', 'AdminController', 'login');
$router->get('/admin/logout', 'AdminController', 'logout');


/******** POST ********/
//Public
$router->post('/login', 'MainController', 'postlogin');
$router->post('/register', 'MainController', 'postRegister');

//Admin
$router->post('/admin/login', 'AdminController', 'postLogin');


//See inside $router
//echo "<pre>";
//print_r($router);

$router->submit();

