<?php

Route::group(['middleware' => 'web'], function() {

    Route::get('test', 'AdminController@test');

    Route::group(['as' => 'admin.', 'prefix' => 'admin'], function() {

        Route::get('important/constants', 'ApplicationController@list_of_constants');
       
        Route::get('reports/provider', 'ApplicationController@weekly_reports_providers');
       
        Route::get('reports/admin', 'ApplicationController@weekly_reports_admin');

        Route::get('export/users', 'ExportReportsController@export_users')->name('export.users');
        
        Route::get('export/providers', 'ExportReportsController@export_providers')->name('export.providers');
        
        Route::get('export/bookings', 'ExportReportsController@export_bookings')->name('export.bookings');

        Route::get('export/booking_payments', 'ExportReportsController@export_booking_payments')->name('export.booking_payments');
       
        Route::get('export/subscription_payments', 'ExportReportsController@export_provider_subscription_payments')->name('export.provider_subscription_payments');

        Route::get('auto_bookings_checkout', 'ApplicationController@auto_bookings_checkout');

        Route::get('auto_bookings_checkin', 'ApplicationController@auto_bookings_checkin');

        Route::get('push_notification_testing', 'ApplicationController@push_notification_testing');
        

        Route::get('/clear-cache', function() {

            $exitCode = Artisan::call('config:cache');

            return back();

        })->name('clear-cache');

        Route::get('login', 'Auth\AdminLoginController@showLoginForm')->name('login');

        Route::post('login', 'Auth\AdminLoginController@login')->name('login.post');

        Route::get('logout', 'Auth\AdminLoginController@logout')->name('logout');

        Route::get('/profile', 'AdminController@profile')->name('profile');

        Route::post('/profile/save', 'AdminController@profile_save')->name('profile.save');

        Route::post('/change/password', 'AdminController@change_password')->name('change.password');

        Route::get('/', 'AdminController@index')->name('dashboard');

        

        Route::get('/password/reset','Auth\AdminLoginController@showLinkRequestForm')->name('password.request');

        Route::post('forgot_password_update', 'Auth\AdminLoginController@forgot_password_update')->name('forgot_password.update');

        Route::post('reset_password_update', 'Auth\AdminLoginController@reset_password_update')->name('reset_password.update');

        Route::get('/reset/password', 'Auth\AdminLoginController@reset_password')->name('reset_password');

        /***
         *
         * Admin Account releated routes
         *
         */
        // Users CRUD Operations
       
        Route::get('/users/index', 'AdminController@users_index')->name('users.index');

        Route::get('/users/create', 'AdminController@users_create')->name('users.create');

        Route::get('/users/edit', 'AdminController@users_edit')->name('users.edit');    

        Route::post('/users/save', 'AdminController@users_save')->name('users.save');

        Route::get('/users/view', 'AdminController@users_view')->name('users.view');

        Route::get('/users/delete', 'AdminController@users_delete')->name('users.delete');

        Route::get('/users/status', 'AdminController@users_status')->name('users.status');

        Route::get('/users/verify', 'AdminController@users_verify_status')->name('users.verify');

        Route::get('/users/wishlist/index', 'AdminController@wishlists_index')->name('wishlists.index');

        Route::get('/users/wishlist/delete', 'AdminController@wishlists_delete')->name('wishlists.delete');

        Route::get('/wishlists/status', 'AdminController@wishlists_status')->name('wishlists.status');

        Route::post('/users/bulk_action', 'AdminController@users_bulk_action')->name('users.bulk_action');
        
        // Providers CRUD operations

        Route::get('/providers/index', 'AdminController@providers_index')->name('providers.index');

        Route::get('/providers/create', 'AdminController@providers_create')->name('providers.create');

        Route::get('/providers/edit', 'AdminController@providers_edit')->name('providers.edit');

        Route::post('/providers/save', 'AdminController@providers_save')->name('providers.save');

        Route::get('/providers/view/', 'AdminController@providers_view')->name('providers.view');

        Route::get('/providers/revenues/', 'AdminController@providers_revenues')->name('providers.revenues');

        Route::get('/providers/delete', 'AdminController@providers_delete')->name('providers.delete');

        Route::get('/providers/status', 'AdminController@providers_status')->name('providers.status');

        Route::get('/providers/verify', 'AdminController@providers_verify_status')->name('providers.verify');

        Route::get('/providers/documents/view', 'AdminController@providers_documents_view')->name('providers.documents.view');

        Route::get('/providers/documents/index', 'AdminController@providers_documents_index')->name('providers.documents.index');

        Route::get('/providers/documents/status', 'AdminController@providers_documents_status')->name('providers.documents.status');

        Route::post('/providers/bulk_action', 'AdminController@providers_bulk_action')->name('providers.bulk_action');

        // Vehicle Details CRUD operations

        Route::get('/vehicle_details/create', 'AdminController@vehicle_details_create')->name('vehicle_details.create');

        Route::get('/vehicle_details/edit', 'AdminController@vehicle_details_edit')->name('vehicle_details.edit');

        Route::post('/vehicle_details/save', 'AdminController@vehicle_details_save')->name('vehicle_details.save');

        Route::get('/vehicle_details/delete', 'AdminController@vehicle_details_delete')->name('vehicle_details.delete');


        // service locations CRUD operations

        Route::get('/service_locations/index', 'AdminController@service_locations_index')->name('service_locations.index');

        Route::get('/service_locations/create', 'AdminController@service_locations_create')->name('service_locations.create');

        Route::get('/service_locations/edit', 'AdminController@service_locations_edit')->name('service_locations.edit');

        Route::post('/service_locations/save', 'AdminController@service_locations_save')->name('service_locations.save');

        Route::get('/service_locations/view', 'AdminController@service_locations_view')->name('service_locations.view');

        Route::get('/service_locations/delete', 'AdminController@service_locations_delete')->name('service_locations.delete');

        Route::get('/service_locations/status', 'AdminController@service_locations_status')->name('service_locations.status');

        Route::post('/service_locations/bulk_action', 'AdminController@service_locations_bulk_action')->name('service_locations.bulk_action');

        // Bookings CRUD operations

        Route::get('/bookings/dashboard', 'AdminController@bookings_dashboard')->name('bookings.dashboard');

        Route::get('/bookings/index', 'AdminController@bookings_index')->name('bookings.index');

        Route::post('/bookings/status/{id}', 'AdminController@bookings_status')->name('bookings.status'); 
        
        Route::get('/bookings/view', 'AdminController@bookings_view')->name('bookings.view');

        Route::get('/bookings/cancel', 'AdminController@bookings_cancel')->name('bookings.cancel');

        // Revenues
       
        Route::get('/revenues/dashboard', 'AdminController@revenues_dashboard')->name('revenues.dashboard');

        Route::get('/bookings/payments', 'AdminController@bookings_payments')->name('bookings.payments');

        // Reviews

        Route::get('/reviews/providers','AdminController@reviews_providers')->name('reviews.providers');

        Route::get('/reviews/users','AdminController@reviews_users')->name('reviews.users');

        Route::get('/reviews/users/view', 'AdminController@reviews_users_view')->name('reviews.users.view');

        Route::get('/reviews/providers/view', 'AdminController@reviews_providers_view')->name('reviews.providers.view');


        // settings

        Route::get('/settings', 'AdminController@settings')->name('settings'); 
     
        Route::get('/admin-control', 'AdminController@admin_control')->name('control'); 
        Route::get('/ios-control', 'AdminController@ios_control')->name('ios-control'); 
        
        Route::get('/settings_generate_json', 'AdminController@settings_generate_json')->name('settings_generate_json'); 
     
        Route::post('/settings/save', 'AdminController@settings_save')->name('settings.save'); 

        Route::post('/env_settings','AdminController@env_settings_save')->name('env-settings.save');

        // STATIC PAGES

        Route::get('/static_pages' , 'AdminController@static_pages_index')->name('static_pages.index');

        Route::get('/static_pages/create', 'AdminController@static_pages_create')->name('static_pages.create');

        Route::get('/static_pages/edit', 'AdminController@static_pages_edit')->name('static_pages.edit');

        Route::post('/static_pages/save', 'AdminController@static_pages_save')->name('static_pages.save');

        Route::get('/static_pages/delete', 'AdminController@static_pages_delete')->name('static_pages.delete');

        Route::get('/static_pages/view', 'AdminController@static_pages_view')->name('static_pages.view');

        Route::get('/static_pages/status', 'AdminController@static_pages_status_change')->name('static_pages.status');


        // Documents CRUD operations

        Route::get('/documents/index', 'AdminController@documents_index')->name('documents.index');

        Route::get('/documents/create', 'AdminController@documents_create')->name('documents.create');

        Route::get('/documents/edit', 'AdminController@documents_edit')->name('documents.edit');

        Route::post('/documents/save', 'AdminController@documents_save')->name('documents.save');

        Route::get('/documents/view', 'AdminController@documents_view')->name('documents.view');

        Route::get('/documents/delete', 'AdminController@documents_delete')->name('documents.delete');

        Route::get('/documents/status', 'AdminController@documents_status')->name('documents.status');


        Route::get('/help','AdminController@help')->name('help');

        //ProviderSubscription Methods.

        Route::get('provider_subscriptions/index', 'AdminController@provider_subscriptions_index')->name('provider_subscriptions.index');

        Route::get('provider_subscriptions/create', 'AdminController@provider_subscriptions_create')->name('provider_subscriptions.create');

        Route::get('provider_subscriptions/edit', 'AdminController@provider_subscriptions_edit')->name('provider_subscriptions.edit'); 

        Route::post('provider_subscriptions/save', 'AdminController@provider_subscriptions_save')->name('provider_subscriptions.save');

        Route::get('provider_subscriptions/view', 'AdminController@provider_subscriptions_view')->name('provider_subscriptions.view');

        Route::get('provider_subscriptions/delete', 'AdminController@provider_subscriptions_delete')->name('provider_subscriptions.delete');

        Route::get('provider_subscriptions/status', 'AdminController@provider_subscriptions_status_change')->name('provider_subscriptions.status');

       Route::get('provider_subscriptions/is_popular', 'AdminController@provider_subscriptions_is_popular')->name('provider_subscriptions.is_popular');
       
        Route::get('provider_subscriptions/payments', 'AdminController@provider_subscription_payments')->name('provider_subscriptions.payments');

        Route::get('provider_subscriptions/plans', 'AdminController@provider_subscriptions_plans')->name('provider_subscriptions.plans');

        Route::get('provider/subscriptions/plancancelled_reasons/save', 'AdminController@provider_subscriptions_plans_save')->name('providers.subscriptions.plans.save');

        Route::post('provider/subscriptions/disable', 'AdminController@provider_auto_subscription_disable')->name('providers.subscriptions.cancel');

        Route::get('/provider/subscriptions/enable', 'AdminController@provider_auto_subscription_enable')->name('providers.subscriptions.enable');

        //Provider Subscription Payments

        Route::get('/provider_subscriptions/payments/view', 'AdminController@provider_subscription_payments_view')->name('provider.subscriptions.payments.view');

        // Save the Admin control status 

        // Hosts CRUD operations
       
        Route::get('/spaces/index', 'AdminController@spaces_index')->name('spaces.index');

        Route::get('/spaces/create', 'AdminController@spaces_create')->name('spaces.create');

        Route::get('/spaces/edit', 'AdminController@spaces_edit')->name('spaces.edit');

        Route::post('/spaces/save', 'AdminController@spaces_save')->name('spaces.save');

        Route::get('/spaces/view', 'AdminController@spaces_view')->name('spaces.view');

        Route::get('/spaces/delete', 'AdminController@spaces_delete')->name('spaces.delete');

        Route::get('/spaces/status', 'AdminController@spaces_status')->name('spaces.status'); 

        Route::get('/spaces/verification', 'AdminController@spaces_verification_status')->name('spaces.verification_status');

        Route::get('/spaces/availability/create', 'AdminController@spaces_availability_create')->name('spaces.availability.create');         

        Route::post('/spaces/availability/save', 'AdminController@spaces_availability_save')->name('spaces.availability.save');     

        Route::get('/spaces/availability/delete', 'AdminController@spaces_availability_delete')->name('spaces.availability.delete'); 

        Route::get('/spaces/gallery/index', 'AdminController@spaces_gallery_index')->name('spaces.gallery.index'); 

        Route::get('/spaces/gallery/delete', 'AdminController@spaces_gallery_delete')->name('spaces.gallery.delete'); 

        Route::post('/spaces/gallery/save', 'AdminController@spaces_gallery_save')->name('spaces.gallery.save');

        Route::post('/spaces/bulk_action', 'AdminController@spaces_bulk_action')->name('spaces.bulk_action');  


        // Save the Admin control status

        Route::get('/provider_redeems', 'AdminController@provider_redeems')->name('provider_redeems.index');

        Route::post('/provider_redeems/payment', 'AdminController@provider_redeems_payment')->name('provider_redeems.payment');

        Route::get('/user_refunds', 'AdminController@user_refunds')->name('user_refunds.index');

        Route::post('/user_refunds/payment', 'AdminController@user_refunds_payment')->name('user_refunds.payment');


        // amenities operations

        Route::get('/amenities/index', 'AdminController@amenities_index')->name('amenities.index');

        Route::get('/amenities/create', 'AdminController@amenities_create')->name('amenities.create');

        Route::get('/amenities/edit', 'AdminController@amenities_edit')->name('amenities.edit');

        Route::post('/amenities/save', 'AdminController@amenities_save')->name('amenities.save');

        Route::get('/amenities/view/', 'AdminController@amenities_view')->name('amenities.view');

        Route::get('/amenities/delete/', 'AdminController@amenities_delete')->name('amenities.delete');

        Route::get('/amenities/status/', 'AdminController@amenities_status')->name('amenities.status');
       
        Route::post('get_amenities', 'ApplicationController@get_amenities')->name('get_amenities');

        Route::post('/amenities/bulk_action', 'AdminController@amenities_bulk_action')->name('amenities.bulk_action');

        Route::get('/providers/documents/verify', 'AdminController@providers_document_verify_status')->name('providers.documents.verify');

        // Custom Push

        Route::get('/custom/push', 'AdminController@custom_push')->name('push');

        Route::post('/custom/push', 'AdminController@custom_push_save')->name('send.push');

        
    });


});