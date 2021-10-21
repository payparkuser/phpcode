<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Maatwebsite\Excel\Facades\Excel;

use App\Exports\UsersExport;

use App\Exports\ProvidersExport;

use App\Exports\BookingsExport;

use App\Exports\BookingPaymentsExport;

use App\Exports\ProviderSubscriptionPaymentsExport;

use Illuminate\Contracts\View\View;

Use App\User;

use Setting;

class ExportReportsController extends Controller
{
    
    /**
     * @method export_users()
     *
     * @uses Export user details.
     *
     * @created Anjana
     *
     * @updated Anjana
     *
     * @param $formate(request)
     * 
     * @return downloads the file of specified formate
     *
     */       
    public function export_users(Request $request) {

        $formats = [ 'xlsx' => '.xlsx', 'csv' => '.csv', 'xls' => '.xls', 'pdf' => '.pdf'];

        $file_format = isset($formats[$request->format]) ? $formats[$request->format] : '.xlsx';

        $filename = Setting::get('site_name', 'RentCubo').'_users_'.date('Y-m-d').$file_format;

        return Excel::download(new UsersExport, $filename);
    }
    
    /**
     * @method export_providers()
     *
     * @uses Export provider details.
     *
     * @created Anjana
     *
     * @updated Anjana
     *
     * @param $formate(request)
     * 
     * @return downloads the file of specified formate
     *
     */
    public function export_providers(Request $request) {

        $formats = [ 'xlsx' => '.xlsx', 'csv' => '.csv', 'xls' => '.xls', 'pdf' => '.pdf'];

        $file_format = isset($formats[$request->format]) ? $formats[$request->format] : '.xlsx';

        $filename = Setting::get('site_name', 'RentCubo').'_providers_'.date('Y-m-d').$file_format;

        return Excel::download(new ProvidersExport, $filename);
    }    
    
    /**
     * @method export_bookings()
     *
     * @uses Export booking details.
     *
     * @created Anjana
     *
     * @updated Anjana
     *
     * @param $formate(request)
     * 
     * @return downloads the file of specified formate
     *
     */
    public function export_bookings(Request $request) {

        $formats = [ 'xlsx' => '.xlsx', 'csv' => '.csv', 'xls' => '.xls', 'pdf' => '.pdf'];

        $file_format = isset($formats[$request->format]) ? $formats[$request->format] : '.xlsx';
        
        $filename = Setting::get('site_name', 'RentCubo').'_bookings_'.date('Y-m-d').$file_format;

        return Excel::download(new BookingsExport, $filename);
    }    
    
    /**
     * @method export_booking_payments()
     *
     * @uses Export booking_payment details.
     *
     * @created Anjana
     *
     * @updated Anjana
     *
     * @param $formate(request)
     * 
     * @return downloads the file of specified formate
     *
     */
    public function export_booking_payments(Request $request) {

        $formats = [ 'xlsx' => '.xlsx', 'csv' => '.csv', 'xls' => '.xls', 'pdf' => '.pdf'];

        $file_format = isset($formats[$request->format]) ? $formats[$request->format] : '.xlsx';

        $filename = Setting::get('site_name', 'RentCubo').'_booking_payments_'.date('Y-m-d').$file_format;

        return Excel::download(new BookingPaymentsExport, $filename);
    }    

    /**
     * @method export_provider_subscription_payments()
     *
     * @uses Export provider_subscription_payment details.
     *
     * @created Anjana
     *
     * @updated Anjana
     *
     * @param $formate(request)
     * 
     * @return downloads the file of specified formate
     *
     */
    public function export_provider_subscription_payments(Request $request) {

        $formats = [ 'xlsx' => '.xlsx', 'csv' => '.csv', 'xls' => '.xls', 'pdf' => '.pdf'];

        $file_format = isset($formats[$request->format]) ? $formats[$request->format] : '.xlsx';

        $filename = Setting::get('site_name', 'RentCubo').'_provider_subscription_payments_'.date('Y-m-d').$file_format;
        
        return Excel::download(new ProviderSubscriptionPaymentsExport, $filename);
    }

}
