<?php 

namespace App\Helpers;

use Hash, Exception, Log, Setting, DB;

use App\Repositories\BookingRepository as BookingRepo;

use App\Repositories\HostRepository as HostRepo;

use App\Admin, App\User, App\Provider;

use App\Wishlist;

use App\Host, App\Lookups;

use App\ServiceLocation;

use App\CommonQuestion, App\CommonQuestionAnswer;

use App\HostQuestionAnswer;

use App\HostAvailability;

use Illuminate\Support\Carbon;

// use Carbon\Carbon;

use Carbon\CarbonPeriod;

class HostHelper {

    /** 
     * @method check_valid_dates()
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param integer $host_id 
     * 
     * @param integer $user_id 
     *
     * @return boolean
     */
    
    public static function check_valid_dates($dates) {

        $list_dates = $dates ? explode(',', $dates) : [];

        $list_dates = array_filter($list_dates,function($date){
            return strtotime($date) > strtotime('today');
        });

        return $list_dates; 

    }

    /** 
     * @method wishlist_status()
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param integer $host_id 
     * 
     * @param integer $user_id 
     *
     * @return boolean
     */
    
    public static function wishlist_status($host_id, $user_id) {

        $wishlist_details = Wishlist::where('user_id', $user_id)->where('host_id', $host_id)->first();

        return $wishlist_details ? YES: NO;

    }

    /**
     *
     * @method locations_data()
     *
     * @uses used to get the list of hosts based on the location
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param integer $user_id
     *
     * @param integer $skip
     *
     * @return list of hosts
     */

    public static function locations_data($request) {

        try {

            $base_query = ServiceLocation::CommonResponse()->orderby('service_locations.created_at' , 'desc');

            $take = Setting::get('admin_take_count', 12);

            $skip = $request->skip ?: 0;

            $service_locations = $base_query->skip($skip)->take($take)->get();

            foreach ($service_locations as $key => $service_location_details) {

                $service_location_details->api_page_type_id = $service_location_details->service_location_id;
            }

            return $service_locations;

        }  catch( Exception $e) {

            Log::info($e->getMessage());

            return [];

        }

    }

    /**
     *
     * @method location_based_hosts()
     *
     * @uses used to get the list of hosts based on the location
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param integer $user_id
     *
     * @param integer $skip
     *
     * @return list of hosts
     */

    public static function location_based_hosts($request) {

        try {

            $service_location_id = is_array($request->service_location_id) ? $request->service_location_id : [];

            $base_query = Host::whereIn('hosts.service_location_id', $service_location_id)
                            ->orderby('hosts.created_at' , 'desc');

            $take = Setting::get('admin_take_count', 12);

            $skip = $request->skip ?: 0;

            $host_ids = $base_query->skip($skip)->take($take)->pluck('hosts.id');

            $host_ids = $host_ids ? $host_ids->toArray() : [];

            $hosts = HostRepo::host_list_response($host_ids, $request->id);

            return $hosts;

        }  catch( Exception $e) {

            Log::info($e->getMessage());

            return [];

        }

    }

    /**
     *
     * @method recently_uploaded_hosts()
     *
     * @uses used to get the list of hosts based on the recently uploaded
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param integer $user_id
     *
     * @param integer $sub_profile_id
     *
     * @param integer $skip
     *
     * @return list of hosts
     */

    public static function recently_uploaded_hosts($request) {

        try {

            $base_query = Host::VerifedHostQuery()->orderby('hosts.created_at' , 'desc');

            // check page type 

            $base_query = self::get_page_type_query($request, $base_query);

            $take = Setting::get('admin_take_count', 12);

            $skip = $request->skip ?: 0;

            $host_ids = $base_query->skip($skip)->take($take)->pluck("hosts.id");

            $host_ids = $host_ids ? $host_ids->toArray() : [];

            $hosts = HostRepo::host_list_response($host_ids, $request->id);

            return $hosts;

        }  catch( Exception $e) {

            Log::info($e->getMessage());

            return [];

        }

    }

    /**
     *
     * @method top_rated_hosts()
     *
     * @uses used to get the list of hosts based on the recently uploaded
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param integer $user_id
     *
     * @param integer $sub_profile_id
     *
     * @param integer $skip
     *
     * @return list of hosts
     */

    public static function top_rated_hosts($request) {

        try {

            $base_query = Host::VerifedHostQuery()->orderby('hosts.created_at' , 'desc');

            $base_query = self::get_page_type_query($request, $base_query);

            $take = Setting::get('admin_take_count', 12);

            $skip = $request->skip ?: 0;

            $host_ids = $base_query->skip($skip)->take($take)->pluck('hosts.id');

            $host_ids = $host_ids ? $host_ids->toArray() : [];

            $hosts = HostRepo::host_list_response($host_ids, $request->id);

            return $hosts;

        }  catch( Exception $e) {

            Log::info($e->getMessage());

            return [];

        }

    }    

    /**
     *
     * @method suggestions()
     *
     * @uses used to get the list of hosts based on the booked & search history
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param integer $user_id
     *
     * @param integer $skip
     *
     * @return list of hosts
     */

    public static function suggestions($request) {

        try {

            $base_query = Host::VerifedHostQuery()->orderby('hosts.created_at' , 'desc');

            $base_query = self::get_page_type_query($request, $base_query);

            $take = Setting::get('admin_take_count', 12);

            $skip = $request->skip ?: 0;

            $host_ids = $base_query->skip($skip)->take($take)->pluck('hosts.id');

            $host_ids = $host_ids ? $host_ids->toArray() : [];

            $hosts = HostRepo::host_list_response($host_ids, $request->id);

            return $hosts;

        }  catch( Exception $e) {

            Log::info($e->getMessage());

            return [];

        }

    }

    /**
     *
     * @method get_page_type_query()
     *
     * @uses based on the page type, change the query
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param Request $request
     *
     * @param $base_query
     *
     * @return $base_query 
     */
    public static function get_page_type_query($request, $base_query) {

        if($request->api_page_type == API_PAGE_TYPE_HOME) {

            // No logics

        } elseif($request->api_page_type == API_PAGE_TYPE_LOCATION) {

            $base_query = $base_query->where('hosts.service_location_id', $request->api_page_type_id);

        }

        return $base_query;

    }

    /**
     * @method generate_date_range()
     * 
     * @uses Creating date collection between two dates
     *
     * @param string since any date, time or datetime format
     * 
     * @param string until any date, time or datetime format
     * 
     * @param string step
     * 
     * @param string date of output format
     * 
     * @return array
     */
    public static function generate_date_range($year = "", $month = "", $step = '+1 day', $output_format = 'd/m/Y', $loops = 2) {

        $year = $year ?: date('Y');

        $month = $month ?: date('m');

        $data = [];

        for($current_loop = 0; $current_loop < $loops; $current_loop++) {

            // Get the start and end date of the months

            $month_start_date = Carbon::createFromDate($year, $month, 01)->format('Y-m-d');

            $no_of_days = Carbon::parse($month_start_date)->daysInMonth;

            $month_end_date = Carbon::createFromDate($year, $month, $no_of_days)->format('Y-m-d');

            $period = CarbonPeriod::create($month_start_date, $month_end_date);

            $dates = [];

            // Iterate over the period
            foreach ($period as $date) {
                $dates[] = $date->format('Y-m-d');
            }

            // Create object

            $loop_data = new \stdClass;;

            $loop_data->month = $month;

            $loop_data->year = $year;

            $loop_data->total_days = $no_of_days;

            $loop_data->dates = $dates;

            array_push($data, $loop_data);

            // Update the next loops

            if($loops > 1) {

                $check_date = Carbon::createFromDate($year, $month, 01)->addMonth(1)->day(01);

                $year = $check_date->year;

                $month = $check_date->month;
            }
        
        }

        return $data;
    }

    /**
     * @method check_host_availablity()
     *
     * @uses 
     *
     * @param string since any date, time or datetime format
     * 
     * @param string until any date, time or datetime format
     * 
     * @param string step
     * 
     * @param string date of output format
     * 
     * @return array
     */
    public static function check_host_availablity($checkin, $checkout, $host_id) {

        // Get the intervals between two dates

       $period = CarbonPeriod::create($checkin, $checkout);

       $blocked_dates = 0;

        // Iterate over the period
        foreach ($period as $date) {

            // Check the dates are available 

            $is_blocked = HostAvailability::where('host_id', $host_id)->whereDate('available_date', $date)->where('is_blocked_booking', YES)->count();

            $blocked_dates += $is_blocked;

            $dates[] = $date->format('Y-m-d');

        }

        return $is_host_available =  $blocked_dates == 0 ? YES : NO;        

    }

    public static function location_block($request) {

        $locations = HostHelper::locations_data($request);

        $location_data['title'] = tr('URL_TYPE_LOCATION');

        $location_data['description'] = "";

        $location_data['api_page_type'] = API_PAGE_TYPE_LOCATION;

        $location_data['api_page_type_id'] = $request->api_page_type_id ?: 0;

        $location_data['is_see_all'] = NO;

        // $location_data['url_type'] = URL_TYPE_LOCATION;

        // $location_data['url_page_id'] = 0;

        $location_data['data'] = $locations;

        return $location_data;
    
    }

    public static function filter_guests($request) {

        $adults_data = $data = [];

        $adults_data['title'] = tr('adults');

        $adults_data['description'] = "";

        $adults_data['search_key'] = 'adults';

        array_push($data, $adults_data);

        $children_data = [];

        $children_data['title'] = tr('children');

        $children_data['description'] = "Ages 2 - 12";

        $children_data['search_key'] = 'children';

        array_push($data, $children_data);

        $infants_data = [];

        $infants_data['title'] = tr('infants');

        $infants_data['description'] = "Under 2";

        $infants_data['search_key'] = 'infants';

        array_push($data, $infants_data);

        return $data;

    }

    public static function filter_options_host_type($request) {

        $host_types_data = new \stdClass;

        $host_types_data->title = tr('SEARCH_OPTION_HOST_TYPE');

        $host_types_data->description = "";

        $host_types_data->search_type = SEARCH_OPTION_HOST_TYPE;

        $host_types_data->type = CHECKBOX;

        $host_types_data->search_key = 'host_type';

        $host_types_data->should_display_the_filter = YES;

        $host_types = self::get_host_types();

        $host_types_data->data = $host_types;

        return $host_types_data;

    }

    public static function filter_options_pricings($request) {

        $pricings_data = new \stdClass;

        $pricings_data->title = tr('SEARCH_OPTION_PRICE');

        $pricings_data->description = "";

        $pricings_data->search_type = SEARCH_OPTION_PRICE;

        $pricings_data->type = RANGE;

        $pricings_data->search_key = 'price';

        $pricings_data->start_key = "0.00";

        $pricings_data->end_key = "100000000.00";

        $pricings_data->should_display_the_filter = YES;

        $price_data['min_price'] = "0.00";

        $price_data['max_price'] = "100000000.00";

        $pricings_data->data = $price_data;

        return $pricings_data;

    }
    

    /**
     * @method formatted_price_type()
     *
     * @uses 
     *
     * @created Bhawya
     *
     * @updated Bhawya
     *
     * @param date $booking_details
     *
     * @return string $amount
     */

    public static function formatted_price_type($price_type) {

        if($price_type  == PRICE_TYPE_DAY ) {

            $price_type_text = tr('per_day');

        } else if($price_type  == PRICE_TYPE_MONTH ) {

            $price_type_text = tr('per_month');

        } else {

            $price_type_text = tr('per_hour');

        }

        return $price_type_text;

    }

    /**
     * @method amenties_data()
     *
     * @uses Used to get Amenities data
     *
     * @created Bhawya
     * 
     * @updated Bhawya
     *
     * @param datetime $host_details
     *
     * @return boolean
     */
    
    public static function amenties_data($host_details) {
        
        $lookups_id = explode(',', $host_details->amenities);
        
        $amenities = Lookups::Approved()
                        ->where('is_amenity', YES)
                        ->whereIn('id',$lookups_id)
                        ->pluck('value')->toArray();

        $amenities = implode_values($amenities);
        
        return $amenities ?? '';
    }
}
