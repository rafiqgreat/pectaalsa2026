<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'home';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Teacher registration wizard (canonical: /singup)
$route['singup'] = 'signup/index';
$route['singup/step/(:num)'] = 'signup/step/$1';
$route['singup/save_personal'] = 'signup/save_personal';
$route['singup/save_address'] = 'signup/save_address';
$route['singup/save_education'] = 'signup/save_education';
$route['singup/save_experience'] = 'signup/save_experience';
$route['singup/save_bank'] = 'signup/save_bank';
$route['singup/save_specialization'] = 'signup/save_specialization';
$route['singup/save_security'] = 'signup/save_security';
$route['singup/save_emarking'] = 'signup/save_emarking';
$route['singup/upload_file'] = 'signup/upload_file';
$route['singup/delete_file'] = 'signup/delete_file';

// Host the wizard on the existing registration URL
$route['user/login/register'] = 'signup/index';
$route['user/login/register/resume'] = 'signup/resume';
$route['user/login/register/resume_submit'] = 'signup/resume_submit';
$route['user/login/register/step/(:num)'] = 'signup/step/$1';
$route['user/login/register/save_personal'] = 'signup/save_personal';
$route['user/login/register/save_address'] = 'signup/save_address';
$route['user/login/register/save_education'] = 'signup/save_education';
$route['user/login/register/save_experience'] = 'signup/save_experience';
$route['user/login/register/save_bank'] = 'signup/save_bank';
$route['user/login/register/save_specialization'] = 'signup/save_specialization';
$route['user/login/register/save_security'] = 'signup/save_security';
$route['user/login/register/save_emarking'] = 'signup/save_emarking';
$route['user/login/register/upload_file'] = 'signup/upload_file';
$route['user/login/register/delete_file'] = 'signup/delete_file';

// Backward-compatible aliases
$route['signup'] = 'signup/index';
$route['signup/step/(:num)'] = 'signup/step/$1';
$route['signup/(:any)'] = 'signup/$1';
$route['Signup'] = 'signup/index';
$route['Signup/step/(:num)'] = 'signup/step/$1';
$route['Signup/(:any)'] = 'signup/$1';

$route['admin'] = 'admin/login';
$route['admin/(:any)'] = 'admin/$1';
$route['admin/(:any)/(:any)'] = 'admin/$1/$2';
$route['admin/(:any)/(:any)/(:any)'] = 'admin/$1/$2/$3';

$route['user'] = 'user/login';
$route['user/(:any)'] = 'user/$1';
$route['user/(:any)/(:any)'] = 'user/$1/$2';
$route['user/(:any)/(:any)/(:any)'] = 'user/$1/$2/$3';



// [AdminLTE]
// $route['adminlte/(:any)'] = 'adminlte/main/$1';

// Admin E-Marker edit wizard (extra segments beyond default admin routes)
$route['admin/emarkers/edit/(:num)'] = 'admin/emarkers/edit/$1';
$route['admin/emarkers/edit/(:num)/(:num)'] = 'admin/emarkers/edit/$1/$2';


