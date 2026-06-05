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
$route['singup/check_resume'] = 'signup/check_resume';

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
$route['user/login/register/check_resume'] = 'signup/check_resume';

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

// Admin e-Marking routes
$route['admin/emarking'] = 'admin/emarking/questions';
$route['admin/emarking/questions'] = 'admin/emarking/questions';
$route['admin/emarking/add-question'] = 'admin/emarking/add_question';
$route['admin/emarking/edit-question/(:num)'] = 'admin/emarking/edit_question/$1';
$route['admin/emarking/rubric-steps/(:num)'] = 'admin/emarking/rubric_steps/$1';
$route['admin/emarking/import-crq-images'] = 'admin/emarking/import_crq_images';
$route['admin/emarking/import-dictation-images'] = 'admin/emarking/import_dictation_images';
$route['admin/emarking/create-batch'] = 'admin/emarking/create_batch';
$route['admin/emarking/batches'] = 'admin/emarking/batches';
$route['admin/emarking/reports'] = 'admin/emarking/reports';
$route['admin/emarking/reports-eng-crqs-barcodes'] = 'admin/emarking/reports_eng_crqs_barcodes';
$route['admin/emarking/export-eng-crqs-barcodes-csv'] = 'admin/emarking/export_eng_crqs_barcodes_csv';
$route['admin/emarking/reports-eng-dict-barcodes'] = 'admin/emarking/reports_eng_dict_barcodes';
$route['admin/emarking/export-eng-dict-barcodes-csv'] = 'admin/emarking/export_eng_dict_barcodes_csv';
$route['admin/emarking/reports-urdu-crqs-barcodes'] = 'admin/emarking/reports_urdu_crqs_barcodes';
$route['admin/emarking/export-urdu-crqs-barcodes-csv'] = 'admin/emarking/export_urdu_crqs_barcodes_csv';
$route['admin/emarking/reports-urdu-dict-barcodes'] = 'admin/emarking/reports_urdu_dict_barcodes';
$route['admin/emarking/export-urdu-dict-barcodes-csv'] = 'admin/emarking/export_urdu_dict_barcodes_csv';
$route['admin/emarking/reports-math-crqs-barcodes'] = 'admin/emarking/reports_math_crqs_barcodes';
$route['admin/emarking/export-math-crqs-barcodes-csv'] = 'admin/emarking/export_math_crqs_barcodes_csv';
$route['admin/emarking/reports-science-crqs-barcodes'] = 'admin/emarking/reports_science_crqs_barcodes';
$route['admin/emarking/export-science-crqs-barcodes-csv'] = 'admin/emarking/export_science_crqs_barcodes_csv';
$route['admin/emarking/billing'] = 'admin/emarking/billing';

// eMarker marking routes
$route['emarker/marking/dashboard'] = 'emarker/marking/dashboard';
$route['emarker/marking/view-batch/(:num)'] = 'emarker/marking/view_batch/$1';
$route['emarker/marking/start/(:num)'] = 'emarker/marking/start/$1';
$route['emarker/marking/save-marks'] = 'emarker/marking/save_marks';
$route['emarker/marking/get_batch_for_checking'] = 'emarker/marking/get_batch_for_checking';

// E-Marker e-Marking module
$route['emarker'] = 'emarker/marking';
$route['emarker/(:any)'] = 'emarker/$1';
$route['emarker/(:any)/(:any)'] = 'emarker/$1/$2';
$route['emarker/(:any)/(:any)/(:any)'] = 'emarker/$1/$2/$3';



// [AdminLTE]
// $route['adminlte/(:any)'] = 'adminlte/main/$1';

// Admin E-Marker edit wizard (extra segments beyond default admin routes)
$route['admin/emarkers/edit/(:num)'] = 'admin/emarkers/edit/$1';
$route['admin/emarkers/edit/(:num)/(:num)'] = 'admin/emarkers/edit/$1/$2';


