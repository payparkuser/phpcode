<?php

/*
|--------------------------------------------------------------------------
| Application Constants
|--------------------------------------------------------------------------
|
| 
|
*/

if(!defined('SAMPLE_ID')) define('SAMPLE_ID', 1);

if(!defined('TAKE_COUNT')) define('TAKE_COUNT', 6);

if(!defined('NO')) define('NO', 0);
if(!defined('YES')) define('YES', 1);

if(!defined('PAID')) define('PAID',1);
if(!defined('UNPAID')) define('UNPAID', 0);


if(!defined('AVAILABLE')) define('AVAILABLE', 1);
if(!defined('NOTAVAILABLE')) define('NOTAVAILABLE', 0);

if(!defined('DATE_AVAILABLE')) define('DATE_AVAILABLE', 1);
if(!defined('DATE_NOTAVAILABLE')) define('DATE_NOTAVAILABLE', 0);

if(!defined('DEVICE_ANDROID')) define('DEVICE_ANDROID', 'android');
if(!defined('DEVICE_IOS')) define('DEVICE_IOS', 'ios');
if(!defined('DEVICE_WEB')) define('DEVICE_WEB', 'web');

if(!defined('APPROVED')) define('APPROVED', 1);
if(!defined('DECLINED')) define('DECLINED', 0);

if(!defined('DEFAULT_TRUE')) define('DEFAULT_TRUE', true);
if(!defined('DEFAULT_FALSE')) define('DEFAULT_FALSE', false);

if(!defined('ADMIN')) define('ADMIN', 'admin');
if(!defined('USER')) define('USER', 'user');
if(!defined('PROVIDER')) define('PROVIDER', 'provider');


if(!defined('COD')) define('COD',   'COD');
if(!defined('PAYPAL')) define('PAYPAL', 'PAYPAL');
if(!defined('CARD')) define('CARD',  'CARD');

if(!defined('STRIPE_MODE_LIVE')) define('STRIPE_MODE_LIVE',  'live');
if(!defined('STRIPE_MODE_SANDBOX')) define('STRIPE_MODE_SANDBOX',  'sandbox');

//////// USERS

if(!defined('USER_TYPE_NORMAL')) define('USER_TYPE_NORMAL', 0);
if(!defined('USER_TYPE_PAID')) define('USER_TYPE_PAID', 1);

if(!defined('USER_PENDING')) define('USER_PENDING', 0);
if(!defined('USER_APPROVED')) define('USER_APPROVED', 1);
if(!defined('USER_DECLINED')) define('USER_DECLINED', 2);

if(!defined('USER_EMAIL_NOT_VERIFIED')) define('USER_EMAIL_NOT_VERIFIED', 0);
if(!defined('USER_EMAIL_VERIFIED')) define('USER_EMAIL_VERIFIED', 1);

if(!defined('USER_STEP_WELCOME')) define('USER_STEP_WELCOME', 0);
if(!defined('USER_STEP_COMPLETED')) define('USER_STEP_COMPLETED', 1);

//////// PROVIDERs

if(!defined('PROVIDER_TYPE_NORMAL')) define('PROVIDER_TYPE_NORMAL', 0);
if(!defined('PROVIDER_TYPE_PAID')) define('PROVIDER_TYPE_PAID', 1);

if(!defined('PROVIDER_PENDING')) define('PROVIDER_PENDING', 0);
if(!defined('PROVIDER_APPROVED')) define('PROVIDER_APPROVED', 1);
if(!defined('PROVIDER_DECLINED')) define('PROVIDER_DECLINED', 2);

if(!defined('PROVIDER_EMAIL_NOT_VERIFIED')) define('PROVIDER_EMAIL_NOT_VERIFIED', 0);
if(!defined('PROVIDER_EMAIL_VERIFIED')) define('PROVIDER_EMAIL_VERIFIED', 1);

if(!defined('PROVIDER_STEP_WELCOME')) define('PROVIDER_STEP_WELCOME', 0);
if(!defined('PROVIDER_STEP_COMPLETED')) define('PROVIDER_STEP_COMPLETED', 1);

if(!defined('ADMIN_SPACE_PENDING')) define('ADMIN_SPACE_PENDING',0);

//////// USERS END

/***** ADMIN CONTROLS KEYS ********/

if(!defined('ADMIN_CONTROL_ENABLED')) define("ADMIN_CONTROL_ENABLED", 1);

if(!defined('ADMIN_CONTROL_DISABLED')) define("ADMIN_CONTROL_DISABLED", 0);

if(!defined('NO_DEVICE_TOKEN')) define("NO_DEVICE_TOKEN", "NO_DEVICE_TOKEN");

// Notification settings

if(!defined('EMAIL_NOTIFICATION')) define('EMAIL_NOTIFICATION', 'email');

if(!defined('PUSH_NOTIFICATION')) define('PUSH_NOTIFICATION', 'push');


// SPACE related constants 

if(!defined('ACCESS_METHOD_KEY')) define('ACCESS_METHOD_KEY', 'Key');
if(!defined('ACCESS_METHOD_SECRET_CODE')) define('ACCESS_METHOD_SECRET_CODE', 'Secret_code');

//  The provider will have control over the SPACE ( show or hide )

if(!defined('SPACE_OWNER_PUBLISHED')) define('SPACE_OWNER_PUBLISHED' , 1);

if(!defined('SPACE_OWNER_UNPUBLISHED')) define('SPACE_OWNER_UNPUBLISHED' , 0);

// The admin will have control on the SPACE display ( approve or decline)

if(!defined('ADMIN_SPACE_APPROVED')) define('ADMIN_SPACE_APPROVED' , 1);

if(!defined('ADMIN_SPACE_PENDING')) define('ADMIN_SPACE_PENDING' , 0);

// On new SPACE listed, the admin needs to verify the SPACE

if(!defined('ADMIN_SPACE_VERIFY_PENDING')) define('ADMIN_SPACE_VERIFY_PENDING' , 0);

if(!defined('ADMIN_SPACE_VERIFIED')) define('ADMIN_SPACE_VERIFIED' , 1);

if(!defined('ADMIN_SPACE_VERIFY_DECLINED')) define('ADMIN_SPACE_VERIFY_DECLINED' , 2);



// These constants are used identify the home page api types http://prntscr.com/mahza1

// Home page data start

if(!defined('API_PAGE_TYPE_HOME')) define('API_PAGE_TYPE_HOME', 'HOME');

if(!defined('API_PAGE_TYPE_CATEGORY')) define('API_PAGE_TYPE_CATEGORY', "CATEGORY");

if(!defined('API_PAGE_TYPE_SUB_CATEGORY')) define('API_PAGE_TYPE_SUB_CATEGORY', "SUB_CATEGORY");

if(!defined('API_PAGE_TYPE_LOCATION')) define('API_PAGE_TYPE_LOCATION', "LOCATION");

// Home page data end

// Single data start

if(!defined('API_PAGE_TYPE_SEE_ALL')) define('API_PAGE_TYPE_SEE_ALL', "SEE_ALL");

if(!defined('API_PAGE_TYPE_TOP_RATED')) define('API_PAGE_TYPE_TOP_RATED', "TOP_RATED");

if(!defined('API_PAGE_TYPE_WISHLIST')) define('API_PAGE_TYPE_WISHLIST', "WISHLIST");

if(!defined('API_PAGE_TYPE_RECENT_UPLOADED')) define('API_PAGE_TYPE_RECENT_UPLOADED', "RECENT_UPLOADED");

if(!defined('API_PAGE_TYPE_SUGGESTIONS')) define('API_PAGE_TYPE_SUGGESTIONS', "SUGGESTIONS");

// Single data end



// Home page types 

if(!defined('URL_TYPE_CATEGORY')) define('URL_TYPE_CATEGORY' , 1);

if(!defined('URL_TYPE_SUB_CATEGORY')) define('URL_TYPE_SUB_CATEGORY' , 2);

if(!defined('URL_TYPE_LOCATION')) define('URL_TYPE_LOCATION' , 3);

if(!defined('URL_TYPE_TOP_RATED')) define('URL_TYPE_TOP_RATED' , 4);

if(!defined('URL_TYPE_WISHLIST')) define('URL_TYPE_WISHLIST' , 5);

if(!defined('URL_TYPE_RECENT_UPLOADED')) define('URL_TYPE_RECENT_UPLOADED' , 6);

if(!defined('URL_TYPE_SUGGESTIONS')) define('URL_TYPE_SUGGESTIONS' , 7);

// android view for hosts list

if(!defined('SECTION_TYPE_HORIZONTAL')) define('SECTION_TYPE_HORIZONTAL' , 'HORIZONTAL');

if(!defined('SECTION_TYPE_VERTICAL')) define('SECTION_TYPE_VERTICAL' , 'VERTICAL');

if(!defined('SECTION_TYPE_GRID')) define('SECTION_TYPE_GRID' , 'GRID');


if(!defined('BOOKING_INITIATE')) define('BOOKING_INITIATE' , 0);

if(!defined('BOOKING_ONPROGRESS')) define('BOOKING_ONPROGRESS' , 1);

if(!defined('BOOKING_WAITING_FOR_PAYMENT')) define('BOOKING_WAITING_FOR_PAYMENT' , 2);

if(!defined('BOOKING_DONE_BY_USER')) define('BOOKING_DONE_BY_USER' , 3);

if(!defined('BOOKING_CANCELLED_BY_USER')) define('BOOKING_CANCELLED_BY_USER' , 4);

if(!defined('BOOKING_CANCELLED_BY_PROVIDER')) define('BOOKING_CANCELLED_BY_PROVIDER' , 5);

if(!defined('BOOKING_COMPLETED')) define('BOOKING_COMPLETED' , 6);

if(!defined('BOOKING_REFUND_INITIATED')) define('BOOKING_REFUND_INITIATED' , 7);

if(!defined('BOOKING_CHECKIN')) define('BOOKING_CHECKIN' , 8);

if(!defined('BOOKING_CHECKOUT')) define('BOOKING_CHECKOUT' , 9);

if(!defined('BOOKING_REVIEW_DONE')) define('BOOKING_REVIEW_DONE' , 10);

if(!defined('BOOKING_APPROVED_BY_PROVIDER')) define('BOOKING_APPROVED_BY_PROVIDER' , 11);

if(!defined('BOOKING_CANCELLED_BY_ADMIN')) define('BOOKING_CANCELLED_BY_ADMIN' , 12);

if(!defined('PAYMENT_INITIATED')) define('PAYMENT_INITIATED' , 0);

if(!defined('PAYMENT_COMPLETED')) define('PAYMENT_COMPLETED' , 1);

if(!defined('PAYMENT_CANCELLED')) define('PAYMENT_CANCELLED' , 2);


if(!defined('SEARCH_OPTION_DATE')) define('SEARCH_OPTION_DATE' , 1);

if(!defined('SEARCH_OPTION_GUEST')) define('SEARCH_OPTION_GUEST' , 2);

if(!defined('SEARCH_OPTION_HOST_TYPE')) define('SEARCH_OPTION_HOST_TYPE' , 3);

if(!defined('SEARCH_OPTION_PRICE')) define('SEARCH_OPTION_PRICE' , 4);

if(!defined('SEARCH_OPTION_OTHER')) define('SEARCH_OPTION_OTHER' , 5);

if(!defined('SEARCH_OPTION_ROOMS_BEDS')) define('SEARCH_OPTION_ROOMS_BEDS' , 6);

if(!defined('SEARCH_OPTION_SUB_CATEGORY')) define('SEARCH_OPTION_SUB_CATEGORY' , 7);

if(!defined('SEARCH_OPTION_AMENTIES')) define('SEARCH_OPTION_AMENTIES' , 8);


// Bell notification status

if(!defined('BELL_NOTIFICATION_STATUS_UNREAD')) define('BELL_NOTIFICATION_STATUS_UNREAD', 1);

if(!defined('BELL_NOTIFICATION_STATUS_READ')) define('BELL_NOTIFICATION_STATUS_READ', 2);

// Bell notification redirection type

if(!defined('BELL_NOTIFICATION_REDIRECT_HOME')) define('BELL_NOTIFICATION_REDIRECT_HOME', 1);

if(!defined('BELL_NOTIFICATION_REDIRECT_SPACE_VIEW')) define('BELL_NOTIFICATION_REDIRECT_SPACE_VIEW', 2);

if(!defined('BELL_NOTIFICATION_REDIRECT_BOOKINGS')) define('BELL_NOTIFICATION_REDIRECT_BOOKINGS', 3);

if(!defined('BELL_NOTIFICATION_REDIRECT_BOOKING_VIEW')) define('BELL_NOTIFICATION_REDIRECT_BOOKING_VIEW', 4);

if(!defined('BELL_NOTIFICATION_REDIRECT_CHAT')) define('BELL_NOTIFICATION_REDIRECT_CHAT', 5);


// Bell Notification - Receiver Type

if(!defined('BELL_NOTIFICATION_RECEIVER_TYPE_USER')) define('BELL_NOTIFICATION_RECEIVER_TYPE_USER' , 'user');
if(!defined('BELL_NOTIFICATION_RECEIVER_TYPE_PROVIDER')) define('BELL_NOTIFICATION_RECEIVER_TYPE_PROVIDER' , 'provider');

// Bell Notification - Notification Type

if(!defined('BELL_NOTIFICATION_TYPE_BOOKING_DONE_BY_USER')) define('BELL_NOTIFICATION_TYPE_BOOKING_DONE_BY_USER' , 1);

if(!defined('BELL_NOTIFICATION_TYPE_BOOKING_APPROVED')) define('BELL_NOTIFICATION_TYPE_BOOKING_APPROVED', 2);

if(!defined('BELL_NOTIFICATION_TYPE_BOOKING_DECLINED')) define('BELL_NOTIFICATION_TYPE_BOOKING_DECLINED', 3);

if(!defined('BELL_NOTIFICATION_TYPE_CHECKIN')) define('BELL_NOTIFICATION_TYPE_CHECKIN', 4);

if(!defined('BELL_NOTIFICATION_TYPE_CHECKOUT')) define('BELL_NOTIFICATION_TYPE_CHECKOUT', 5);

if(!defined('BELL_NOTIFICATION_TYPE_BOOKING_CANCELLED_BY_USER')) define('BELL_NOTIFICATION_TYPE_BOOKING_CANCELLED_BY_USER' , 6);

if(!defined('BELL_NOTIFICATION_TYPE_BOOKING_CANCELLED_BY_PROVIDER')) define('BELL_NOTIFICATION_TYPE_BOOKING_CANCELLED_BY_PROVIDER' , 7);

if(!defined('BELL_NOTIFICATION_TYPE_BOOKING_CANCELLED_BY_ADMIN')) define('BELL_NOTIFICATION_TYPE_BOOKING_CANCELLED_BY_ADMIN' , 17);

if(!defined('BELL_NOTIFICATION_TYPE_USER_REVIEW')) define('BELL_NOTIFICATION_TYPE_USER_REVIEW' , 8);

if(!defined('BELL_NOTIFICATION_TYPE_PROVIDER_REVIEW')) define('BELL_NOTIFICATION_TYPE_PROVIDER_REVIEW' , 9);

if(!defined('BELL_NOTIFICATION_TYPE_SPACE_APPROVED')) define('BELL_NOTIFICATION_TYPE_SPACE_APPROVED' , 10);

if(!defined('BELL_NOTIFICATION_TYPE_SPACE_DECLINED')) define('BELL_NOTIFICATION_TYPE_SPACE_DECLINED' , 11);

if(!defined('BELL_NOTIFICATION_TYPE_BOOKING_REJECTED')) define('BELL_NOTIFICATION_TYPE_BOOKING_REJECTED', 13);

if(!defined('BELL_NOTIFICATION_TYPE_BOOKING_PAYMENT')) define('BELL_NOTIFICATION_TYPE_BOOKING_PAYMENT', 14);

if(!defined('BELL_NOTIFICATION_TYPE_BOOKING_PROVIDER_CHECKIN')) define('BELL_NOTIFICATION_TYPE_BOOKING_PROVIDER_CHECKIN', 15);

if(!defined('BELL_NOTIFICATION_TYPE_BOOKING_PROVIDER_CHECKOUT')) define('BELL_NOTIFICATION_TYPE_BOOKING_PROVIDER_CHECKOUT', 16);

if(!defined('BELL_NOTIFICATION_TYPE_BOOKINGS_REJECTED')) define('BELL_NOTIFICATION_TYPE_BOOKINGS_REJECTED', 12);
// User bell notification status

if(!defined('NOTIFICATION_BOOKING_ACCECPTED_BY_PROVIDER')) define('NOTIFICATION_BOOKING_ACCECPTED_BY_PROVIDER', 1);

if(!defined('NOTIFICATION_BOOKING_REJECTED_BY_PROVIDER')) define('NOTIFICATION_BOOKING_REJECTED_BY_PROVIDER', 2);

if(!defined('NOTIFICATION_BOOKING_CANCELED_BY_PROVIDER')) define('NOTIFICATION_BOOKING_CANCELED_BY_PROVIDER', 3);

if(!defined('NOTIFICATION_BOOKING_STARTED')) define('NOTIFICATION_BOOKING_STARTED', 4);

if(!defined('NOTIFICATION_BOOKING_COMPLETED')) define('NOTIFICATION_BOOKING_COMPLETED', 5);

if(!defined('NOTIFICATION_BOOKING_REVIEWED_BY_PROVIDER')) define('NOTIFICATION_BOOKING_REVIEWED_BY_PROVIDER', 6);

if(!defined('NOTIFICATION_NEW_MESSAGE_FROM_PROVIDER')) define('NOTIFICATION_NEW_MESSAGE_FROM_PROVIDER', 7);

if(!defined('NOTIFICATION_NEW_SPACE_UPLOADED')) define('NOTIFICATION_NEW_SPACE_UPLOADED', 8);

// Provider bell notification status

if(!defined('NOTIFICATION_BOOKING_NEW_FROM_USER')) define('NOTIFICATION_BOOKING_NEW_FROM_USER', 9);

if(!defined('NOTIFICATION_BOOKING_CANCELED_BY_USER')) define('NOTIFICATION_BOOKING_CANCELED_BY_USER', 10);

if(!defined('NOTIFICATION_BOOKING_REVIEWED_BY_USER')) define('NOTIFICATION_BOOKING_REVIEWED_BY_USER', 11);

if(!defined('NOTIFICATION_NEW_MESSAGE_FROM_USER')) define('NOTIFICATION_NEW_MESSAGE_FROM_USER', 12);

if(!defined('NOTIFICATION_SPACE_APPROVED')) define('NOTIFICATION_SPACE_APPROVED', 13);

if(!defined('NOTIFICATION_SPACE_DECLINED')) define('NOTIFICATION_SPACE_DECLINED', 14);

if(!defined('NOTIFICATION_SPACE_VERIFIED')) define('NOTIFICATION_SPACE_VERIFIED', 15);


if(!defined("DROPDOWN")) define("DROPDOWN", 'dropdown');

if(!defined('CHECKBOX')) define('CHECKBOX', 'checkbox');

if(!defined('RADIO')) define('RADIO', 'radio');

if(!defined('SPINNER')) define('SPINNER', 'spinner');

if(!defined('SPINNER_CALL_SUB_CATEGORY')) define('SPINNER_CALL_SUB_CATEGORY', 'call_sub_category_api');

if(!defined('SWITCH')) define('SWITCH', 'switch');

if(!defined('RANGE')) define('RANGE', 'range');

if(!defined('AVAILABILITY_CALENDAR')) define('AVAILABILITY_CALENDAR', 'availability_calendar');


if(!defined('ABOUT_HOST_SPACE')) define('ABOUT_HOST_SPACE', 'about_host_space');

if(!defined('REVIEW')) define('REVIEW', 'REVIEW');

if(!defined('TEXTAREA')) define('TEXTAREA', 'textarea');

if(!defined("SELECT")) define("SELECT", 'select');

if(!defined('INPUT')) define('INPUT', 'input');

if(!defined('INPUT_NUMBER')) define('INPUT_NUMBER', 'number');

if(!defined('INPUT_TEXT')) define('INPUT_TEXT', 'text');

if(!defined('INPUT_TEXTAREA')) define('INPUT_TEXTAREA', 'textarea');

if(!defined('INPUT_GOOGLE_PLACE_SEARCH')) define('INPUT_GOOGLE_PLACE_SEARCH', 'input_place_search');

if(!defined('MAP_VIEW')) define('MAP_VIEW', 'map_view');

if(!defined('DATE')) define('DATE', 'date');

if(!defined('INCREMENT_DECREMENT')) define('INCREMENT_DECREMENT', 'increment');

if(!defined('UPLOAD')) define('UPLOAD', 'upload');

if(!defined('UPLOAD_SINGLE')) define('UPLOAD_SINGLE', 'single');

if(!defined('UPLOAD_MULTIPLE')) define('UPLOAD_MULTIPLE', 'multiple');


if(!defined('PLAN_TYPE_MONTH')) define('PLAN_TYPE_MONTH', 'month');

if(!defined('PLAN_TYPE_DAY')) define('PLAN_TYPE_DAY', 'day');

if(!defined('PLAN_TYPE_YEAR')) define('PLAN_TYPE_YEAR', 'year');


if (!defined('PAID_STATUS')) define('PAID_STATUS', 1);


if (!defined('QUESTION_TYPE_AMENTIES')) define('QUESTION_TYPE_AMENTIES', 'amenties');

if (!defined('QUESTION_TYPE_NONE')) define('QUESTION_TYPE_NONE', 'none');

if (!defined('QUESTION_TYPE_RULES')) define('QUESTION_TYPE_RULES', 'rules');

// Push notification redirection type

if(!defined('PUSH_NOTIFICATION_REDIRECT_HOME')) define('PUSH_NOTIFICATION_REDIRECT_HOME', 1);

if(!defined('PUSH_NOTIFICATION_REDIRECT_SPACE_VIEW')) define('PUSH_NOTIFICATION_REDIRECT_SPACE_VIEW', 2);

if(!defined('PUSH_NOTIFICATION_REDIRECT_BOOKINGS')) define('PUSH_NOTIFICATION_REDIRECT_BOOKINGS', 3);

if(!defined('PUSH_NOTIFICATION_REDIRECT_BOOKING_VIEW')) define('PUSH_NOTIFICATION_REDIRECT_BOOKING_VIEW', 4);

if(!defined('PUSH_NOTIFICATION_REDIRECT_CHAT')) define('PUSH_NOTIFICATION_REDIRECT_CHAT', 5);

// Static pages sections

if(!defined('STATIC_PAGE_SECTION_1')) define('STATIC_PAGE_SECTION_1', 1);

if(!defined('STATIC_PAGE_SECTION_2')) define('STATIC_PAGE_SECTION_2', 2);

if(!defined('STATIC_PAGE_SECTION_3')) define('STATIC_PAGE_SECTION_3', 3);

if(!defined('STATIC_PAGE_SECTION_4')) define('STATIC_PAGE_SECTION_4', 4);



if (!defined('SPACE_TYPE_DRIVEWAY')) define('SPACE_TYPE_DRIVEWAY', 'driveway');

if (!defined('SPACE_TYPE_GARAGE')) define('SPACE_TYPE_GARAGE', 'garage');

if (!defined('SPACE_TYPE_CAR_PARK')) define('SPACE_TYPE_CAR_PARK', 'carpark');


if (!defined('SPACE_AVAIL_ADD_SPACE')) define('SPACE_AVAIL_ADD_SPACE', 1);

if (!defined('SPACE_AVAIL_REMOVE_SPACE')) define('SPACE_AVAIL_REMOVE_SPACE', 0);

if (!defined('INCHES')) define('INCHES', 'Inches');



if (!defined('DOCUMENT_TYPE_IDENTITY')) define('DOCUMENT_TYPE_IDENTITY', 'identity');

if (!defined('DOCUMENT_TYPE_OTHERS')) define('DOCUMENT_TYPE_OTHERS', 'others');


if (!defined('SPACE_BEST_MATCH')) define('SPACE_BEST_MATCH', 'best-match');

if (!defined('SPACE_CHEAPEST')) define('SPACE_CHEAPEST', 'cheapest');

if (!defined('SPACE_CLOSEST')) define('SPACE_CLOSEST', 'closest');

if(!defined('PRICE_TYPE_HOUR')) define('PRICE_TYPE_HOUR' , 'per_hour');

if(!defined('PRICE_TYPE_DAY')) define('PRICE_TYPE_DAY' , 'per_day');

if(!defined('PRICE_TYPE_MONTH')) define('PRICE_TYPE_MONTH' , 'per_month');


if(!defined('PROVIDER_DOCUMENT_NOT_UPLOADED')) define('PROVIDER_DOCUMENT_NOT_UPLOADED', 0);

if(!defined('PROVIDER_DOCUMENT_UPLOADED')) define('PROVIDER_DOCUMENT_UPLOADED', 1);

if(!defined('PROVIDER_DOCUMENT_VERIFIED')) define('PROVIDER_DOCUMENT_VERIFIED', 2);

if(!defined('APPROVE_ALL')) define('APPROVE_ALL', 1);

if(!defined('DECLINE_ALL')) define('DECLINE_ALL', 0);


