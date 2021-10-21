<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Helpers\Helper, App\Helpers\EnvEditorHelper, App\Helpers\HostHelper;

use App\Repositories\HostRepository as HostRepo;

use App\Repositories\BookingRepository as BookingRepo;

use App\Repositories\PushNotificationRepository as PushRepo;

use App\Notifications\PushNotification;

use DB, Hash, Setting, Auth, Validator, Exception, Enveditor, File;

use App\Admin;

use App\Booking, App\BookingChat, App\BookingPayment;

use App\BookingProviderReview, App\BookingUserReview;

use App\ServiceLocation;

use App\CommonQuestion, App\CommonQuestionAnswer, App\CommonQuestionGroup;

use App\Host, App\HostDetails, App\HostAvailability, App\HostGallery, App\HostInventory, App\HostAvailabilityList;

use App\Provider, App\ProviderDetails, App\ProviderDocument;

use App\Settings, App\StaticPage, App\Lookups, App\Document ;

use App\User, App\UserCard, App\Wishlist, App\UserVehicle;

use App\ProviderSubscription, App\ProviderSubscriptionPayment;

use Carbon\Carbon;

use App\Jobs\BellNotificationJob, App\Jobs\SendEmailJob, App\Jobs\SpaceDeclineJob, App\Jobs\SpaceApproveJob, App\Jobs\ProvidersDocumentVerifyJob, App\Jobs\ProviderStatusJob, App\Jobs\ProviderRedeemJob, App\Jobs\UserRefundJob, App\Jobs\AdminBookingCancelJob;

use App\ProviderBillingInfo,App\UserBillingInfo;

use App\ProviderRedeem, App\UserRefund;

use App\Repositories\AccountRepository as AccRepo;

class AdminController extends Controller {
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    protected $loginAdmin, $timezone, $skip, $take , $paginate_count;

    public function __construct(Request $request) {
                
        $this->middleware('auth:admin');
       
        $this->loginAdmin = Auth::guard('admin')->user()->timezone ?? "Asia/Kolkata";

        $this->timezone = $this->loginAdmin->timezone ?? "America/New_York";
       
        $this->skip = $request->skip ?: 0;
       
        $this->take = $request->take ?: (Setting::get('admin_take_count') ?: TAKE_COUNT);

        $this->paginate_count = Setting::get('admin_take_count', 10);

    }

    /**
     * @method dashboard_index()
     *
     * @uses Show the application dashboard.
     *
     * @created vithya
     *
     * @updated vithya
     *
     * @param 
     * 
     * @return return view page
     *
     */
    public function index() {

        $date = date_default_timezone_set($this->timezone); 

        $dashboard_data = [];

        $dashboard_data['total_users'] = User::IsNotDeleted()->count();

        $dashboard_data['total_providers'] = Provider::IsNotDeleted()->count();

        $dashboard_data['total_hosts'] = Host::count();

        $dashboard_data['total_verified_hosts'] = Host::where('is_admin_verified', ADMIN_SPACE_VERIFIED)->count();

        $dashboard_data['total_unverified_hosts'] = Host::whereIn('is_admin_verified', [ADMIN_SPACE_VERIFY_PENDING, ADMIN_SPACE_VERIFY_DECLINED])->count();

        $dashboard_data['total_bookings'] = Booking::count();

        $dashboard_data['total_revenue'] = BookingPayment::where('status', PAID)->sum('booking_payments.total');
        
        $dashboard_data['today_revenue'] = BookingPayment::whereDate('booking_payments.updated_at', today())->where('status', PAID)->sum('booking_payments.paid_amount');

        // Recent datas

        $recent_users= User::IsNotDeleted()->orderBy('id' , 'desc')->skip($this->skip)->take(TAKE_COUNT)->get();

        $recent_providers= Provider::IsNotDeleted()->orderBy('id' , 'desc')->skip($this->skip)->take(TAKE_COUNT)->get(); 

        $recent_bookings = Booking::orderBy('id' , 'desc')->skip($this->skip)->take(TAKE_COUNT)->get();

        $data = json_decode(json_encode($dashboard_data));

        // last x days page visiters count for graph
        $views = last_x_days_page_view(10);

        // hosts analytics
        $hosts_count = get_hosts_count();

        return view('admin.dashboard')
                    ->with('page' , 'dashboard')
                    ->with('sub_page' , 'dashboard')
                    ->with('data', $data)
                    ->with('recent_users', $recent_users)
                    ->with('recent_providers', $recent_providers)
                    ->with('recent_bookings', $recent_bookings)
                    ->with('views', $views)
                    ->with('hosts_count', $hosts_count);
    }

    /**
     * @method users_index()
     *
     * @uses To list out users details 
     *
     * @created Anjana
     *
     * @updated vithya
     *
     * @param 
     * 
     * @return return view page
     *
     */
    public function users_index(Request $request) {

        $base_query = User::IsNotDeleted()->orderBy('updated_at','desc');

        if($request->search_key) {

            $base_query = $base_query
                    ->where('name','LIKE','%'.$request->search_key.'%')
                    ->orWhere('email','LIKE','%'.$request->search_key.'%')
                    ->orWhere('mobile','LIKE','%'.$request->search_key.'%');
        }
        
        $users = $base_query->paginate($this->paginate_count);

        return view('admin.users.index')
                    ->with('page','users')
                    ->with('sub_page' , 'users-view')
                    ->with('users' , $users);
    }

    /**
     * @method users_create()
     *
     * @uses To create user details
     *
     * @created  Anjana
     *
     * @updated vithya
     *
     * @param 
     * 
     * @return return view page
     *
     */
    public function users_create() {

        $user_details = new User;

        $user_billing_info = new UserBillingInfo;

        return view('admin.users.create')
                    ->with('page' , 'users')
                    ->with('sub_page','users-create')
                    ->with('user_details', $user_details)
                    ->with('user_billing_info', $user_billing_info);           
    }

    /**
     * @method users_edit()
     *
     * @uses To display and update user details based on the user id
     *
     * @created Anjana
     *
     * @updated Anjana
     *
     * @param object $request - User Id
     * 
     * @return redirect view page 
     *
     */
    public function users_edit(Request $request) {

        try {

            $user_details = User::find($request->user_id);

            if(!$user_details) { 

                throw new Exception(tr('user_not_found'), 101);
            }

            $user_billing_info = UserBillingInfo::where('user_id', $request->user_id)->first();

            return view('admin.users.edit')
                    ->with('page' , 'users')
                    ->with('sub_page','users-view')
                    ->with('user_details' , $user_details)
                    ->with('user_billing_info' , $user_billing_info); 
            
        } catch(Exception $e) {

            return redirect()->route('admin.users.index')->with('flash_error', $e->getMessage());
        }
    
    }

    /**
     * @method users_save()
     *
     * @uses To save the users details of new/existing user object based on details
     *
     * @created Anjana
     *
     * @updated vithya
     *
     * @param object request - User Form Data
     *
     * @return success message
     *
     */
    public function users_save(Request $request) {

        try {

            DB::begintransaction();

            $rules = [
                'name' => 'required|max:191',
                'email' => $request->user_id ? 'required|email|max:191|unique:users,email,'.$request->user_id.',id' : 'required|email|max:191|unique:users,email,NULL,id',
                'password' => $request->user_id ? "" : 'required|min:6',
                'mobile' =>  !$request->user_id ? 'required|digits_between:6,13|unique:users,mobile' : 'required|digits_between:6,13',
                'picture' => 'mimes:jpg,png,jpeg',
                'description' => 'max:191',
                'user_id' => 'exists:users,id'
            ];


            $custom_messages = [
                'mobile.digits_between' => tr('mobile_number_validate'),
                'mobile.unique' => tr('mobile_unique_validate')
            ];

            Helper::custom_validator($request->all(),$rules,$custom_messages);

            if (strlen($request->mobile) == substr_count($request->mobile, 0)) {
                return redirect()->back()->withInput()->with('flash_error', tr('mobile_validate_0'));
            }

            $user_details = $request->user_id ? User::find($request->user_id) : new User;

            $is_new_user = NO;

            if($user_details->id) {

                $message = tr('user_updated_success'); 

            } else {

                $is_new_user = YES;

                $user_details->password = ($request->password) ? \Hash::make($request->password) : null;

                $message = tr('user_created_success');

                $user_details->email_verified_at = date('Y-m-d H:i:s');

                $user_details->picture = asset('placeholder.jpg');

                $user_details->is_verified = USER_EMAIL_VERIFIED;

            }

            $user_details->name = $request->name ?: $user_details->name;

            $user_details->email = $request->email ?: $user_details->email;

            $user_details->mobile = $request->mobile ?: '';

            $user_details->description = $request->description ?: '';

            $user_details->login_by = $request->login_by ?: 'manual';

            // Upload picture
            
            if($request->hasFile('picture')) {

                if($request->user_id) {

                    Helper::delete_file($user_details->picture, COMMON_FILE_PATH); 
                    // Delete the old pic
                }

                $user_details->picture = Helper::upload_file($request->file('picture'), COMMON_FILE_PATH);
            }

            if($user_details->save()) {

                if($is_new_user == YES) {

                    /**
                     * @todo Welcome mail notification
                     */

                    $user_details->is_verified = USER_EMAIL_VERIFIED;

                    $user_details->save();

                }

                // Save the values only if any details based on billing infos
                if($request->account_name || $request->paypal_email || $request->account_no || $request->route_no) {

                    $user_billing_info = $request->billing_info_id ? UserBillingInfo::find($request->billing_info_id) : new UserBillingInfo;

                    $user_billing_info->user_id = $user_details->id;

                    $user_billing_info->account_name = $request->account_name ?? "";

                    $user_billing_info->paypal_email = $request->paypal_email ?? "";

                    $user_billing_info->account_no = $request->account_no ?? "";

                    $user_billing_info->route_no = $request->route_no ?? "";

                    $user_billing_info->save();
                }
                    
                DB::commit(); 

                return redirect(route('admin.users.view', ['user_id' => $user_details->id]))->with('flash_success', $message);

            } 

            throw new Exception(tr('user_save_failed'));
            
        } catch(Exception $e){ 

            DB::rollback();

            return redirect()->back()->withInput()->with('flash_error', $e->getMessage());

        } 

    }

    /**
     * @method users_view()
     *
     * @uses view the users details based on users id
     *
     * @created Anjana 
     *
     * @updated vithya
     *
     * @param object $request - User Id
     * 
     * @return View page
     *
     */
    public function users_view(Request $request) {
       
        try {
      
            $user_details = User::IsNotDeleted()->find($request->user_id);

            if(!$user_details) { 

                throw new Exception(tr('user_not_found'), 101);                
            }            

            $vehicles = UserVehicle::where('user_id', $request->user_id)->get() ?? [];
            
            $user_billing_info = UserBillingInfo::where('user_id',$request->user_id)->first() ?? new UserBillingInfo;         

            return view('admin.users.view')
                        ->with('page', 'users') 
                        ->with('sub_page','users-view') 
                        ->with('user_details' , $user_details)
                        ->with('vehicles' , $vehicles)
                        ->with('user_billing_info' , $user_billing_info);
            
        } catch (Exception $e) {

            return redirect()->back()->with('flash_error', $e->getMessage());
        }
    
    }

    /**
     * @method users_delete()
     *
     * @uses delete the user details based on user id
     *
     * @created Anjana
     *
     * @updated  
     *
     * @param object $request - User Id
     * 
     * @return response of success/failure details with view page
     *
     */
    public function users_delete(Request $request) {

        try {

            DB::begintransaction();

            $user_details = User::IsNotDeleted()->find($request->user_id);
            
            if(!$user_details) {

                throw new Exception(tr('user_not_found'), 101);                
            }

            $delete_response = AccRepo::user_delete_response($user_details);

            if($delete_response['success'] == false) {

                throw new Exception($delete_response['error'], $delete_response['error_code']);
                
            }

            DB::commit();

            return redirect()->route('admin.users.index')->with('flash_success',tr('user_deleted_success'));
            
            
        } catch(Exception $e){

            DB::rollback();

            return redirect()->back()->with('flash_error', $e->getMessage());

        }       
         
    }

    /**
     * @method users_status
     *
     * @uses To update user status as DECLINED/APPROVED based on users id
     *
     * @created Anjana
     *
     * @updated 
     *
     * @param object $request - User Id
     * 
     * @return response success/failure message
     *
     **/
    public function users_status(Request $request) {

        try {

            DB::beginTransaction();

            $user_details = User::IsNotDeleted()->find($request->user_id);

            if(!$user_details) {

                throw new Exception(tr('user_not_found'), 101);
                
            }

            $user_details->status = $user_details->status ? DECLINED : APPROVED ;

            if($user_details->save()) {

                DB::commit();

                $message = $user_details->status ? tr('user_approve_success') : tr('user_decline_success');

                return redirect()->back()->with('flash_success', $message);
            }
            
            throw new Exception(tr('user_status_change_failed'));

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->route('admin.users.index')->with('flash_error', $e->getMessage());

        }

    }

    /**
     * @method users_verify_status()
     *
     * @uses verify the user
     *
     * @created Anjana
     *
     * @updated
     *
     * @param object $request - User Id
     *
     * @return redirect back page with status of the user verification
     */
    public function users_verify_status(Request $request) {

        try {

            DB::beginTransaction();

            $user_details = User::IsNotDeleted()->find($request->user_id);

            if(!$user_details) {

                throw new Exception(tr('user_details_not_found'), 101);
                
            }

            $user_details->is_verified = $user_details->is_verified ? USER_EMAIL_NOT_VERIFIED : USER_EMAIL_VERIFIED;

            if($user_details->save()) {

                DB::commit();

                $message = $user_details->is_verified ? tr('user_verify_success') : tr('user_unverify_success');

                return redirect()->route('admin.users.index')->with('flash_success', $message);
            }
            
            throw new Exception(tr('user_verify_change_failed'));

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->route('admin.users.index')->with('flash_error', $e->getMessage());

        }
    
    }

    
    /**
     * @method wishlists_index()
     *
     * @uses To list out users wishlist details 
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param 
     * 
     * @return return view page
     *
     */
    public function wishlists_index(Request $request) {

        try {
        
            $user_details = User::IsNotDeleted()->find($request->user_id);

            if(!$user_details) {

                throw new Exception(tr('user_details_not_found'), 101);
            }

            $wishlists = Wishlist::where('user_id',$request->user_id)->orderBy('updated_at','desc')->paginate($this->paginate_count);


            return view('admin.users.wishlists')
                        ->with('page','users')
                        ->with('sub_page' , 'users-view')
                        ->with('user_details' , $user_details)
                        ->with('wishlists' , $wishlists);

        } catch (Exception $e) {

            return redirect()->back()->with('flash_error', $e->getMessage());
        }
    }

    /**
     * @method wishlists_delete()
     *
     * @uses delete the wishlist details based on wishlist id
     *
     * @created Anjana
     *
     * @updated  
     *
     * @param object $request - wishlist Id
     * 
     * @return response of success/failure details with view page
     *
     */
    public function wishlists_delete(Request $request) {

        try {

            DB::begintransaction();

            $wishlist_details = Wishlist::find($request->wishlist_id);
            
            if(!$wishlist_details) {

                throw new Exception(tr('wishlist_not_found'), 101);
            }

            if($wishlist_details->delete()) {

                DB::commit();

                return redirect()->back()->with('flash_success',tr('wishlist_deleted_success'));   
            } 
            
            throw new Exception(tr('wishlist_delete_failed'));
            
        } catch(Exception $e){

            DB::rollback();

            return redirect()->back()->with('flash_error', $e->getMessage());

        }       
         
    }

    /**
     * @method users_bulk_action()
     * 
     * @uses To delete,approve,decline multiple users
     *
     * @created Arun
     *
     * @updated 
     *
     * @param 
     *
     * @return success/failure message
     */
    public function users_bulk_action(Request $request) {

        try {

            $action_name = $request->action_name ;

            $user_ids = explode(',', $request->selected_users);

            if (!$user_ids && !$action_name) {

                throw new Exception(tr('users_action_is_empty'));

            }

            DB::beginTransaction();

            if($action_name == 'bulk_delete'){

                $users =  User::whereIn('id', $user_ids)->get();

                foreach ($users as $user) {
                    
                    $delete_response = AccRepo::user_delete_response($user);

                    if($delete_response['success'] == false) {

                        throw new Exception($delete_response['error'], $delete_response['error_code']);
                        
                    }
                }

                DB::commit();

                return redirect()->back()->with('flash_success',tr('admin_users_delete_success'));

            }elseif($action_name == 'bulk_approve'){

                $user =  User::whereIn('id', $user_ids)->update(['status' => APPROVED]);

                if ($user) {

                    DB::commit();

                    return back()->with('flash_success',tr('admin_users_approve_success'))->with('bulk_action','true');

                }

                throw new Exception(tr('users_approve_failed'));

            }elseif($action_name == 'bulk_decline'){
                
                $user =  User::whereIn('id', $user_ids)->update(['status' => DECLINED]);

                if ($user) {

                    DB::commit();

                    return back()->with('flash_success',tr('admin_users_decline_success'))->with('bulk_action','true');
                }

                throw new Exception(tr('users_decline_failed'));
            }

        } catch( Exception $e) {

            DB::rollback();

            return redirect()->back()->with('flash_error',$e->getMessage());
        }
    }

    /**
     * @method providers_index
     *
     * @uses Get the providers list
     *
     * @created Anjana
     *
     * @updated Vidhya
     *
     * @param 
     * 
     * @return view page
     *
     */
    public function providers_index(Request $request) {

        $base_query = Provider::IsNotDeleted()->orderBy('updated_at','desc');

        if($request->search_key) {

            $base_query = $base_query
                    ->where('name','LIKE','%'.$request->search_key.'%')
                    ->orWhere('email','LIKE','%'.$request->search_key.'%')
                    ->orWhere('mobile','LIKE','%'.$request->search_key.'%');
        }

        $providers = $base_query->paginate($this->paginate_count);

        return view('admin.providers.index')
                    ->with('page' , 'providers')
                    ->with('sub_page','providers-view')
                    ->with('providers' , $providers);

    }

    /**
     * @method providers_create
     *
     * @uses To create providers details
     *
     * @created Anjana
     *
     * @updated  
     *
     * @param 
     * 
     * @return view page
     *
     */
    public function  providers_create() {

        $provider_details = new Provider;

        $provider_billing_info = new ProviderBillingInfo;

        return view('admin.providers.create')
                    ->with('page' , 'providers')
                    ->with('sub_page','providers-create')
                    ->with('provider_details', $provider_details)
                    ->with('provider_billing_info', $provider_billing_info);
    
    }

    /**
     * @method providers_edit()
     *
     * @uses To display and update provider details based on the provider id
     *
     * @created Anjana
     *
     * @updated Anjana 
     *
     * @param object $request - provider Id
     * 
     * @return redirect view page 
     *
     */    
    public function providers_edit(Request $request) {

        try {
      
            $provider_details = Provider::IsNotDeleted()->find($request->provider_id);

            if(!$provider_details) {

                throw new Exception(tr('provider_not_found'), 101);
                
            }

            $provider_billing_info = ProviderBillingInfo::where('provider_id', $request->provider_id)->first();
           
            return view('admin.providers.edit')
                        ->with('page', 'providers')
                        ->with('sub_page', 'providers-view')
                        ->with('provider_details', $provider_details)
                        ->with('provider_billing_info', $provider_billing_info ?? '');
            
        } catch (Exception $e) {

            return redirect()->back()->with('flash_error', $e->getMessage());
        }
    
    }

    /**
     * @method providers_save
     *
     * @uses To save the providers details of new/existing provider object based on details
     *
     * @created Anjana
     *
     * @updated Naveen
     *
     * @param object $request - providers object details
     * 
     * @return response of success/failure response details
     *
     */
    public function providers_save(Request $request) {

        try {
            
            DB::begintransaction();
            
            $rules = [
                'name' => 'required|max:191',
                'email' => $request->provider_id ? 'required|email|max:191|unique:providers,email,'.$request->provider_id.',id' : 'required|email|max:191|unique:providers,email,NULL,id',
                'password' => $request->provider_id ? "" : 'required|min:6',
                'mobile' => 'required|digits_between:6,13',
                'picture' => 'mimes:jpg,png,jpeg',
                'description' => 'max:191'
                ];

            Helper::custom_validator($request->all(),$rules);

            $providers_details = $request->provider_id ? Provider::find($request->provider_id) : new Provider;

            $new_user = NO;

            if($providers_details->id) {

                $message = tr('provider_updated_success'); 

            } else {

                $new_user = YES;

                $message = tr('provider_created_success');

                $providers_details->password = ($request->password) ? \Hash::make($request->password) : null;

                $providers_details->email_verified_at = date('Y-m-d H:i:s');

                $providers_details->picture = asset('placeholder.jpg');

            }

            $providers_details->name = $request->has('name') ? $request->name: $providers_details->name;

            $providers_details->email = $request->has('email') ? $request->email: $providers_details->email;
            
            $providers_details->mobile =  $request->mobile ?? "";

            $providers_details->description = $request->description ?? "";
            
            // Upload picture
            if($request->hasFile('picture')) {

                if($request->provider_id) {

                    Helper::delete_file($providers_details->picture, PROFILE_PATH_PROVIDER); 
                    // Delete the old pic
                }

                $providers_details->picture = Helper::upload_file($request->file('picture'), PROFILE_PATH_PROVIDER);

            }

            if( $providers_details->save() ) {
                
                // Save the values only if any details based on billing infos
                if($request->account_name || $request->paypal_email || $request->account_no || $request->route_no) {

                    $provider_billing_info = $request->billing_info_id ? ProviderBillingInfo::find($request->billing_info_id) : new ProviderBillingInfo;

                    $provider_billing_info->provider_id = $providers_details->id;

                    $provider_billing_info->account_name = $request->account_name ?? "";

                    $provider_billing_info->paypal_email = $request->paypal_email ?? "";

                    $provider_billing_info->account_no = $request->account_no ?? "";

                    $provider_billing_info->route_no = $request->route_no ?? "";

                    $provider_billing_info->save();
                }

                DB::commit(); 

                return redirect()->route('admin.providers.view', ['provider_id' => $providers_details->id])->with('flash_success', $message);

            } 

            throw new Exception(tr('provider_save_failed'), 101);
            
        } catch(Exception $e){ 

            DB::rollback();

            return redirect()->back()->withInput()->with('flash_error', $e->getMessage());

        }   
        
    }

    /**
     * @method providers_view
     *
     * @uses view the selected provider details 
     *
     * @created Anjana
     *
     * @updated
     *
     * @param Integer $request - provider id
     * 
     * @return view page
     *
     **/
    public function providers_view(Request $request) {

        $provider_details = Provider::IsNotDeleted()->find($request->provider_id);

        if(!$provider_details) {

            return redirect()->route('admin.providers.index')->with('flash_error',tr('provider_not_found'));
        }

        $provider_billing_info = ProviderBillingInfo::where('provider_id',$request->provider_id)->first() ?? new ProviderBillingInfo;

        return view('admin.providers.view')
                    ->with('page', 'providers')
                    ->with('sub_page','providers-view')
                    ->with('provider_details' , $provider_details)
                    ->with('provider_billing_info' , $provider_billing_info);
    
    }

    /**
     * @method providers_revenues
     *
     * @uses view the selected provider details 
     *
     * @created Anjana
     *
     * @updated Naveen
     *
     * @param Integer $request - provider id
     * 
     * @return view page
     *
     **/
    public function providers_revenues(Request $request) {

        $provider_details = Provider::find($request->provider_id);

        if(!$provider_details) {

            return redirect()->route('admin.providers.index')->with('flash_error',tr('provider_not_found'));
        }

        $provider_details->total_provider_amount = BookingPayment::where('provider_id',$request->provider_id)->where('status', PAID)->sum('provider_amount');

        $provider_details->month_provider_amount = BookingPayment::where('provider_id',$request->provider_id)->whereMonth('booking_payments.updated_at','=',date('m'))->where('status', PAID)->sum('booking_payments.paid_amount');

        $provider_details->today_provider_amount = BookingPayment::where('provider_id',$request->provider_id)->whereDate('booking_payments.updated_at',today())->where('status', PAID)->sum('booking_payments.paid_amount');

        $hosts = Host::where('provider_id',$request->provider_id)->paginate($this->paginate_count);

        foreach($hosts as $key => $host_details) {

            $host_details->total_earnings = BookingPayment::where('host_id', $host_details->id)->sum('total');

            $host_details->admin_earnings = BookingPayment::where('host_id', $host_details->id)->sum('admin_amount');

            $host_details->provider_earnings = BookingPayment::where('host_id', $host_details->id)->sum('provider_amount');

        }



        return view('admin.providers.revenues')
                    ->with('page', 'providers')
                    ->with('sub_page','providers-view')
                    ->with('provider_details' , $provider_details)
                    ->with('hosts' , $hosts);
    
    }

    /**
     * @method providers_delete
     *
     * @uses To delete the providers details based on selected provider id
     *
     * @created Anjana
     *
     * @updated 
     *
     * @param Integer $request - provider id
     * 
     * @return response of success/failure details
     *
     **/
    public function providers_delete(Request $request) {

        try {

            DB::beginTransaction();

            $provider_details = provider::IsNotDeleted()->find($request->provider_id);

            if(!$provider_details) {

                throw new Exception(tr('provider_not_found'), 101);     
            }

            $delete_response = AccRepo::provider_delete_response($provider_details);

            if($delete_response['success'] == false) {

                throw new Exception($delete_response['error'], $delete_response['error_code']);
                
            }

            DB::commit();

            return redirect()->route('admin.providers.index',['page'=>$request->page,'search_key'=>$request->search_key])->with('flash_success',tr('provider_delete_success')); 

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->route('admin.providers.index')->with('flash_error', $e->getMessage());

        }
   
    }

    /**
     * @method providers_status
     *
     * @uses To update provider status as DECLINED/APPROVED based on provide id
     *
     * @created Anjana
     *
     * @updated 
     *
     * @param Integer $request - provider id
     * 
     * @return response success/failure message
     *
     **/
    public function providers_status(Request $request) {

        try {

            DB::beginTransaction();

            $provider_details = Provider::IsNotDeleted()->find($request->provider_id);

            if(!$provider_details) {

                throw new Exception(tr('provider_not_found'), 101);
                
            }

            $provider_details->status = $provider_details->status ? DECLINED : APPROVED;

            if( $provider_details->save()) {

                DB::commit();

                // Email & Push Notification for Approve/decline

                $job_data['provider_details'] = $provider_details;

                $job_data['email_verification'] = NO;

                $job_data['timezone'] = $this->timezone;

                $this->dispatch(new ProviderStatusJob($job_data));

                $message = $provider_details->status ? tr('provider_approve_success') : tr('provider_decline_success');

                return redirect()->back()->with('flash_success', $message);
            }

            throw new Exception(tr('provider_status_change_failed'), 101);

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->route('admin.providers.index')->with('flash_error', $e->getMessage());

        }

    }

    /**
     * @method providers_verify_status()
     *
     * @uses verify for the Provider
     *
     * @created Anjana
     *
     * @updated
     *
     * @param object $request - Provider Id
     *
     * @return redirect back page with status of the Provider verification
     */
    public function providers_verify_status(Request $request) {

        try {

            DB::beginTransaction();

            $provider_details = Provider::IsNotDeleted()->find($request->provider_id);

            if(!$provider_details) {

                throw new Exception(tr('provider_not_found'), 101);
                
            }

            $provider_details->is_verified = $provider_details->is_verified ? PROVIDER_EMAIL_NOT_VERIFIED : PROVIDER_EMAIL_VERIFIED;

            $provider_details->save();

            DB::commit();

            // Email & Push Notification for Provider

            $job_data['provider_details'] = $provider_details;

            $job_data['email_verification'] = YES;

            $this->dispatch(new ProviderStatusJob($job_data));

            $message = $provider_details->is_verified ? tr('provider_verify_success') : tr('provider_unverify_success');

            return redirect()->route('admin.providers.index')->with('flash_success', $message);

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->back()->with('flash_error', $e->getMessage());

        }
    
    }

    /**
     * @method providers_bulk_action()
     * 
     * @uses To delete,approve,decline multiple providers
     *
     * @created Arun
     *
     * @updated 
     *
     * @param 
     *
     * @return success/failure message
     */
    public function providers_bulk_action(Request $request) {

        try {

            $action_name = $request->action_name ;

            $provider_ids = explode(',', $request->selected_providers);

            DB::beginTransaction();

            if (!$provider_ids && !$action_name) {

                throw new Exception(tr('providers_action_is_empty'));

            }

            if($action_name == 'bulk_delete'){

                $providers =  Provider::whereIn('id', $provider_ids)->get();

                foreach ($providers as $provider) {
                    
                    $delete_response = AccRepo::provider_delete_response($provider);

                    if($delete_response['success'] == false) {

                        throw new Exception($delete_response['error'], $delete_response['error_code']);
                        
                    }
                }

                DB::commit();

                return back()->with('flash_success',tr('admin_providers_delete_success'))->with('bulk_action','true');

            }elseif($action_name == 'bulk_approve'){

                $provider =  Provider::whereIn('id', $provider_ids)->update(['status' => APPROVED]);

                if ($provider) {

                    DB::commit();

                    return back()->with('flash_success',tr('admin_providers_approve_success'))->with('bulk_action','true');

                }

                throw new Exception(tr('providers_approve_failed'));

            }elseif($action_name == 'bulk_decline'){
                
                $provider =  Provider::whereIn('id', $provider_ids)->update(['status' => DECLINED]);

                if ($provider) {

                    DB::commit();

                    return back()->with('flash_success',tr('admin_providers_decline_success'))->with('bulk_action','true');

                }

                throw new Exception(tr('providers_decline_failed'));

            }

        } catch( Exception $e) {

            DB::rollback();

            return redirect()->back()->with('flash_error',$e->getMessage());
        }

    }

    /**
     * @method service_locations_index()
     *
     * @uses To list out service locations details.
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param -
     *
     * @return view page
     */    

    public function service_locations_index(Request $request) {

        $base_query = ServiceLocation::orderBy('created_at','desc');

        if($request->search_key) {

            $base_query = $base_query
                    ->where('name','LIKE','%'.$request->search_key.'%')
                    ->orWhere('address','LIKE','%'.$request->search_key.'%');
        }

        $service_locations = $base_query->paginate($this->paginate_count);

        return view('admin.service_locations.index')
                    ->with('page','service-locations')
                    ->with('sub_page','service-locations-view')
                    ->with('service_locations' , $service_locations);
    }

    /**
     * @method service_locations_create()
     *
     * @uses To create service location object
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param 
     * 
     * @return view page
     *
     */

    public function service_locations_create() {
        
        $service_location_details = new ServiceLocation;
       
        return view('admin.service_locations.create')
                    ->with('page' , 'service-locations')
                    ->with('sub_page','service-locations-create')
                    ->with('service_location_details', $service_location_details);
    }

    /**
     * @method service_locations_edit
     *
     * @uses To update service location based on id
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param integer (request) $service_location_id
     * 
     * @return view page
     *
     */    
    public function service_locations_edit(Request $request) {

        try {

            $service_location_details = ServiceLocation::find($request->service_location_id);

            if(!$service_location_details) {

                throw new Exception(tr('service_location_not_found'), 101);
                
            }

            return view('admin.service_locations.edit')
                        ->with('page','service_locations')
                        ->with('sub_page','service_locations-view')
                        ->with('service_location_details',$service_location_details);

       } catch (Exception $e) {

            return redirect()->route('admin.service_locations.index')->with('flash_error', $e->getMessage());
       }
    
    }


    /**
     * @method service_locations_save()
     *
     * @uses To save/update the new/existing service locations object details
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param integer (request) $service_location_id, service_location (request) details
     * 
     * @return success/failure message
     *
     */
    
    public function service_locations_save(Request $request) {
       
        try {

            DB::beginTransaction();

            $rules = [
                'name' => 'required|max:191',
                'address' => 'required',
                'cover_radius' => 'required|numeric|min:1',
                'description' => 'max:191',
                'picture' => 'mimes:jpg,png,jpeg',
                'latitude' => 'required',
                'longitude' => 'required'
            ];
                
            Helper::custom_validator($request->all(),$rules);

            $service_location_details = new ServiceLocation;

            $message = tr('service_location_created_success');

            if( $request->service_location_id != '') {

                $service_location_details = ServiceLocation::find($request->service_location_id);

                $message = tr('service_location_updated_success');

            } else {
               
                $service_location_details->status = APPROVED;

                $service_location_details->unique_id = uniqid();
            }

            $service_location_details->name = $request->name;

            $service_location_details->description = $request->description ?? '';

            $service_location_details->address = $request->address;

            $service_location_details->cover_radius = $request->cover_radius;

            $service_location_details->latitude = $request->latitude;

            $service_location_details->longitude = $request->longitude;

            // Upload picture

            if($request->hasFile('picture') ) {

                if($request->service_location_id) {

                    Helper::delete_file($service_location_details->picture, FILE_PATH_SERVICE_LOCATION); 
                    // Delete the old pic
                }

                $service_location_details->picture = Helper::upload_file($request->file('picture'), FILE_PATH_SERVICE_LOCATION);
            }

            if( $service_location_details->save()) {
                
                DB::commit();

                return redirect()->route('admin.service_locations.view',['service_location_id'=>$service_location_details->id])->with('flash_success',$message);
            }
            
        } catch (Exception $e) {
            
            DB::rollback();

            return redirect()->back()->withInput()->with('flash_error', $e->getMessage());
        }

    }

    /**
     * @method service_locations_view()
     *
     * @uses display service_location details based on service location id
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param integer (request) $service_location_id
     * 
     * @return view page
     *
     */
    public function service_locations_view(Request $request) {

        $service_location_details = ServiceLocation::find($request->service_location_id);

        if(!$service_location_details) {

            return redirect()->route('admin.service_locations.index')->with('flash_error',tr('service_location_not_found'));  
            
        }

        return view('admin.service_locations.view')
                    ->with('page', 'service_locations')
                    ->with('sub_page','service-locations-view')
                    ->with('service_location_details' , $service_location_details);
    
    }

    /**
     * @method service_locations_delete
     *
     * @uses To delete the service locations details based on service location id
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param integer (request) $service_location_id
     * 
     * @return success/failure message
     *
     */
    public function service_locations_delete(Request $request) {

        try {

            DB::beginTransaction();

            $service_location_details = ServiceLocation::find($request->service_location_id);

            if(!$service_location_details) {

                throw new Exception(tr('service_location_not_found'), 101);                
            }

            if($service_location_details->delete() ) {

                DB::commit();

                // Delete relavant image

                if($service_location_details->picture !='' ) {

                        Helper::delete_file($service_location_details->picture, FILE_PATH_SERVICE_LOCATION); 
                }

                return redirect()->route('admin.service_locations.index')->with('flash_success',tr('service_location_deleted_success')); 

            }

            throw new Exception(tr('service_location_delete_error'));
            
        } catch(Exception $e) {

            DB::rollback();

            return redirect()->route('admin.service_locations.index')->with('flash_error', $e->getMessage());

        }
   
    }

    /**
     * @method service_locations_status
     *
     * @uses To update service_location status as DECLINED/APPROVED based on service_location id
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param integer (request) $service_location_id
     * 
     * @return success/failure message
     *
     */
    public function service_locations_status(Request $request) {

        try {

            DB::beginTransaction();

            $service_location_details = ServiceLocation::find($request->service_location_id);

            if(!$service_location_details) {

                throw new Exception(tr('service_location_not_found'), 101);                
            }

            $service_location_details->status = $service_location_details->status ? DECLINED : APPROVED;

            if($service_location_details->save()) {

                DB::commit();

                $message = $service_location_details->status ? tr('service_location_approve_success') : tr('service_location_decline_success');

                return redirect()->back()->with('flash_success', $message);
            }

            throw new Exception(tr('service_location_status_change_failed'));

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->route('admin.service_locations.index')->with('flash_error', $e->getMessage());

        }

    }

    /**
     * @method service_locations_bulk_action()
     * 
     * @uses To delete,approve,decline multiple service_locations
     *
     * @created Arun
     *
     * @updated 
     *
     * @param 
     *
     * @return success/failure message
     */
    public function service_locations_bulk_action(Request $request) {

        try {

            $action_name = $request->action_name ;

            $service_location_ids = explode(',', $request->selected_service_locations);

            if (!$service_location_ids && !$action_name) {

                throw new Exception(tr('service_locations_action_is_empty'));

            }

            DB::beginTransaction();

            if($action_name == 'bulk_delete'){

                $service_location =  ServiceLocation::whereIn('id', $service_location_ids)->delete();

                if ($service_location) {

                    DB::commit();

                    return back()->with('flash_success',tr('admin_service_locations_delete_success'))->with('bulk_action','true');

                }

                throw new Exception(tr('service_locations_delete_failed'));

            }elseif($action_name == 'bulk_approve'){

                $service_location =  ServiceLocation::whereIn('id', $service_location_ids)->update(['status' => APPROVED]);

                if ($service_location) {

                    DB::commit();

                    return back()->with('flash_success',tr('admin_service_locations_approve_success'))->with('bulk_action','true');

                }

                throw new Exception(tr('service_locations_approve_failed'));

            }elseif($action_name == 'bulk_decline'){
                
                $service_location =  ServiceLocation::whereIn('id', $service_location_ids)->update(['status' => DECLINED]);

                if ($service_location) {

                    DB::commit();

                    return back()->with('flash_success',tr('admin_service_locations_decline_success'))->with('bulk_action','true');

                }

                throw new Exception(tr('service_locations_decline_failed'));

            }

        } catch( Exception $e) {

            DB::rollback();

            return redirect()->back()->with('flash_error',$e->getMessage());
        }


    }

    /**
     * @method hosts_availability_view()
     *
     * @uses view the hosts availability calendar view
     *
     * @created Bhawya 
     *
     * @updated Bhawya
     *
     * @param object $request - host Id
     * 
     * @return View page
     *
     */
    public function hosts_availability_view(Request $request) {

        try {

            $rules = [
                'id' => 'required|exists:hosts',
            ];

            Helper::custom_validator($request->all(),$rules);


            // Load the host details based on the host id
            $host_detail = Host::find($request->id);

            if(!$host_detail) {

                throw new Exception(tr('host_not_found'), 101);   
            }

            // Load the host availability details based on the host id
            $hosts_availability = HostAvailability::where('host_id', $request->id)->get();

            if(!$hosts_availability) {

                return redirect()->route('admin.hosts.index')->with('flash_error',tr('host_not_found'));  
            }

            return view('admin.hosts.availability')
                            ->with('page', 'hosts')
                            ->with('sub_page','hosts-view')
                            ->with('host_detail' , $host_detail)
                            ->with('hosts_availability' , $hosts_availability);

        } catch(Exception $e) {

            return back()->with('flash_error', $e->getMessage());

        }
    }

   
    /**
     * @method revenues_dashboard()
     *
     * @uses To display revenue
     *
     * @created Anjana
     *
     * @updated vithya
     *
     * @param 
     *
     * @return
     **/
    public function revenues_dashboard() {

        $data['total_provider_subscription_amount'] = ProviderSubscriptionPayment::sum('paid_amount');

        // provider Revenue by bookings 
        $data['total_provider_amount'] = BookingPayment::sum('provider_amount');

        // toatal Revenue by Bookings
        $data['total_amount'] = BookingPayment::sum('paid_amount');
    
        // total admin Revenue  
        $data['total_admin_amount'] = BookingPayment::sum('admin_amount') + $data['total_provider_subscription_amount'];

        $data['total_today_amount'] = BookingPayment::whereDate('updated_at',today())->sum('booking_payments.total');

        $data['currency'] = Setting::get('currency');

        $recent_bookings = BookingPayment::Bookingpaymentdetails()
                            ->addSelect('users.email as user_email','users.picture as user_picture','users.created_at as user_create')
                            ->skip($this->skip)->take($this->take)->get();

        $data['recent_bookings'] = $recent_bookings;

        $data = (object) $data;


        $data->analytics = last_x_days_revenue(10);

        return view('admin.revenues.dashboard')
                ->with('page', 'revenues')
                ->with('sub_page' ,'revenues-dashboard')
                ->with('data', $data);

    }

    /**
     * @method bookings_dashboard()
     *
     * @uses to display bookings analysis
     *
     * @created Anjana
     *
     * @updated Anjana
     *
     * @param 
     * 
     * @return return view page
     *
     */
    public function bookings_dashboard(Request $request) {  
        
        $booking_data['total_bookings'] = Booking::count();

        $booking_data['bookings_completed'] = Booking::where('status', '=' ,BOOKING_COMPLETED)->count();

        $booking_data['bookings_cancelled_by_user'] = Booking::where('status', '=', BOOKING_CANCELLED_BY_USER)->count();

        $booking_data['bookings_cancelled_by_provider'] = Booking::where('status', '=', BOOKING_CANCELLED_BY_PROVIDER)->count();

        // today checkin and checkouts count
        $booking_data['total_bookings_checkin'] = Booking::where('status','=',BOOKING_CHECKIN)->count();

        $booking_data['total_bookings_checkout'] = Booking::where('status','=',BOOKING_CHECKOUT)->count();
        
        $booking_data['status'] = $status = $request->status ?: BOOKING_CHECKIN;  // BOOKING_CHECKIN = 8
        
        $bookings = Booking::orderBy('updated_at','desc')
                ->when($status == BOOKING_COMPLETED, function ($query) use ($status) { // BOOKING_COMPLETED = 6
                    return $query->where('status', $status);
                })
                ->when($status == BOOKING_CANCELLED_BY_USER, function ($query) use ($status) { // BOOKING_CANCELLED_BY_USER = 4
                    return $query->where('status', $status);
                })
                ->when($status == BOOKING_CANCELLED_BY_PROVIDER, function ($query) use ($status) { // BOOKING_CANCELLED_BY_PROVIDER = 5
                    return $query->where('status', $status);
                }) 
                ->when($status == BOOKING_CHECKIN, function ($query) use ($status) {
                    // BOOKING_CHECKIN = 8
                    return $query->where('status', $status);
                })
                ->when($status == BOOKING_CHECKOUT, function ($query) use ($status) {
                    // BOOKING_CHECKIN = 9
                    return $query->where('status', $status);
                })
                ->paginate($this->paginate_count);

        $booking_data['bookings'] = $bookings;

        $data = (object) $booking_data;

        return view('admin.bookings.dashboard')
                ->with('page','bookings')
                ->with('sub_page' , 'bookings-dashboard')   
                ->with('data' , $data);    
    }  

    /**
     * @method bookings_index()
     *
     * @uses To list out bookings details 
     *
     * @created Anjana
     *
     * @updated vithya
     *
     * @param 
     * 
     * @return return view page
     *
     */
    public function bookings_index(Request $request) {

        $base_query = Booking::orderBy('updated_at','desc');

        // to get user based bookings
        if($request->user_id) {

            $module_details = User::find($request->user_id);

            if(!$module_details) {

                return redirect()->back()->with('flash_error',tr('user_not_found'));
            }

            $base_query = $base_query->where('bookings.user_id','=', $request->user_id);

        } 

        // to get provider based bookings
        if($request->provider_id) {
           
            $module_details = Provider::find($request->provider_id);

            if(!$module_details) {

                return redirect()->back()->with('flash_error',tr('provider_not_found'));
            }

            $base_query = $base_query->where('bookings.provider_id','=', $request->provider_id);
        }        

        // to get host based bookings
        if($request->host_id) {
           
            $module_details = Host::find($request->host_id);

            if(!$module_details) {

                return redirect()->back()->with('flash_error',tr('host_not_found'));
            }

            $base_query = $base_query->where('bookings.host_id','=', $request->host_id);
        }

        // Get booking details based on service locations
        if($request->service_location_id) { 

            $host_ids = Host::where('service_location_id',$request->service_location_id)->pluck('id')->toArray();

            $base_query = $base_query->whereIn('bookings.host_id', $host_ids);
        }
        
        // to check and get bookings belongs to below status 
        $booking_status = array(BOOKING_INITIATE, BOOKING_ONPROGRESS, BOOKING_WAITING_FOR_PAYMENT , BOOKING_COMPLETED, BOOKING_CANCELLED_BY_USER, BOOKING_CANCELLED_BY_PROVIDER, BOOKING_REFUND_INITIATED, BOOKING_CHECKIN, BOOKING_CHECKOUT ); 

        if($request->status && in_array($request->status, $booking_status)) {
          
            $base_query = $base_query->where('status', '=', $request->status);
        }       

        /*if($request->status == BOOKING_CHECKIN) {

            $base_query = $base_query->whereDate('checkin',today());
        }        

        if($request->status == BOOKING_CHECKOUT) {

            $base_query = $base_query->whereDate('checkin',today());
        }*/

        if($request->search_key) {

            $search_key = $request->search_key;

            $base_query = $base_query 

                ->whereHas('providerDetails', function($q) use ($search_key) {

                    return $q->Where('providers.name','LIKE','%'.$search_key.'%');

                })->orWhereHas('hostDetails', function($q) use ($search_key) {

                    return $q->Where('hosts.host_name','LIKE','%'.$search_key.'%');

                })->orWhereHas('userDetails', function($q) use ($search_key) {

                    return $q->Where('users.name','LIKE','%'.$search_key.'%');

                });
        }
        
        $bookings = $base_query->paginate($this->paginate_count);

        foreach ($bookings as $key => $value) {

            $value->host_name = $value->hostDetails->host_name ?? '';

        }   
        
        return view('admin.bookings.index')
                    ->with('page','bookings')
                    ->with('sub_page' , 'bookings-view')
                    ->with('bookings' , $bookings);
    }

    /**
     * @method bookings_view()
     *
     * @uses view the bookings details based on bookings id
     *
     * @created Anjana 
     *
     * @updated Anjana
     *
     * @param object $request - booking Id
     * 
     * @return View page
     *
     */
    public function bookings_view(Request $request) {
        
        try {

            $booking_details = Booking::find($request->booking_id);
           
            if(!$booking_details) {

                throw new Exception(tr('booking_not_found'), 101);   
            }

            $booking_details->user_picture = $booking_details->userDetails->picture ?? asset('placeholder.jpg') ;

            $booking_details->user_name = $booking_details->userDetails->name ?? tr('user_not_avail'); 
            
            $booking_details->provider_picture = $booking_details->providerDetails->picture ?? asset('placeholder.jpg');  

            $booking_details->provider_name = $booking_details->providerDetails->name ?? tr('provider_not_avail');

            $booking_details->host_picture = $booking_details->hostDetails->picture ?? 'Host not available'; 

            $booking_details->host_name = $booking_details->hostDetails->host_name ?? asset('placeholder.jpg');

            $booking_details->price_type = HostHelper::formatted_price_type($booking_details->price_type);
            
            // get booking payments details
            $booking_payment_details = BookingPayment::BookingPaymentdetailsview()->where('booking_id','=',$booking_details->id)->first() ?:  new BookingPayment;

            return view('admin.bookings.view')
                    ->with('page', 'booking')
                    ->with('sub_page' ,'bookings-view')
                    ->with('booking_details',$booking_details)
                    ->with('booking_payment_details',$booking_payment_details);

        } catch (Exception $e) {

            return back()->with('flash_error', $e->getMessage());

        }
    }

    /**
     * @method bookings_payments()
     *
     * @uses To display bookings payments
     *
     * @created Anjana
     *
     * @updated vithya
     *
     * @param 
     *
     * @return
     *
     **/
    public function bookings_payments(Request $request) {
        
        $base_query = BookingPayment::orderBy('created_at','DESC')
            ->when($request->user_id, function ($query) use ($request) { 
                return $query->where('booking_payments.user_id',$request->user_id);
            })
            ->when($request->host_id, function ($query) use ($request) { 
                return $query->where('booking_payments.host_id',$request->host_id);
            })         
            ->when($request->provider_id, function ($query) use ($request) { 
                return $query->where('booking_payments.provider_id',$request->provider_id);
            });

        if($request->search_key) {

            $search_key = $request->search_key;

            $base_query = $base_query 

                ->whereHas('providerDetails', function($q) use ($search_key) {

                    return $q->Where('providers.name','LIKE','%'.$search_key.'%');

                })->orWhereHas('userDetails', function($q) use ($search_key) {

                    return $q->Where('users.name','LIKE','%'.$search_key.'%');

                })->orWhereHas('hostDetails', function($q) use ($search_key) {

                    return $q->Where('hosts.host_name','LIKE','%'.$search_key.'%');

                });
        }

        $booking_payments = $base_query->paginate($this->paginate_count);

        // to assign related user,provider,host detatils to booking payments
        foreach ($booking_payments as $key => $value) {

            $value->booking_unique_id = $value->bookingDetails->unique_id ?? '' ;

            $value->user_name = $value->userDetails->name ?? '' ;

            $value->provider_name = $value->providerDetails->name ?? '' ;

            $value->host_name = $value->hostDetails->host_name ?? '' ;
        }   

        return view('admin.revenues.booking_payments')
                ->with('page', 'revenues')
                ->with('sub_page' ,'revenues-payments')
                ->with('booking_payments',$booking_payments);
    }

    /**
     * @method bookings_view()
     *
     * @uses To display the Single booking payments details.
     *
     * @created Anjana
     *
     * @updated 
     *
     * @param request $booking_id
     *
     * @return view page
     *
     *@todo change method name later
     */
    public function booking_view(Request $request) {

        $booking_payment_details = BookingPayment::Bookingpaymentdetailsview()->where('booking_payments.id', $request->booking_id)->first();

        return view('admin.booking.view')
                ->with('page', 'booking')
                ->with('sub_page' ,'booking-view')
                ->with('booking_payment_details',$booking_payment_details);

    }

    /**
     * @method reviews_providers()
     *
     * @uses To list out provider review details 
     *
     * @created Anjana
     *
     * @updated 
     *
     * @param request $provider_id
     * 
     * @return return view page
     *
     */
    public function reviews_providers(Request $request) {

        $base_query = BookingProviderReview::orderBy('created_at','DESC');

        if($request->provider_id) {
                       
            $base_query = $base_query->where('booking_provider_reviews.provider_id','=',$request->provider_id);
        }  

        if($request->search_key) {

            $search_key = $request->search_key;

            $base_query = $base_query 

                ->whereHas('userDetails', function($q) use ($search_key) {

                    return $q->Where('users.name','LIKE','%'.$search_key.'%');

                })->orWhereHas('providerDetails', function($q) use ($search_key) {

                    return $q->Where('providers.name','LIKE','%'.$search_key.'%');
                });
               
        }

        $provider_reviews = $base_query->paginate($this->paginate_count);

        return view('admin.reviews.index')
                ->with('page', 'reviews')
                ->with('sub_page' , 'reviews-provider')
                ->with('reviews', $provider_reviews);
    } 

    /**
     * @method reviews_providers_view()
     *
     * @uses view the providers review details based on booking_reviews_id
     *
     * @created Anjana 
     *
     * @updated  
     *
     * @param integer $booking_reviews_id
     * 
     * @return View page
     *
     */
    public function reviews_providers_view(Request $request) {

        try {

            $booking_review_details = BookingProviderReview::find($request->booking_review_id);

            if(!$booking_review_details) {
                
                throw new Exception(tr('review_not_found'), 101);
            }

            return view('admin.reviews.view')
                    ->with('page', 'reviews') 
                    ->with('sub_page','reviews-provider') 
                    ->with('review_details' , $booking_review_details);
        
        } catch (Exception $e) {

            return back()->with('flash_error', $e->getMessage());

        }
    }

    /**
     * @method reviews_users()
     *
     * @uses To list out user review details 
     *
     * @created Anjana
     *
     * @updated 
     *
     * @param request $user_id
     * 
     * @return return view page
     *
     */
    public function reviews_users(Request $request) {

        $base_query = BookingUserReview::orderBy('created_at','DESC');

        if($request->user_id) {
                       
            $base_query = $base_query->where('booking_user_reviews.user_id',$request->user_id);
        }  

        if($request->search_key) {

            $search_key = $request->search_key;

            $base_query = $base_query 

                ->whereHas('userDetails', function($q) use ($search_key) {

                    return $q->Where('users.name','LIKE','%'.$search_key.'%');

                })->orWhereHas('providerDetails', function($q) use ($search_key) {

                    return $q->Where('providers.name','LIKE','%'.$search_key.'%');
                });
               
        }

        $user_reviews = $base_query->paginate($this->paginate_count);
        
        return view('admin.reviews.index')
                ->with('page', 'reviews')
                ->with('sub_page' , 'reviews-user')
                ->with('reviews', $user_reviews);
    }

    /**
     * @method reviews_users_view()
     *
     * @uses view the users review details based on booking_reviews_id
     * 
     * @created Anjana 
     *
     * @updated  
     *
     * @param integer booking_reviews_id
     * 
     * @return View page
     *
     */
    public function reviews_users_view(Request $request) {

        try {

            $booking_user_review_details = BookingUserReview::find($request->booking_review_id);

            if(!$booking_user_review_details) {
                
                throw new Exception(tr('review_not_found'), 101);
            }

            return view('admin.reviews.view')
                            ->with('page', 'reviews') 
                            ->with('sub_page', 'reviews-user') 
                            ->with('review_details' , $booking_user_review_details);
            
        } catch (Exception $e) {

            return back()->with('flash_error', $e->getMessage());

        }              
    }

    /**
     * @method settings()
     *
     * @uses To view the settings page
     *
     * @created Anjana 
     *
     * @updated 
     *
     * @param - 
     *
     * @return view page
     */
    public function settings() {

        $env_values = EnvEditorHelper::getEnvValues();
        
        return view('admin.settings.settings')
                ->with('env_values',$env_values)
                ->with('page' , 'settings')
                ->with('sub_page' , 'settings-view');
   
    }

    /**
     * @method settings_save()
     * 
     * @uses to update settings details
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param (request) setting details
     *
     * @return success/error message
     */
    public function settings_save(Request $request) {

        try {
            
            DB::beginTransaction();
            
            $rules =  
                [
                    'site_logo' => 'mimes:jpeg,jpg,bmp,png',
                    'site_icon' => 'mimes:jpeg,jpg,bmp,png',
                ];

            $custom_errors = 
                [
                    'mimes' => tr('image_error')
                ];

            Helper::custom_validator($request->all(),$rules,$custom_errors);

            foreach( $request->toArray() as $key => $value) {

                if($key != '_token') {

                    $check_settings = Settings::where('key' ,'=', $key)->count();

                    if( $check_settings == 0 ) {

                        throw new Exception( $key.tr('settings_key_not_found'), 101);
                    }
                    
                    if( $request->hasFile($key) ) {
                                            
                        $file = Settings::where('key' ,'=', $key)->first();
                       
                        Helper::delete_file($file->value, FILE_PATH_SITE);

                        $file_path = Helper::upload_file($request->file($key) , FILE_PATH_SITE);    

                        $result = Settings::where('key' ,'=', $key)->update(['value' => $file_path]); 

                        if( $result == TRUE ) {
                     
                            DB::commit();
                   
                        } else {

                            throw new Exception(tr('settings_save_error'), 101);
                        } 
                   
                    } else {
                    
                        $result = Settings::where('key' ,'=', $key)->update(['value' => $value]);  
                    
                        if( $result == TRUE ) {
                         
                            DB::commit();
                       
                        } else {

                            throw new Exception(tr('settings_save_error'), 101);
                        } 

                    }  
 
                }
            }

            Helper::settings_generate_json();

            return back()->with('flash_success', tr('settings_update_success'));
            
        } catch (Exception $e) {

            DB::rollback();

            return back()->with('flash_error', $e->getMessage());
        
        }
    }

    /**
     * @method env_settings_save()
     *
     * @uses To update the email details for .env file
     *
     * @created Anjana
     *
     * @updated
     *
     * @param Form data
     *
     * @return view page
     */

    public function env_settings_save(Request $request) {

        try {

            $env_values = EnvEditorHelper::getEnvValues();

            $env_settings = ['MAIL_DRIVER' , 'MAIL_HOST' , 'MAIL_PORT' , 'MAIL_USERNAME' , 'MAIL_PASSWORD' , 'MAIL_ENCRYPTION' , 'MAILGUN_DOMAIN' , 'MAILGUN_SECRET' , 'FCM_SERVER_KEY', 'FCM_SENDER_ID' , 'FCM_PROTOCOL'];

            if($env_values) {

                foreach ($env_values as $key => $data) {

                    if($request->$key) { 

                        \Enveditor::set($key, $request->$key);

                    }
                }
            }

            $message = tr('settings_update_success');

            return redirect()->route('clear-cache')->with('flash_success', $message);  

        } catch(Exception $e) {

            return back()->withInput()->with('flash_error' , $e->getMessage());

        }  

    }

    /**
     * @method profile()
     *
     * @uses  Used to display the logged in admin details
     *
     * @created Anjana
     *
     * @updated
     *
     * @param 
     *
     * @return view page 
     */

    public function profile() {

        return view('admin.account.profile')
                ->with('page', "dashboard")
                ->with('sub_page' , 'profile');
    }

    /**
     * @method profile_save()
     *
     * @uses To update the admin details
     *
     * @created Anjana
     *
     * @updated
     *
     * @param -
     *
     * @return view page 
     */

    public function profile_save(Request $request) {

        try {

            DB::beginTransaction();

            $rules = 
                [
                    'name' => 'max:191',
                    'email' => $request->admin_id ? 'email|max:191|unique:admins,email,'.$request->admin_id : 'email|max:191|unique:admins,email,NULL',
                    'admin_id' => 'required|exists:admins,id',
                    'picture' => 'mimes:jpeg,jpg,png'
                ];
            
            Helper::custom_validator($request->all(),$rules);

            $admin_details = Admin::find($request->admin_id);

            if(!$admin_details) {

                Auth::guard('admin')->logout();

                throw new Exception(tr('admin_details_not_found'), 101);
            }
        
            $admin_details->name = $request->name ?: $admin_details->name;

            $admin_details->email = $request->email ?: $admin_details->email;

            if($request->hasFile('picture') ) {
                
                Helper::delete_file($admin_details->picture, PROFILE_PATH_ADMIN); 
                
                $admin_details->picture = Helper::upload_file($request->file('picture'), PROFILE_PATH_ADMIN);
            }
            
            $admin_details->remember_token = Helper::generate_token();

            $admin_details->timezone = $request->timezone ?: $admin_details->timezone;

            $admin_details->save();

            DB::commit();

            return redirect()->route('admin.profile')->with('flash_success', tr('admin_profile_success'));


        } catch(Exception $e) {

            DB::rollback();

            return redirect()->back()->withInput()->with('flash_error' , $error);

        }    
    
    }

    /**
     * @method change_password()
     *
     * @uses To change the admin password
     *
     * @created Anjana
     *
     * @updated
     *
     * @param 
     *
     * @return view page 
     */

    public function change_password(Request $request) {

        try {

            DB::begintransaction();

            $rules = 
            [              
                'password' => 'required|confirmed|min:6',
                'old_password' => 'required',
            ];
            
            Helper::custom_validator($request->all(),$rules);

            $admin_details = Admin::find(Auth::guard('admin')->user()->id);

            if(!$admin_details) {

                Auth::guard('admin')->logout();
                              
                throw new Exception(tr('admin_details_not_found'), 101);

            }

            if(Hash::check($request->old_password,$admin_details->password)) {

                $admin_details->password = Hash::make($request->password);

                $admin_details->save();

                DB::commit();

                Auth::guard('admin')->logout();

                // return back()->with('flash_success', tr('password_change_success'));
                return redirect()->route('admin.login');
                
            } else {

                throw new Exception(tr('password_mismatch'));
            }

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->back()->withInput()->with('flash_error' , $error);

        }    
    
    }

    /**
     * @method help()
     *
     * @uses display contact details
     *
     * @created Anjana 
     *
     * @updated
     *
     * @param 
     *
     * @return view page 
     */
    public function help(Request $request) {

        return view('admin.help')
                ->with('page' , 'help')
                ->with('sub_page' , 'help-view');

    }


    /**
     * @method documents_index()
     *
     * @uses To display document list page
     *
     * @created Anjana
     *
     * @updated Vithya R
     *
     * @param 
     *
     * @return view page
     */
    public function documents_index() {

        $documents = Document::orderBy('updated_at','desc')->paginate($this->paginate_count);
        
        return view('admin.documents.index')
                    ->with('page' , 'documents')
                    ->with('sub_page','documents-view')
                    ->with('documents' , $documents);
    }

    /**
     * @method documents_create()
     *
     * @uses To create document details
     *
     * @created Anjana
     *
     * @updated vithya
     *
     * @param -
     *
     * @return view page
     */
    public function documents_create() {

        $document_details = new Document;
        
        return view('admin.documents.create')
                ->with('page' , 'documents')
                ->with('sub_page','documents-create')
                ->with('document_details', $document_details);
    }
  
    /**
     * @method documents_edit()
     *
     * @uses To display and update document details based on the document id
     *
     * @created Anjana
     *
     * @updated vithya
     *
     * @param object $request - document Id
     * 
     * @return redirect view page 
     *
     */
    public function documents_edit(Request $request) {

        $document_details = Document::find($request->document_id);

        if(!$document_details) {

            return back()->with('flash_error', tr('document_not_found'));
        }

        return view('admin.documents.edit')
                    ->with('page','documents')
                    ->with('sub_page','documents-view')
                    ->with('document_details',$document_details);
    }

    /**
     * @method documents_save()
     *
     * @uses To save the details based on document or to create a new document
     *
     * @created Anjana
     *
     * @updated vithya
     *
     * @param object $request - document object details
     * 
     * @return success/failure message
     *
     */
    public function documents_save(Request $request) {

        try {

            DB::beginTransaction();

            $rules = [

                'name' => 'required|max:191',
                'description' => 'max:191'
            ];
            
            Helper::custom_validator($request->all(),$rules);
            
            $document_details = new Document;

            $message = tr('document_created_success');

            if($request->document_id != '') {

                $document_details = Document::find($request->document_id);

                $message = tr('document_updated_success');

                if($request->hasFile('picture')) {
                    Helper::delete_file($request->file('picture'), FILE_PATH_PROVIDER_DOCUMENT);
                }

            } else {
               
                $document_details->status = APPROVED;

            }

            if($request->hasFile('picture')) {

                $document_details->picture = Helper::upload_file($request->file('picture'), FILE_PATH_PROVIDER_DOCUMENT);

            }


            $document_details->name = $request->name ?: $document_details->name;
            
            $document_details->description = $request->description ?: '';

            if($document_details->save()) {

                DB::commit();

                return redirect()->route('admin.documents.view', ['document_id' => $document_details->id])->with('flash_success', $message);

            }

            return back()->with('flash_error', tr('document_save_failed'));
            
        } catch(Exception $e) {

            DB::rollback();

            return redirect()->back()->withInput()->with('flash_error', $e->getMessage());

        }
        
    }

    /**
     * @method documents_view()
     *
     * @uses view the document details based on document id
     *
     * @created Anjana 
     *
     * @updated Anjana
     *
     * @param object $request - document Id
     * 
     * @return View page
     *
     */
    public function documents_view(Request $request) {

        $document_details = Document::find($request->document_id);

        if(!$document_details) {

            return redirect()->route('admin.documents.index')->with('flash_error',tr('document_not_found'));

        }

        return view('admin.documents.view')
                    ->with('page', 'documents')
                    ->with('sub_page','documents-view')
                    ->with('document_details' , $document_details);
    
    }

    /**
     * @method documents_delete
     *
     * @uses To delete the document details based on selected document id
     *
     * @created Anjana
     *
     * @updated 
     *
     * @param integer $document_id
     * 
     * @return response of success/failure details
     *
     */
    public function documents_delete(Request $request) {

        try {

            DB::beginTransaction();

            $document_details = Document::find($request->document_id);

            if(!$document_details) {

                throw new Exception(tr('document_not_found'), 101);
                
            }

            if($document_details->delete()) {

                DB::commit();

                return redirect()->route('admin.documents.index')->with('flash_success',tr('document_deleted_success')); 

            } 

            throw new Exception(tr('document_delete_failed'));

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->route('admin.documents.index')->with('flash_error', $e->getMessage());

        }
   
    }

    /**
     * @method documents_status()
     *
     * @uses To delete the document details based on document id
     *
     * @created Anjana
     *
     * @updated 
     *
     * @param integer $document_id
     * 
     * @return response success/failure message
     *
     */
    public function documents_status(Request $request) {

        try {

            DB::beginTransaction();

            $document_details = Document::find($request->document_id);

            if(!$document_details) {

                throw new Exception(tr('document_not_found'), 101);
                
            }

            $document_details->status = $document_details->status ? DECLINED : APPROVED;

            if( $document_details->save()) {

                DB::commit();

                $message = $document_details->status ? tr('document_approve_success') : tr('document_decline_success');

                return redirect()->back()->with('flash_success', $message);

            } 

            throw new Exception(tr('document_status_change_failed'));
                
        } catch(Exception $e) {

            DB::rollback();

            return redirect()->route('admin.documents.index')->with('flash_error', $e->getMessage());
        }

    }
    
    /**
     * @method providers_documents_index()
     *
     * @uses To display the providers documents list.
     *
     * @created Anjana
     *
     * @updated Vidhya
     *
     * @param request
     *
     * @return view page
     */
    public function providers_documents_index(Request $request) {

        $base_query = ProviderDocument::groupBy('provider_id')->select('*',DB::raw("COUNT(*) as total_documents"));
        
        if($request->search_key) {

            $search_key = $request->search_key;

            $base_query = $base_query 

                ->whereHas('providerDetails', function($q) use ($search_key) {

                    return $q->Where('providers.name','LIKE','%'.$search_key.'%');

                });
        }
        
        $provider_documents  = $base_query->paginate($this->paginate_count);


        return view('admin.providers.documents.index')
                        ->with('page' , 'providers')
                        ->with('sub_page','providers-documents')
                        ->with('provider_documents' , $provider_documents);
    }

    /**
     * @method providers_documents_view()
     *
     * @uses view the provider document list, based on provider Id / document id
     *
     * @created Anjana 
     *
     * @updated Vidhya
     *
     * @param object $request - Provider Id, Document Id
     * 
     * @return View page
     *
     */
    public function providers_documents_view(Request $request) {
       
        $provider_documents = ProviderDocument::orderBy('updated_at','desc')
                ->when($request->provider_id, function ($query) use ($request) { 
                    return $query->where('provider_documents.provider_id', $request->provider_id);
                })
                ->when($request->document_id, function ($query) use ($request) { 
                    return $query->where('document_id', $request->document_id);
                })                
                ->get();

        $provider_details = Provider::find($request->provider_id);

        return view('admin.providers.documents.view')
                    ->with('page' , 'providers')
                    ->with('sub_page','providers-documents')
                    ->with('provider_details' , $provider_details)
                    ->with('provider_documents' , $provider_documents);
    }

    /**
     * @method providers_documents_status
     *
     * @uses To update provider_document status as DECLINED/APPROVED based on provider_document id
     *
     * @created Anjana
     *
     * @updated 
     *
     * @param request $provider_document_id
     * 
     * @return response success/failure message
     *
     **/
    public function providers_documents_status(Request $request) {

        try {

            DB::beginTransaction();

            $provider_document_details = ProviderDocument::find($request->provider_document_id);

            if(!$provider_document_details) {

                throw new Exception(tr('document_not_found'), 101);                
            }

            $provider_document_details->status = $provider_document_details->status ? DECLINED : APPROVED;

            if($provider_document_details->save()) {

                // All provider documents status

                $provider_documents = ProviderDocument::where('provider_id', $provider_document_details->provider_id)->get();

                $total_documents = $provider_documents->count();

                $approved_documents = $provider_documents->where('status', APPROVED)->count();

                if($approved_documents < $total_documents) {

                    Provider::where('providers.id', $provider_document_details->provider_id)->update(['is_document_verified' => PROVIDER_DOCUMENT_UPLOADED]);

                } elseif($approved_documents == $total_documents) {

                    Provider::where('providers.id', $provider_document_details->provider_id)->update(['is_document_verified' => PROVIDER_DOCUMENT_VERIFIED]);

                }

                DB::commit();

                $message = $provider_document_details->status ? tr('document_approve_success') : tr('document_decline_success');

                return redirect()->back()->with('flash_success', $message);
            }
            
            throw new Exception(tr('document_status_change_failed'));

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->back()->with('flash_error', $e->getMessage());

        }

    }

    /**
     * @method static_pages_index()
     *
     * @uses To list the static pages
     *
     * @created vithya
     *
     * @updated vithya  
     *
     * @param -
     *
     * @return List of pages   
     */

    public function static_pages_index() {

        $static_pages = StaticPage::orderBy('updated_at' , 'desc')->paginate($this->paginate_count);

        return view('admin.static_pages.index')
                    ->with('page','static_pages')
                    ->with('sub_page',"static_pages-view")
                    ->with('static_pages',$static_pages);
    
    }

    /**
     * @method static_pages_create()
     *
     * @uses To create static_page details
     *
     * @created vithya
     *
     * @updated Anjana   
     *
     * @param
     *
     * @return view page   
     *
     */
    public function static_pages_create() {

        $static_keys = ['about' , 'contact' , 'privacy' , 'terms' , 'help' , 'faq' , 'refund', 'cancellation'];

        foreach ($static_keys as $key => $static_key) {

            // Check the record exists

            $check_page = StaticPage::where('type', $static_key)->first();

            if($check_page) {
                unset($static_keys[$key]);
            }
        }

        $section_types = static_page_footers(0, $is_list = YES);

        $static_keys[] = 'others';

        $static_page_details = new StaticPage;

        return view('admin.static_pages.create')
                ->with('page','static_pages')
                ->with('sub_page',"static_pages-create")
                ->with('static_keys', $static_keys)
                ->with('static_page_details',$static_page_details)
                ->with('section_types',$section_types);
   
    }

    /**
     * @method static_pages_edit()
     *
     * @uses To display and update static_page details based on the static_page id
     *
     * @created Anjana
     *
     * @updated vithya
     *
     * @param object $request - static_page Id
     * 
     * @return redirect view page 
     *
     */
    public function static_pages_edit(Request $request) {

        try {

            $static_page_details = StaticPage::find($request->static_page_id);

            if(!$static_page_details) {

                throw new Exception(tr('static_page_not_found'), 101);
            }

            $static_keys = ['about' , 'contact' , 'privacy' , 'terms' , 'help' , 'faq' , 'refund', 'cancellation'];

            foreach ($static_keys as $key => $static_key) {

                // Check the record exists

                $check_page = StaticPage::where('type', $static_key)->first();

                if($check_page) {
                    unset($static_keys[$key]);
                }
            }

            $section_types = static_page_footers(0, $is_list = YES);

            $static_keys[] = 'others';

            $static_keys[] = $static_page_details->type;

            return view('admin.static_pages.edit')
                    ->with('page' , 'static_pages')
                    ->with('sub_page','static_pages-view')
                    ->with('static_keys' , array_unique($static_keys))
                    ->with('static_page_details' , $static_page_details)
                    ->with('section_types',$section_types);
            
        } catch(Exception $e) {

            return redirect()->route('admin.static_pages.index')->with('flash_error' , $error);

        }
    }

    /**
     * @method static_pages_save()
     *
     * @uses To create/update the page details 
     *
     * @created vithya
     *
     * @updated vithya
     *
     * @param
     *
     * @return index page    
     *
     */
    public function static_pages_save(Request $request) {

        try {

            DB::beginTransaction();

            $rules = [
                    'title' => 'required|max:191',
                    'description' => 'required',
                    'type' => !$request->static_page_id ? 'required' : ""
                ];
                   
            Helper::custom_validator($request->all(),$rules);

            if($request->static_page_id != '') {

                $static_page_details = StaticPage::find($request->static_page_id);

                $message = tr('static_page_updated_success');                    

            } else {

                $check_page = "";

                // Check the staic page already exists                  
                
                if($request->type != 'others') {

                    $check_page = StaticPage::where('type',$request->type)->first();

                    if($check_page) {

                        return back()->with('flash_error',tr('static_page_already_alert'));
                    }

                }

                $message = tr('static_page_created_success');

                $static_page_details = new StaticPage;

                $static_page_details->status = APPROVED;

            }

            $static_page_details->title = $request->title ?: $static_page_details->title;

            $static_page_details->description = $request->description ?: $static_page_details->description;

            $static_page_details->type = $request->type ?: $static_page_details->type;

            $static_page_details->section_type = $request->section_type ?: $static_page_details->section_type;

            if($static_page_details->save()) {

                DB::commit();

                Helper::settings_generate_json();
                
                return redirect()->route('admin.static_pages.view', ['static_page_id' => $static_page_details->id] )->with('flash_success', $message);

            } 

            throw new Exception(tr('static_page_save_failed'), 101);
                      
        } catch(Exception $e) {

            DB::rollback();

            return back()->withInput()->with('flash_error', $e->getMessage());

        }
    
    }

    /**
     * @method static_pages_delete()
     *
     * Used to view file of the create the static page 
     *
     * @created vithya
     *
     * @updated vithya R
     *
     * @param -
     *
     * @return view page   
     */

    public function static_pages_delete(Request $request) {

        try {

            DB::beginTransaction();

            $static_page_details = StaticPage::find($request->static_page_id);

            if(!$static_page_details) {

                throw new Exception(tr('static_page_not_found'), 101);
                
            }

            if($static_page_details->delete()) {

                DB::commit();

                return redirect()->route('admin.static_pages.index')->with('flash_success',tr('static_page_deleted_success')); 

            } 

            throw new Exception(tr('static_page_error'));

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->route('admin.static_pages.index')->with('flash_error', $e->getMessage());

        }
    
    }

    /**
     * @method static_pages_view()
     *
     * @uses view the static_pages details based on static_pages id
     *
     * @created Anjana 
     *
     * @updated vithya
     *
     * @param object $request - static_page Id
     * 
     * @return View page
     *
     */
    public function static_pages_view(Request $request) {

        $static_page_details = StaticPage::find($request->static_page_id);

        if(!$static_page_details) {
           
            return redirect()->route('admin.static_pages.index')->with('flash_error',tr('static_page_not_found'));

        }

        return view('admin.static_pages.view')
                    ->with('page', 'static_pages')
                    ->with('sub_page','static_pages-view')
                    ->with('static_page_details' , $static_page_details);
    }

    /**
     * @method static_pages_status_change()
     *
     * @uses To update static_page status as DECLINED/APPROVED based on static_page id
     *
     * @created vithya
     *
     * @updated vithya
     *
     * @param - integer static_page_id
     *
     * @return view page 
     */

    public function static_pages_status_change(Request $request) {

        try {

            DB::beginTransaction();

            $static_page_details = StaticPage::find($request->static_page_id);

            if(!$static_page_details) {

                throw new Exception(tr('static_page_not_found'), 101);
                
            }

            $static_page_details->status = $static_page_details->status == DECLINED ? APPROVED : DECLINED;

            $static_page_details->save();

            DB::commit();

            $message = $static_page_details->status == DECLINED ? tr('static_page_decline_success') : tr('static_page_approve_success');

            return redirect()->back()->with('flash_success', $message);

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->back()->with('flash_error', $e->getMessage());

        }

    }

    // Provider Subscription Methos begins

    /**
     * @method provider_subscriptions_index()
     *
     * @uses To list out all the provider_subscriptions
     *
     * @created Anjana
     *
     * @updated Anjana
     *
     * @param
     *
     * @return success/failure message
     */
    public function provider_subscriptions_index(Request $request) {

        $provider_subscriptions = ProviderSubscription::orderBy('updated_at', 'desc')->paginate($this->paginate_count);
             
        return view('admin.provider_subscriptions.index')
                ->with('page', 'provider_subscriptions')
                ->with('sub_page', 'provider_subscriptions-view')
                ->with('provider_subscriptions', $provider_subscriptions);
    }

    /**
     * @method provider_subscriptions_create()
     *
     * @uses To create provider_subscriptions details 
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param
     *
     * @return view page
     *
     */
    public function provider_subscriptions_create() {

        $provider_subscription_details = new ProviderSubscription;
        
        return view('admin.provider_subscriptions.create')
                ->with('page', 'provider_subscriptions')
                ->with('sub_page', 'provider_subscriptions-create')
                ->with('provider_subscription_details', $provider_subscription_details);
    
    }

    /**
     * @method provider_subscriptions_edit()
     *
     * @uses To display and update provider_subscription details based on the provider_subscription id
     *
     * @created Anjana
     *
     * @updated vithya
     *
     * @param object $request - provider_subscription Id
     * 
     * @return redirect view page 
     *
     */
    public function provider_subscriptions_edit(Request $request) {

        try {

            $provider_subscription_details = ProviderSubscription::where('id', $request->provider_subscription_id)->first();
            
            if(!$provider_subscription_details) {

                throw new Exception(tr('provider_subscription_not_found'), 101);
            }
                
            return view('admin.provider_subscriptions.edit')
                        ->with('page', 'provider_subscriptions')
                        ->with('sub_page', 'provider_subscriptions-view')
                        ->with('provider_subscription_details', $provider_subscription_details);
       
        } catch(Exception $e) {

            return redirect()->route('admin.provider_subscriptions.index')->with('flash_error', $e->getMessage());

        } 
    
    }

    /**
     * @method provider_subscriptions_save
     *
     * @uses To save the provider_subscriptions based on id
     *
     * @created Anjana H
     *
     * @updated Anjana
     *
     * @param object $request - provider_subscription Id
     *
     * @return response of success/failure response
     *
     */
    public function provider_subscriptions_save(Request $request) {

        try {

            DB::begintransaction();

            $rules = [
                'title'  => 'required|max:255',
                'description' => 'max:255',
                'amount' => 'required|numeric|min:0|max:10000000',
                'plan' => 'required|numeric|min:1',
                'image'  => 'mimes:jpeg,png,jpg',
            ];

            Helper::custom_validator($request->all(),$rules);

            $provider_subscription_details = $request->provider_subscription_id ? ProviderSubscription::find($request->provider_subscription_id) : new ProviderSubscription;

            if(!$provider_subscription_details) {

                throw new Exception(tr('provider_subscription_not_found'), 101);
            }

            if ($request->hasFile('image')) {

                Helper::delete_file('/uploads/provider_subscriptions/', $provider_subscription_details->image);

                $picture = Helper::upload_avatar('uploads/provider_subscriptions', $request->file('image'));
            }

            $provider_subscription_details->status = APPROVED;

            $provider_subscription_details->title = $request->title;

            $provider_subscription_details->description = $request->description ?: "";

            $provider_subscription_details->amount = $request->amount;

            $provider_subscription_details->plan = $request->plan;

            if( $provider_subscription_details->save() ) {

                DB::commit();

                $message = $request->provider_subscription_id ? tr('provider_subscription_create_success') : tr('provider_subscription_update_success');

                return redirect()->route('admin.provider_subscriptions.view', ['provider_subscription_id' => $provider_subscription_details->id])->with('flash_success', $message);
            } 

            throw new Exception(tr('provider_subscription_saved_error') , 101);

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->back()->withInput()->with('flash_error', $e->getMessage());
        } 
    
    }

    /**
     * @method provider_subscriptions_view
     *
     * @uses To display subscription based on provider_subscription_id
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param request provider_subscription_id
     *
     * @return success/failure message
     */
    public function provider_subscriptions_view(Request $request) {

        try {

           $provider_subscription_details = ProviderSubscription::where('id', $request->provider_subscription_id)->first();

            if(!$provider_subscription_details) {

                throw new Exception(tr('provider_subscription_not_found'), 101);
            }

            $total_subscriptions = ProviderSubscriptionPayment::where('provider_subscription_id',  $request->provider_subscription_id)->where('status', PAID)->count();

            $provider_subscription_details->total_subscriptions = $total_subscriptions;

            $total_revenue = ProviderSubscriptionPayment::where('provider_subscription_id', $request->provider_subscription_id)->where('status', PAID)->sum('paid_amount');

            $provider_subscription_details->total_revenue = $total_revenue;

            return view('admin.provider_subscriptions.view')
                    ->with('page', 'provider_subscriptions')
                    ->with('sub_page', 'provider_subscriptions-view')
                    ->with('provider_subscription_details', $provider_subscription_details);
        
        } catch(Exception $e) {

            return redirect()->back()->with('flash_error', $e->getMessage());

        }     
    
    }

    /**
     * @method provider_subscriptions_delete()
     *
     * @uses To delete the particular provider_subscription detail
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param request sub provider_subscription_id
     *
     * @return success/error message
     */
    public function provider_subscriptions_delete(Request $request) {

        try {

            DB::beginTransaction();

            $provider_subscription_details = ProviderSubscription::find($request->provider_subscription_id);

            if (!$provider_subscription_details) {

                throw new Exception(tr('provider_subscription_not_found'), 101);
            } 

            if( $provider_subscription_details->delete()) {

                DB::commit();

                return redirect()->route('admin.provider_subscriptions.index')->with('flash_success', tr('provider_subscription_delete_success'));
            }

            throw new Exception(tr('provider_subscription_delete_error'), 101);
                
        } catch (Exception $e) {

            DB::rollback();

            return redirect()->route('admin.provider_subscriptions.index')->with('flash_error', $e->getMessage());

        }

    }

    /**
     * @method provider_subscriptions_status_change()
     *
     * @uses to change status approve/decline update process
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param request sub provider_subscription_id
     *
     * @return success message
     */
    public function provider_subscriptions_status_change(Request $request) {

        try {

            DB::begintransaction();

            $provider_subscription_details = ProviderSubscription::find($request->provider_subscription_id);

            if(!$provider_subscription_details) {

                throw new Exception(tr('provider_subscription_not_found'), 101);
            } 

            $provider_subscription_details->status = $provider_subscription_details->status == APPROVED ? DECLINED : APPROVED;

            if( $provider_subscription_details->save() ) {

                DB::commit();

                $message = $provider_subscription_details->status == APPROVED ? tr('provider_subscription_approved_success') : tr('provider_subscription_declined_success');

                return back()->with('flash_success', $message);
            }

            throw new Exception(tr('provider_subscription_status_error'), 101);

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->route('admin.provider_subscriptions.index')->with('flash_error', $e->getMessage());
        }  
        
    }

    /**
     * @method provider_subscriptions_is_popular()
     *
     * @uses Make the provider_subscriptions as popular
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param request sub provider_subscription id
     *
     * @return success/error message
     */

    public function provider_subscriptions_is_popular(Request $request) {

        try {

            DB::begintransaction();

            $provider_subscription_details = ProviderSubscription::find($request->provider_subscription_id
                );

            if (!$provider_subscription_details) {

                throw new Exception(tr('provider_subscription_not_found'), 101);
            }

            $provider_subscription_details->is_popular = $provider_subscription_details->is_popular == APPROVED ? DECLINED : APPROVED ;

            if( $provider_subscription_details->save() ) {

                DB::commit();

                $message = $provider_subscription_details->is_popular == YES ? tr('provider_subscription_add_popular_success') : tr('provider_subscription_remove_popular_success');
                
                return back()->with('flash_success',$message );
            } 

            throw new Exception(tr('provider_subscription_is_popular_error'), 101);  

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->route('admin.provider_subscriptions.index')->with('flash_error', $e->getMessage());
        }

    }
    
    /**
     * @method provider_subscriptions_plans()
     *
     * @uses To display subscriptions based on provider id
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param Integer (request) $provider_id
     *
     * @return success/error message
     */
    public function provider_subscriptions_plans(Request $request) {

        try {


            $provider_subscription_payments = ProviderSubscriptionPayment::where('provider_id' , $request->provider_id)
                        ->orderBy('provider_subscription_payments.created_at' , 'desc')
                         ->get();

            $free_subscription = $provider_subscription_payments->where('paid_amount','=',0.00)->pluck('provider_subscription_id') ?? [];
            

            $provider_subscriptions = ProviderSubscription::orderBy('created_at','desc')
                            ->when($free_subscription, function ($q) use ($free_subscription) {
                                if($free_subscription->count() >= 1)
                                {
                                    return $q->whereNotIn('id', $free_subscription);
                                }
                                
                            })
                         ->where('status', APPROVED)->get();

                        
            return view('admin.provider_subscriptions.provider_plans')
                        ->with('page', 'provider_subscriptions')   
                        ->with('sub_page','provider_subscriptions-view')
                        ->with('provider_subscriptions' , $provider_subscriptions)
                        ->with('provider_id', $request->provider_id)
                        ->with('provider_subscription_payments', $provider_subscription_payments); 
        
        } catch (Exception $e) {
            
            return redirect()->back()->with('flash_error',$e->getMessage());
        }            
    }
    
    /**
     * @method provider_subscriptions_plans_save()
     *
     * @uses To save provider subscription based on subscription and provider id
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param Integer (request) $provider_subscription_id, $provider_id
     *
     * @return success/error message
     */
    public function provider_subscriptions_plans_save(Request $request) {
          
        try {

            // validation

            DB::beginTransaction();

            // record check
            $provider_details = Provider::find($request->provider_id);

            if(!$provider_details ) {

                throw new Exception( tr('provider_not_found'), 101);
            } 
 
            $provider_subscription_details = ProviderSubscription::where('id', $request->provider_subscription_id)->first();
           
            if(!$provider_subscription_details) {

                throw new Exception(tr('provider_subscription_not_found'), 101);
            } 

            $provider_subscription_payment = new ProviderSubscriptionPayment();
           
            $provider_subscription_payment->provider_id = $provider_details->id;

            $provider_subscription_payment->provider_subscription_id = $provider_subscription_details->id;

            $provider_subscription_payment->subscription_amount = $provider_subscription_details->amount;

            $provider_subscription_payment->paid_amount = $provider_subscription_details->amount;
             
            $provider_subscription_payment->payment_id = ($provider_subscription_payment->subscription_amount > 0) ? uniqid(str_replace(' ', '-', 'PAY')) : 'Free Plan'; 

            $check_provider_subscription_payment = ProviderSubscriptionPayment::where('provider_id' , $provider_details->id)->where('status', DEFAULT_TRUE)->orderBy('id', 'desc')->first();
            
            if ($check_provider_subscription_payment) {
            
                $expiry_date = strtotime($check_provider_subscription_payment->expiry_date);
               
                $plan = $provider_subscription_details->plan;
                
                $provider_subscription_payment->expiry_date = $expiry_date >= strtotime(date('Y-m-d H:i:s')) ? date('Y-m-d H:i:s', strtotime("+{$plan} months", $expiry_date)) :  date('Y-m-d H:i:s',strtotime("+{$plan} months"));

            } else {

                $provider_subscription_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+{$provider_subscription_details->plan} months"));
            }

            $provider_subscription_payment->status = PAID;

            if($provider_subscription_payment->save() )  {

                $provider_details->provider_type = PROVIDER_TYPE_PAID;

                $provider_details->save();

                DB::commit();

                return back()->with('flash_success', tr('provider_subscription_applied_success'));

            } 
            
            throw new Exception(tr('admin_user_subascription_save_error'), 101);
                        
        } catch (Exception $e) {
            
            DB::rollback();
            
            return back()->with('flash_error',$e->getMessage());
        }

    }


    /**
     * @method provider_auto_subscription_disable()
     *
     * @uses To prevent automatic subscription of provider,provider has option to cancel subscription
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param $request - Provider details & payment details
     *
     * @return success/failure message
     */
    public function provider_auto_subscription_disable(Request $request) {

       try {
            
            DB::beginTransaction();

            $provider_payment = ProviderSubscriptionPayment::where('provider_id', $request->provider_id)->where('status', PAID_STATUS)->orderBy('created_at', 'desc')->first();

            if(!$provider_payment) {

                throw new Exception(tr('provider_subscription_payment_not_found'), 101);
            } 

            $provider_payment->is_cancelled = AUTORENEWAL_CANCELLED;

            $provider_payment->cancelled_reason = $request->cancel_reason;

            if ($provider_payment->save()) {
               
                DB::commit();

                return back()->with('flash_success', tr('admin_cancel_subscription_success'));            
            }

            throw new Exception(tr('provider_auto_subscription_disable_error'), 101);
          
        } catch (Exception $e) {
            
            DB::rollback();

            return back()->with('flash_error',$e->getMessage());
        }      

    }

    /**
     * @method provider_auto_subscription_enable()
     *
     * @uses To prevent automatic subscription, provider has option to cancel provider subscriptions
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param (request) - provider details & payment details
     *
     * @return success/failure message
     */
    public function provider_auto_subscription_enable(Request $request) {
        
        try {

            $provider_payment = ProviderSubscriptionPayment::where('provider_id', $request->provider_id)->where('status', PAID_STATUS)->orderBy('created_at', 'desc')
                ->where('is_cancelled', AUTORENEWAL_CANCELLED)
                ->first();

            if(!$provider_payment)  {

                throw new Exception(tr('provider_subscription_payment_not_found'), 101);
            }  

            $provider_payment->is_cancelled = AUTORENEWAL_ENABLED;

            $provider_payment->save();

            return back()->with('flash_success', tr('provider_auto_subscription_enable_success'));
        
        } catch (Exception $e) {
            return back()->with('flash_error', $e->getMessage());
        }     

    }

    /**
     * @method provider_subscription_payments()
     *
     * @uses To list out provider_subscription payment details
     *
     * @created Anjana
     *
     * @updated Anjana
     *
     * @param -
     *
     * @return view page
     */
    public function provider_subscription_payments(Request $request) {
        
        try {

            $base_query = ProviderSubscriptionPayment::orderBy('created_at', 'desc');

            if($request->provider_subscription_id) {
                           
                $base_query = $base_query->where('provider_subscription_payments.provider_subscription_id',$request->provider_subscription_id);
            }


            if($request->search_key) {

                $search_key = $request->search_key;

                $base_query = $base_query 

                    ->whereHas('providerDetails', function($q) use ($search_key) {

                        return $q->Where('providers.name','LIKE','%'.$search_key.'%');

                    })->orWhereHas('providerSubscriptionDetails', function($q) use ($search_key) {

                        return $q->Where('provider_subscriptions.title','LIKE','%'.$search_key.'%');

                    })->orwhere(function ($q) use ($search_key) {

                        $q->where('provider_subscription_payments.payment_id', "like", "%" . $search_key . "%");
                    });
            }
            
            $provider_subscription_payments = $base_query->paginate($this->paginate_count);
            
            // @todo Change the page and sub_page variable names
           
            return view('admin.revenues.provider_subscription_payments')
                    ->with('page','revenues-sidebar')
                    ->with('sub_page', 'revenues-provider_subscription-payments')
                    ->with('provider_subscription_payments', $provider_subscription_payments);

        } catch (Exception $e) {
            
            return back()->with('flash_error',$e->getMessage());
        }    
    }

    /**
     * @method provider_subscription_payments_view()
     *
     * @uses To list out provider_subscription payment details
     *
     * @created NAVEEN S
     *
     * @updated Anjana
     *
     * @param 
     *
     * @return view page
     */
    public function provider_subscription_payments_view(Request $request) {
        
        try {

            $provider_subscription_payment = ProviderSubscriptionPayment::find($request->id);

            if(!$provider_subscription_payment) {
                           
                throw new Exception(tr('provider_subscription_payment_not_found'), 101);
            }
           
            return view('admin.revenues.provider_subscription_payments_view')
                        ->with('page','revenues-sidebar')
                        ->with('sub_page', 'revenues-provider_subscription-payments')
                        ->with('provider_subscription_payment', $provider_subscription_payment);
       
        } catch (Exception $e) {
            
            return back()->with('flash_error',$e->getMessage());
        }  
    }
    
    // Provider Subscription Methos End 

    /**
     * @method vehicle_details_create()
     *
     * @uses To create user vehicle details
     *
     * @created  Bhawya
     *
     * @updated Bhawya
     *
     * @param 
     * 
     * @return return view page
     *
     */
    public function vehicle_details_create(Request $request) {

        $vehicle_details = new UserVehicle;
        
        $vehicle_details->user_id = $request->user_id;

        return view('admin.vehicle_details.create')
                    ->with('page' , 'vehicle_details')
                    ->with('sub_page','vehicle_detials-create')
                    ->with('vehicle_details', $vehicle_details);    
    }

    /**
     * @method vehicle_details_save
     *
     * @uses To save the vehicle details based on users
     *
     * @created Bhawya
     *
     * @updated
     *
     * @param object $request - vehicle object details
     * 
     * @return response of success/failure response details
     *
     */
    
    public function vehicle_details_save(Request $request) {

        try {

            DB::beginTransaction();

            $rules = 
            [
                'user_id' => 'required',
                'vehicle_number' => 'required|max:191',
            ];
            
            Helper::custom_validator($request->all(),$rules);
            
            $vehicle_details = new UserVehicle;

            $message = tr('vehicle_details_created_success');

            if($request->vehicle_id != '') {

                $vehicle_details = UserVehicle::find($request->vehicle_id);

                $message = tr('vehicle_details_updated_success');

            } else {
               
                $vehicle_details->status = APPROVED;

            }

            // Load the details from the request

            $vehicle_details->user_id = $request->user_id ?: '';

            $vehicle_details->vehicle_number = $request->vehicle_number ?: '';

            $vehicle_details->vehicle_type = $request->vehicle_type ?: '';

            $vehicle_details->vehicle_brand = $request->vehicle_brand ?: '';

            $vehicle_details->vehicle_model = $request->vehicle_model ?: '';

            if($vehicle_details->save()) {

                DB::commit();

                return redirect()->route('admin.users.view', ['user_id' => $vehicle_details->user_id])->with('flash_success', $message);
            }

            return back()->with('flash_error', tr('vehicle_details_save_failed'));
           
        } catch(Exception $e) {

            DB::rollback();

            return redirect()->back()->withInput()->with('flash_error', $e->getMessage());

        }
        
    }

    /**
     * @method vehicle_details_delete()
     *
     * @uses delete the vehicle details based on user id
     *
     * @created Bhawya
     *
     * @updated  
     *
     * @param object $request - User Id
     * 
     * @return response of success/failure details with view page
     *
     */
    public function vehicle_details_delete(Request $request) {

        try {

            DB::begintransaction();

            $vehicle_details = UserVehicle::find($request->vehicle_id);
            
            if(!$vehicle_details) {

                throw new Exception(tr('vehicle_details_not_found'), 101);                
            }

            if($vehicle_details->delete()) {

                DB::commit();
 
                return back()->with('flash_success', tr('vehicle_deleted_success')); 

            } 
            
            throw new Exception(tr('vehicle_delete_failed'),101);
            
        } catch(Exception $e){

            DB::rollback();

            return redirect()->back()->with('flash_error', $e->getMessage());

        }       
         
    }

    /**
     * @method vehicle_details_edit()
     *
     * @uses To display and update vehicle details based on the user id
     *
     * @created Bhawya
     *
     * @updated
     *
     * @param object $request - Vehicle details ID
     * 
     * @return redirect view page 
     *
     */
    public function vehicle_details_edit(Request $request) {

        try {

            $vehicle_details = UserVehicle::find($request->vehicle_id);

            if(!$vehicle_details) { 

                throw new Exception(tr('details_not_found'), 101);
            }

            return view('admin.vehicle_details.edit')
                    ->with('page' , 'vehicle_details')
                    ->with('sub_page','vehicle_detials-create')
                    ->with('vehicle_details', $vehicle_details); 
            
        } catch(Exception $e) {

            return back()->with('flash_error', $e->getMessage()); 
        }
    
    }

    public function admin_control() {
           
        return view('admin.settings.control')->with('page', tr('admin_control'));
        
    }

    /**
     * @method settings_generate_json()
     *
     * @uses To update settings.json file with updated details.
     *     
     * @created vidhya
     *
     * @updated vidhya
     *
     * @param -
     *
     * @return viwe page.
     */
    public function settings_generate_json(Request $request) {

        Helper::settings_generate_json();

        $file_path = url("/default-json/settings.json");

        return redirect()->route('admin.control')->with('flash_success', 'Settings file updated successfully.'.$file_path);
        
    }

    /**
     * @method spaces_index()
     *
     * @uses show hosts list
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param -
     *
     * @return view page
     */   
    
    public function spaces_index(Request $request) {

        $base_query = Host::orderBy('hosts.created_at','desc');

        $page = "hosts"; $sub_page = "hosts-view"; $page_title = tr('view_spaces');

        if($request->provider_id) {

            $base_query = $base_query->where('hosts.provider_id',$request->provider_id);

            // $page = "providers"; $sub_page = "providers-view";

            $provider_details = Provider::find($request->provider_id);

            $page_title = tr('view_spaces')." - ".$provider_details->name;

        }

        if($request->service_location_id) {

            $base_query = $base_query->where('service_location_id', $request->service_location_id);

            // $page = "service_locations"; $sub_page = "service_locations-view";

            $service_location_details = ServiceLocation::find($request->service_location_id);

            $page_title = tr('view_spaces')." - ".$service_location_details->name;

        } 

        if($request->unverified == YES) {

            $base_query = $base_query->whereIn('is_admin_verified', [ADMIN_SPACE_VERIFY_PENDING,ADMIN_SPACE_VERIFY_DECLINED]);

            $page_title = tr('unverified_spaces');

            $sub_page = "hosts-unverified";

        }

        if($request->search_key) {

            $search_key = $request->search_key;

            $base_query = $base_query 

                ->whereHas('providerDetails', function($q) use ($search_key) {

                    return $q->Where('providers.name','LIKE','%'.$search_key.'%');

                })->orWhereHas('serviceLocationDetails', function($q) use ($search_key) {

                    return $q->Where('service_locations.name','LIKE','%'.$search_key.'%')
                              ->orWhere('service_locations.address','LIKE','%'.$search_key.'%');

                })->orWhere('hosts.host_name','LIKE','%'.$search_key.'%');
        }

        $hosts = $base_query->paginate($this->paginate_count);

        foreach ($hosts as $key => $host_details) {

            // get provider name & image
            $host_details->provider_name = $host_details->providerDetails->username ?? '' ;
            $host_details->location = $host_details->serviceLocationDetails->name ?? '' ;

        }

        return view('admin.spaces.index')
                    ->with('page', $page)
                    ->with('sub_page', $sub_page)
                    ->with('page_title', $page_title)
                    ->with('hosts', $hosts);
    
    }

    /**
     * @method spaces_create()
     *
     * @uses To create host details
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param 
     * 
     * @return view page
     *
     */

    public function spaces_create() {
        
        $host_details = new Host;

        $host_types = Lookups::Approved()->where('type', 'host_type')->get();
        
        $host_owner_types = Lookups::Approved()->where('type', 'host_owner_type')->get();

        $providers = Provider::orderBy('name','asc')->get();

        $service_locations = ServiceLocation::orderby('name', 'asc')->get();

        foreach ($service_locations as $key => $service_location_details) {

            $service_location_details->is_selected = NO;
        }

        return view('admin.spaces.create')
                    ->with('page' , 'hosts')
                    ->with('sub_page','hosts-create')
                    ->with('host_types', $host_types)
                    ->with('host_owner_types', $host_owner_types)
                    ->with('service_locations', $service_locations)
                    ->with('providers', $providers)
                    ->with('host_details', $host_details)
                    ->with('amenitie_details', []);
    
    }

    /**
     * @method spaces_edit()
     *
     * @uses To display and update host details based on the host id
     *
     * @created Anjana
     *
     * @updated vithya
     *
     * @param object $request - host Id
     * 
     * @return redirect view page 
     *
     */
    
    public function spaces_edit(Request $request) {

        try {

            $host_details = Host::find($request->host_id);

            if(!$host_details) {

                return back()->with('flash_error', tr('host_not_found'));
            }

            $host_types = Lookups::Approved()->where('type', 'host_type')->get();

            foreach ($host_types as $key => $host_type_details) {

                $host_type_details->is_selected = NO;

                if($host_details->host_type == $host_type_details->key) {

                    $host_type_details->is_selected = YES;

                }
            }

            $host_owner_types = Lookups::Approved()->where('type', 'host_owner_type')->get();

            foreach ($host_owner_types as $key => $host_owner_type) {

                $host_owner_type->is_selected = NO;

                if($host_details->host_owner_type == $host_owner_type->key) {

                    $host_owner_type->is_selected = YES;

                }
            }

            $service_locations = ServiceLocation::orderby('name', 'asc')->get();

            foreach ($service_locations as $key => $service_location_details) {

                $service_location_details->is_selected = NO;

                if($host_details->service_location_id == $service_location_details->id) {

                    $service_location_details->is_selected = YES;

                }
            } 

            $providers = Provider::orderBy('name','asc')->get();

            foreach ($providers as $key => $provider) {

                $provider->is_selected = NO;

                if($host_details->provider_id == $provider->id) {

                    $provider->is_selected = YES;

                }
            } 


            $lookups = Lookups::Approved()->areAmenities()->where('type', $host_details->host_type)->select('id as amenity_lookup_id','key','value')->get();

            foreach ($lookups as $key => $lookup_details) {

                $lookup_details->is_selected = NO;

                if($host_details->amenities) {

                    $amenitie_ids = explode(',', $host_details->amenities);

                    $check_details = in_array($lookup_details->amenity_lookup_id, $amenitie_ids);

                    $lookup_details->is_selected = $check_details ? YES: NO;

                }
            }
            
            return view('admin.spaces.edit')
                        ->with('page', 'hosts')
                        ->with('sub_page', 'hosts-view')
                        ->with('host_details', $host_details)
                        ->with('host_types', $host_types)
                        ->with('host_owner_types', $host_owner_types)
                        ->with('service_locations', $service_locations)
                        ->with('providers', $providers)
                        ->with('amenitie_details',$lookups);


        } catch (Exception $e) {

            return redirect()->route('admin.spaces.index')->with('flash_error', $e->getMessage());
        }
    
    }

    /**
     * @method spaces_save()
     *
     * @uses To save/update the new/existing service locations object details
     *
     * @created Anjana H
     *
     * @updated Bhawya N
     *
     * @param integer (request) $service_location_id, service_location (request) details
     * 
     * @return success/failure message
     *
     */
    
    public function spaces_save(Request $request) {

        try {

            DB::beginTransaction();

            $rules = [
                'host_name' => 'required|max:191',
                'host_type' => 'required',
                'full_address' => 'required',
                'host_owner_type' => 'required',
                'total_spaces' => 'required|numeric|min:1',
                'access_method' => 'required',
                'access_note' => 'required',
                'description' => 'required',
                'picture' => 'mimes:jpeg,png,jpg,gif,svg|max:2048',
                'pictures.*' => 'mimes:jpeg,png,jpg,gif,svg|max:2048',
                'latitude' => 'required',
                'longitude' => 'required',
                'service_location_id' => 'required|exists:service_locations,id',
                'per_hour' => 'required|numeric|min:0',
                'per_day' => 'required|numeric|min:0',
                'per_month' => 'required|numeric|min:0',

                'width_of_space' => 'required|numeric|min:0',
                'height_of_space' => 'required|numeric|min:0',
                'length_of_space' => 'required|numeric|min:0',
                'is_automatic_booking' => 'required|in:'.YES.','.NO,
            ];

            $custom_errors = [
                'picture.max' => 'The :attribute should not be greater than 2048 KB',
                'pictures.*.max' => 'The :attribute should not be greater than 2048 KB'
            ];
            
            Helper::custom_validator($request->all(),$rules,$custom_errors);

            $host_details = new Host;

            if( $request->host_id != '') {

                $host_details = Host::find($request->host_id);

                $message = tr('host_updated_success');

            } else {
               
                $host_details->is_admin_verified = ADMIN_SPACE_VERIFIED;

                $host_details->admin_status = ADMIN_SPACE_APPROVED;

                $host_details->status = SPACE_OWNER_PUBLISHED;

                $host_details->uploaded_by = ADMIN;

                $host_details->unique_id = uniqid();

                $message = tr('host_created_success');

            }

            $host_details->provider_id = $request->provider_id ?: 0;

            $host_details->host_name = $request->host_name;

            $host_details->host_type = $request->host_type ?: "";

            $host_details->host_owner_type = $request->host_owner_type ?: "";

            $host_details->total_spaces = $request->total_spaces ?: "";

            $host_details->description = $request->description ?: "";

            $host_details->access_note = $request->access_note ?: "";

            $host_details->access_method = $request->access_method ?: "";

            $host_details->service_location_id = $request->service_location_id;

            $host_details->latitude = $request->latitude ?: "12.00000000";

            $host_details->longitude = $request->longitude ?: "86.00000000";

            $host_details->street_details = $request->street_details ?: "Bangalore";

            $host_details->city = $request->city ?: "Bangalore";

            $host_details->state = $request->state ?: "Bangalore";

            $host_details->full_address = $request->full_address ?: "Bangalore";

            $host_details->zipcode = $request->zipcode ?: "560102";

            $host_details->per_hour = $request->per_hour ?: 0;

            $host_details->per_day = $request->per_day ?: 0;

            $host_details->per_month = $request->per_month ?: 0; 

            $host_details->width_of_space = $request->width_of_space ?: 0;

            $host_details->height_of_space = $request->height_of_space ?: 0;

            $host_details->length_of_space = $request->length_of_space ?: 0;


            $host_details->dimension = ($host_details->width_of_space ?: 0)."Ft'W ".' *'.($host_details->length_of_space ?: 0)."Ft'L ".' *'.($host_details->height_of_space ?: 0)."Ft'H ";
          
            if( $request->host_id == '') {
            
                $host_details->picture = asset('placeholder.jpg');
            
            }

            if($request->amenities) {

                $amenitie_ids = implode(',', $request->amenities);
                
                $host_details->amenities = $amenitie_ids ?: '';

            }

            $host_details->is_automatic_booking = $request->is_automatic_booking ?? NO;

            if($request->hasFile('picture') ) {
                
                if($request->host_id != '') {

                    Helper::delete_file($host_details->picture, FILE_PATH_HOST); // Delete the old pic
                }

                $host_details->picture = Helper::upload_file($request->file('picture'), FILE_PATH_HOST);
            }

            if($host_details->save()) {

                $picture = [];
             
                if($request->hasFile('pictures')) {

                    foreach ($request->file('pictures') as $key => $value) {

                        // Save the host gallery pictures
                        $host_gallery = new HostGallery;

                        $host_gallery->picture = Helper::upload_file($value, FILE_PATH_HOST);
                       
                        $host_gallery->host_id = $host_details->id;

                        $host_gallery->status = SPACE_OWNER_PUBLISHED;
                       
                        $host_gallery->save();
               
                    }
             
                }
                
                DB::commit();

                return redirect()->route('admin.spaces.view', ['host_id' => $host_details->id])->with('flash_success',$message);
            }

            throw new Exception(tr('host_save_failed'), 101);
            
        } catch (Exception $e) {
            
            DB::rollback();

            return redirect()->back()->withInput()->with('flash_error', $e->getMessage());
        }

    }

    /**
     * @method spaces_view()
     *
     * @uses view the hosts details based on hosts id
     *
     * @created Anjana 
     *
     * @updated Anjana
     *
     * @param object $request - host Id
     * 
     * @return View page
     *
     */
    public function spaces_view(Request $request) {

        try {

            $rules = [

                'host_id' => 'required|exists:hosts,id',
            ];

            Helper::custom_validator($request->all(),$rules);
            
            // load host details based on host_id
            $host = Host::find($request->host_id);

            if(!$host) {

                throw new Exception(tr('host_not_found'), 101);   
            }

            // Load service location name
            $host->location_name = $host->serviceLocationDetails->name ?? '' ;

            // get provider name & image
            $host->provider_name = $host->providerDetails->name ?? '' ;

            $host->provider_image = $host->providerDetails->picture ?? '' ;
            
            // Load HostGallerie details based on host id
            $host_gallery = HostGallery::where('host_id', $request->host_id)->get();
            
            if($host->amenities) {

                $amenitie_ids = explode(',', $host->amenities);

                $amenities = Lookups::Approved()->areAmenities()->whereIn('id',$amenitie_ids)->get();

            }

            return view('admin.spaces.view')
                        ->with('page', 'hosts')
                        ->with('sub_page','hosts-view')
                        ->with('host' , $host)
                        ->with('host_gallery', $host_gallery)
                        ->with('amenities', $amenities ?? []);

       } catch(Exception $e) {

            return back()->with('flash_error', $e->getMessage());

        }
    
    }


    /**
     * @method spaces_delete
     *
     * @uses To delete the service locations details based on service location id
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param integer (request) $service_location_id
     * 
     * @return success/failure message
     *
     */
    public function spaces_delete(Request $request) {

        try {

            DB::beginTransaction();

            $host_details = Host::find($request->host_id);

            if(!$host_details) {

                throw new Exception(tr('host_not_found'), 101);                
            }

            if($host_details->delete() ) {

                DB::commit();

                // Delete relavant image

                if($host_details->picture !='' ) {

                        Helper::delete_file($host_details->picture, FILE_PATH_HOST); 
                }

                return redirect()->route('admin.spaces.index')->with('flash_success',tr('host_deleted_success')); 

            }

            throw new Exception(tr('host_delete_error'));
            
        } catch(Exception $e) {

            DB::rollback();

            return redirect()->route('admin.spaces.index')->with('flash_error', $e->getMessage());

        }
   
    }

    /**
     * @method spaces_status
     *
     * @uses To update host status as DECLINED/APPROVED based on host id
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param integer (request) $host_id
     * 
     * @return success/failure message
     *
     */
    public function spaces_status(Request $request) {

        try {

            DB::beginTransaction();
            
            $host_details = Host::find($request->host_id);

            if(!$host_details) {

                throw new Exception(tr('host_not_found'), 101);                
            }

            $host_details->admin_status = $host_details->admin_status ? DECLINED : APPROVED;

            if($host_details->save()) {

                DB::commit();

                $job_data['space_details'] = $host_details;

                $job_data['admin_id'] = Auth::guard('admin')->user()->id;
                
                $job_data['timezone'] = Auth::guard('admin')->user()->timezone;

                //Push Notification - Space Approve/Decline - To Provider
                if($host_details->admin_status == ADMIN_SPACE_PENDING) {

                    $this->dispatch(new SpaceDeclineJob($job_data));

                } else {

                    $this->dispatch(new SpaceApproveJob($job_data));

                }


                $message = $host_details->admin_status ? tr('host_approve_success') : tr('host_decline_success');

                return redirect()->back()->with('flash_success', $message);
            }

            throw new Exception(tr('host_status_change_failed'));
        } catch(Exception $e) {

            DB::rollback();

            return redirect()->route('admin.spaces.index')->with('flash_error', $e->getMessage());

        }

    }

    /**
     * @method spaces_verification_status
     *
     * @uses To change the host admin verification
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param integer $host_id
     * 
     * @return success/failure message
     *
     */
    public function spaces_verification_status(Request $request) {

        try {

            DB::beginTransaction();

            $host_details = Host::find($request->host_id);

            if(!$host_details) {

                throw new Exception(tr('host_not_found'), 101);                
            }
            
            $host_details->is_admin_verified = $host_details->is_admin_verified == ADMIN_SPACE_VERIFY_PENDING ? ADMIN_SPACE_VERIFIED : ADMIN_SPACE_VERIFY_DECLINED ;
            
            $host_details->save();

            DB::commit();

            $message = $host_details->is_admin_verified ? tr('host_admin_verified') : tr('host_admin_verification_declined');

            return redirect()->back()->with('flash_success', $message);

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->route('admin.spaces.index')->with('flash_error', $e->getMessage());

        }

    }

    /**
     * @method spaces_gallery_index()
     *
     * @uses To dispaly host gallery images
     *
     * @created Anjana
     *
     * @updated vithya
     *
     * @param 
     * 
     * @return return view page
     *
     */
    public function spaces_gallery_index(Request $request) {
        try {
           
            $host_details = Host::find($request->host_id);

            if(!$host_details) {

                throw new Exception(tr('host_not_found'), 101);                
            }

            $hosts_galleries = HostGallery::where('host_id', $request->host_id)->orderBy('updated_at','desc')->get();

            return view('admin.spaces.gallery')
                        ->with('page','spaces')
                        ->with('sub_page' , 'spaces-view')
                        ->with('host_details' , $host_details)
                        ->with('hosts_galleries' , $hosts_galleries);

            
        } catch (Exception $e) {
            
            return redirect()->route('admin.spaces.index')->with('flash_error', $e->getMessage());
        }
    }

    /**
     * @method spaces_gallery_save()
     *
     * @uses Save gallery images for the host
     *
     * @created Bhawya
     *
     * @updated Bhawya
     *
     * @param object $request - Host Id
     * 
     * @return response of success/failure details with view page
     *
     */
    public function spaces_gallery_save(Request $request) {

        try {

            DB::begintransaction();
            if($request->hasFile('pictures')) {

                $data = HostRepo::host_gallery_upload($request->file('pictures'), $request->host_id, $status = YES);

                DB::commit();
            }

            $host = Host::find($request->host_id);

            if(!$host) {

                throw new Exception(tr('host_not_found'), 101);                
            }
            
            $hosts_galleries = HostGallery::where('host_id', $request->host_id)->orderBy('updated_at','desc')->get();

            return redirect()->route('admin.spaces.gallery.index',['host_id' => $request->host_id])
                        ->with('page','spaces')
                        ->with('sub_page' , 'spaces-view')
                        ->with('host' , $host)
                        ->with('hosts_galleries' , $hosts_galleries);
            
        } catch(Exception $e){

            DB::rollback();

            return redirect()->back()->with('flash_error', $e->getMessage());

        }       
         
    }


    /**
     * @method spaces_gallery_delete()
     *
     * @uses delete the host gallery images based on gallery id
     *
     * @created Anjana
     *
     * @updated Anjana
     *
     * @param object $request - gallery Id
     * 
     * @return response of success/failure details with view page
     *
     */
    public function spaces_gallery_delete(Request $request) {

        try {

            DB::begintransaction();

            $gallery_details = HostGallery::find($request->gallery_id);
            
            if(!$gallery_details) {

                throw new Exception(tr('gallery_not_found'), 101);                
            }

            Helper::delete_file($gallery_details->picture, COMMON_FILE_PATH); 

            if($gallery_details->delete()) {
               
                DB::commit();

                return redirect()->back()->with('flash_success',tr('gallery_deleted_success'));   

            } 
            
            throw new Exception(tr('gallery_delete_failed'),101);
            
        } catch(Exception $e){

            DB::rollback();

            return redirect()->back()->with('flash_error', $e->getMessage());

        }       
         
    }

    /**
     * @method spaces_bulk_action()
     * 
     * @uses To delete,approve,decline multiple spaces
     *
     * @created Arun
     *
     * @updated 
     *
     * @param 
     *
     * @return success/failure message
     */
    public function spaces_bulk_action(Request $request) {

        try {

            $action_name = $request->action_name ;

            $space_ids = explode(',', $request->selected_spaces);

            if (!$space_ids && !$action_name) {

                throw new Exception(tr('space_action_is_empty'));

            }

            DB::beginTransaction();

            if($action_name == 'bulk_delete'){

                $space =  Host::whereIn('id', $space_ids)->delete();

                if ($space) {

                    DB::commit();

                    return back()->with('flash_success',tr('admin_spaces_delete_success'))->with('bulk_action','true');

                }

                throw new Exception(tr('spaces_delete_failed'));


            }elseif($action_name == 'bulk_approve'){

                $space =  Host::whereIn('id', $space_ids)->update(['status' => APPROVED]);

                if ($space) {

                    DB::commit();

                    return back()->with('flash_success',tr('admin_spaces_approve_success'))->with('bulk_action','true');

                }

                throw new Exception(tr('spaces_approve_failed'));

            }elseif($action_name == 'bulk_decline'){
                
                $space =  Host::whereIn('id', $space_ids)->update(['status' => DECLINED]);

                if ($space) {

                    DB::commit();

                    return back()->with('flash_success',tr('admin_spaces_decline_success'))->with('bulk_action','true');

                }

                throw new Exception(tr('spaces_decline_failed'));

            }

        } catch( Exception $e) {

            DB::rollback();

            return redirect()->back()->with('flash_error',$e->getMessage());
        }
    }

    /**
     * @method provider_redeems()
     *
     * @uses view provider redeems
     *
     * @created Bhawya
     *
     * @updated
     *
     * @param Integer $request - provider id
     * 
     * @return view page
     *
     **/
    public function provider_redeems(Request $request) {

        $base_query = ProviderRedeem::orderBy('updated_at','desc');


        if($request->search_key) {

            $search_key = $request->search_key;

            $base_query = $base_query 

                ->whereHas('providerDetails', function($q) use ($search_key) {

                    return $q->Where('providers.name','LIKE','%'.$search_key.'%');
                });
               
        }

        $provider_redeems = $base_query->paginate($this->paginate_count);

        foreach ($provider_redeems as $key => $provider_redeem_details) {

            $provider_redeem_details->provider_name = $provider_redeem_details->providerDetails->name ?? '' ;

            $provider_account_details = ProviderBillingInfo::where('provider_id', $provider_redeem_details->provider_id)->first();
            
            $provider_redeem_details->paynow_btn_status = $provider_account_details && $provider_account_details->paypal_email ? YES : NO;

            $provider_redeem_details->account_name = $provider_account_details->account_name ?? '-';

            $provider_redeem_details->account_no = $provider_account_details->account_no ?? '-';

            $provider_redeem_details->route_no = $provider_account_details->route_no ?? '-';

            $provider_redeem_details->paypal_email = $provider_account_details->paypal_email ?? '-';

            $provider_redeem_details->paid_date = common_date($provider_redeem_details->paid_date);

        }

        return view('admin.revenues.provider_redeems')
                    ->with('page', 'revenues')
                    ->with('sub_page','revenues-provider_redeems')
                    ->with('provider_redeems',$provider_redeems);
    
    }

    /**
     * @method provider_redeems_payment()
     *
     * @uses view provider redeems
     *
     * @created Bhawya
     *
     * @updated
     *
     * @param Integer $request - provider id
     * 
     * @return view page
     *
     **/
    public function provider_redeems_payment(Request $request) {

        try {

            DB::begintransaction();

            $rules = [

                'amount' => 'required|numeric|gt:0',
            ];

            Helper::custom_validator($request->all(),$rules);

            $provider_redeem_details = ProviderRedeem::find($request->provider_redeems_id);

            if(!$provider_redeem_details) {

                throw new Exception(api_error(229), 229);
                
            }

            if($provider_redeem_details->remaining_amount < $request->amount) {

                throw new Exception(api_error(230), 230);
                
            }

            $provider_redeem_details->paid_date = date('Y-m-d H:i:s');

            $provider_redeem_details->paid_amount += $request->amount;

            $provider_redeem_details->remaining_amount = $provider_redeem_details->remaining_amount - $request->amount;

            // $provider_redeem_details->total += $request->amount;
            
            if($provider_redeem_details->save()) {

                DB::commit();

                $job_data['provider_redeem_details'] = $provider_redeem_details;

                $job_data['timezone'] = Auth::guard('admin')->user()->timezone;

                $this->dispatch(new ProviderRedeemJob($job_data));        

                return redirect()->back()->with('flash_success', api_success(223));
            }

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->back()->with('flash_error', $e->getMessage());

        }
    
    }

    /**
     * @method user_refunds()
     *
     * @uses view provider redeems
     *
     * @created Bhawya
     *
     * @updated
     *
     * @param Integer $request - provider id
     * 
     * @return view page
     *
     **/
    public function user_refunds(Request $request) {

        $base_query = UserRefund::orderBy('updated_at','desc');


        if($request->search_key) {

            $search_key = $request->search_key;

            $base_query = $base_query 

                ->whereHas('userDetails', function($q) use ($search_key) {

                    return $q->Where('users.name','LIKE','%'.$search_key.'%');
                });
               
        }
        
        $user_refunds = $base_query->paginate($this->paginate_count);

        foreach ($user_refunds as $key => $user_refund_details) {

            $user_refund_details->user_name = $user_refund_details->userDetails->name ?? '' ;

            $user_account_details = UserBillingInfo::where('user_id',$user_refund_details->user_id)->first();
            
            $user_refund_details->paynow_btn_status = $user_account_details && $user_account_details->paypal_email ? YES : NO;

            $user_refund_details->account_name = $user_account_details->account_name ?? '-';

            $user_refund_details->account_no = $user_account_details->account_no ?? '-';

            $user_refund_details->route_no = $user_account_details->route_no ?? '-';

            $user_refund_details->paypal_email = $user_account_details->paypal_email ?? '-';

            $user_refund_details->paid_date = common_date($user_refund_details->paid_date);

        }

        return view('admin.revenues.user_refunds')
                    ->with('page', 'revenues')
                    ->with('sub_page','revenues-user_refunds')
                    ->with('user_refunds',$user_refunds);
    
    }

    /**
     * @method user_refunds_payment()
     *
     * @uses view provider redeems
     *
     * @created Bhawya
     *
     * @updated
     *
     * @param Integer $request - provider id
     * 
     * @return view page
     *
     **/
    public function user_refunds_payment(Request $request) {

        try {

            DB::beginTransaction();

            $rules = [
                'amount' => 'required|numeric|gt:0',
            ];

            Helper::custom_validator($request->all(),$rules);

            $user_refund_details = UserRefund::find($request->user_refund_id);

            if(!$user_refund_details) {

                throw new Exception(api_error(231), 231);
                
            }
            
            $user_refund_details->paid_date = date('Y-m-d H:i:s');

            $user_refund_details->paid_amount += $request->amount;

            $user_refund_details->remaining_amount = $user_refund_details->total - $user_refund_details->paid_amount;

            if($user_refund_details->save()) {

                DB::commit();

                $job_data['user_refund_details'] = $user_refund_details;

                $job_data['timezone'] = Auth::guard('admin')->user()->timezone;

                $this->dispatch(new UserRefundJob($job_data));

                return redirect()->back()->with('flash_success', api_success(224));
            }

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->back()->with('flash_error', $e->getMessage());

        }
    
    }

    /**
     * @method spaces_availability_create()
     *
     * @uses add hosts availability view
     *
     * @created Anjana H 
     *
     * @updated Anjana H
     *
     * @param object $request - host Id
     * 
     * @return View page
     *
     */
    public function spaces_availability_create(Request $request) {

        try {

            $host_details = Host::find($request->host_id);

            if(!$host_details) {

                throw new Exception(tr('host_not_found'), 101);   
            }

            $available_days = Helper::get_week_days($days = "", $host_details->available_days); 

            /** get the current host availability details based on the host id **/
            $hosts_availability_list = HostAvailabilityList::where('host_id', $request->host_id)->get();
        
            $available_days = (object)$available_days;
                
            return view('admin.spaces.availability_create')
                        ->with('page', 'hosts')
                        ->with('sub_page','hosts-view')
                        ->with('host_details' , $host_details)
                        ->with('available_days' , $available_days)
                        ->with('hosts_availability_list' , $hosts_availability_list);

        } catch(Exception $e) {

            return back()->with('flash_error', $e->getMessage());

        }       
    }    

    /**
     * @method spaces_availability_save 
     *
     * @uses create availability list
     *
     * @created Anjana H 
     *
     * @updated Anjana H
     *
     * @param
     *
     * @return 
     */  
    
    public function spaces_availability_save(Request $request) {

        try {

            $today = date('Y-m-d H:i:s');

            /** Formate from_date and to_date dates **/
            $from_date = common_date($request->from_date, $this->timezone ,'Y-m-d H:i:s');

            $to_date = common_date($request->to_date, $this->timezone ,'Y-m-d H:i:s');

            $request->request->add(['from_date' => $from_date, 'to_date' => $to_date]);

            $rules = [
                'available_days' => '',
                'from_date' => 'date',
                'to_date' => 'required_if:from_date,|date|after:from_date',
                'spaces' => 'required|min:0',
                'type' => 'required'
            ];

            Helper::custom_validator($request->all(),$rules);

            DB::beginTransaction();

            $host_details = Host::find($request->host_id);

            if(!$host_details) {

                throw new Exception(tr('host_not_found'), 101);   
            }

            if($request->has('available_days')) {
                
                $host_details->available_days = $request->available_days;
                
                $host_details->save();
            }

            /** check Host Availability List exists based on from_date **/
            
            $host_availablity = HostAvailabilityList::where('host_id', $request->host_id)->where('from_date',$request->from_date)->first() ?? new HostAvailabilityList;

            $host_availablity->host_id = $request->host_id ?? $host_availablity->host_id;

            $host_availablity->provider_id = $host_details->provider_id ?? $host_availablity->provider_id;

            $host_availablity->from_date = $request->from_date ?? $host_availablity->from_date;

            $host_availablity->to_date = $request->to_date ?? $host_availablity->to_date;

            $host_availablity->type = $request->type ?? $host_availablity->type;
            
            $host_availablity->spaces = $request->spaces ?? $host_availablity->spaces;
            
            if($host_availablity->save()) { 

                DB::commit();
                
                $message = tr('availability_add_success');
                
                return redirect()->route('admin.spaces.availability.create', ['host_id' => $host_details->id])->with('flash_success', $message);
            }

            throw new Exception(tr('availability_delete_error'), 101);            
            
        } catch(Exception $e) {

            DB::rollback();

            return back()->with('flash_error', $e->getMessage());

        }

    }    
    
    /**
     * @method spaces_availability_delete 
     *
     * @uses delete availability list
     *
     * @created Anjana H 
     *
     * @updated Anjana H
     *
     * @param
     *
     * @return 
     */  
    public function spaces_availability_delete(Request $request) {

        try {

            DB::beginTransaction();

            $host_details = Host::find($request->host_id);

            if(!$host_details) {

                throw new Exception(tr('host_not_found'), 101);   
            }

            $hosts_availability_list = HostAvailabilityList::find($request->hosts_availability_list_id);
            
            if(!$hosts_availability_list) {

                throw new Exception(tr('hosts_availability_list_not_found'), 101);   
            }

            if($hosts_availability_list->delete()){

                DB::commit();
                
                $message = tr('availability_delete_success');
                
                return back()->with('flash_success', $message);
                
            }

        } catch(Exception $e) {

            DB::rollback();

            return back()->with('flash_error', $e->getMessage());

        }

    }

    /**
     * @method amenities_index
     *
     * @uses Get the amenities list
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param 
     * 
     * @return view page
     *
     */
    public function amenities_index(Request $request) {

        $base_query = Lookups::areAmenities()->orderBy('updated_at','desc');

        if($request->search_key) {

            $base_query->where(function ($query) use ($request) {
                $query->where('value', "like", "%" . $request->search_key . "%");
                $query->orWhere('type', "like", "%" . $request->search_key . "%");
            });
        }
        
        $amenities = $base_query->paginate($this->paginate_count);

        return view('admin.amenities.index')
                    ->with('page' , 'amenities')
                    ->with('sub_page','amenities-view')
                    ->with('amenities' , $amenities);
    }

    /**
     * @method amenities_create
     *
     * @uses To create amenity details
     *
     * @created Anjana H
     *
     * @updated Anjana H 
     *
     * @param 
     * 
     * @return view page
     *
     */
    public function amenities_create() {

        $amenity_details = new Lookups;

        /** get Host type and type from lookup **/
        $host_types = Lookups::Approved()->where('type', 'host_type')->get();
        
        return view('admin.amenities.create')
                ->with('page' , 'amenities')
                ->with('sub_page','amenities-create')
                ->with('host_types', $host_types)    
                ->with('amenity_details', $amenity_details);    
    }
  
    /**
     * @method amenities_edit()
     *
     * @uses To display and update amenity details based on the amenity id
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param object $request - amenity Id
     * 
     * @return redirect view page 
     *
     */
    public function amenities_edit(Request $request){

        try {
      
            $amenity_details = Lookups::find($request->amenity_id);
           
            $host_types = selected(Lookups::Approved()->where('type', 'host_type')->get(), $amenity_details->type, 'key');

            if(!$amenity_details) {

                return redirect()->route('admin.amenities.index')->with('flash_error',tr('amenity_not_found'));
            }

            return view('admin.amenities.edit')
                        ->with('page','amenities')
                        ->with('sub_page','amenities-view')
                        ->with('host_types',$host_types)
                        ->with('amenity_details',$amenity_details);
            
        } catch (Exception $e) {

            return redirect()->back()->with('flash_error', $e->getMessage());
        }    
    }

    /**
     * @method amenities_save
     *
     * @uses To save the amenity details 
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param object $request - amenity details,amenity id
     * 
     * @return response of success/failure response details
     *
     */    
    public function amenities_save(Request $request) {

        try {

            DB::beginTransaction();

            $rules = [
                'type' => 'required|max:191',
                'value' => 'required|max:191',
                'picture' => 'mimes:jpg,png,jpeg',
            ];
            
            Helper::custom_validator($request->all(),$rules);
            
            $amenity_details = new Lookups;

            $message = tr('amenity_created_success');

            if($request->amenity_id != '') {

                $amenity_details = Lookups::find($request->amenity_id);

                $message = tr('amenity_updated_success');

            } else {
               
                $amenity_details->status = APPROVED;
                
                $amenity_details->is_amenity = YES;
            }

            $amenity_details->value = $request->value ?: $amenity_details->value;
            
            $amenity_details->key = str_replace(' ', '_', $request->value) ?: $amenity_details->key;

            $amenity_details->type = $request->type ?: $amenity_details->type; 

            if($request->hasFile('picture')) {

                if($request->amenity_id){

                    //Delete the old picture located in amenities file
                    Helper::delete_file($amenity_details->picture,FILE_PATH_AMENITIES);
                }

                $amenity_details->picture = Helper:: upload_file($request->file('picture'), FILE_PATH_AMENITIES);

            }

            if($amenity_details->save()) {

                DB::commit();

                return redirect()->route('admin.amenities.view', ['amenity_id' => $amenity_details->id])->with('flash_success', $message);

            }

            return back()->with('flash_error', tr('amenity_save_failed'));           

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->back()->withInput()->with('flash_error', $e->getMessage());

        }
        
    }

    /**
     * @method amenities_view
     *
     * @uses view the selected lookup details 
     *
     * @created Anjana H
     *
     * @updated Anjana
     *
     * @param integer $amenity_id
     * 
     * @return view page
     *
     */
    public function amenities_view(Request $request) {

        $amenity_details = Lookups::find($request->amenity_id);

        if(!$amenity_details) {

            return redirect()->route('admin.amenities.index')->with('flash_error',tr('amenity_not_found'));
        }
       
        return view('admin.amenities.view')
                    ->with('page', 'amenities')
                    ->with('sub_page','amenities-view')
                    ->with('amenity_details' , $amenity_details);
    
    }

    /**
     * @method amenities_delete
     *
     * @uses To delete the lookup details based on lookup id
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param integer $amenity_id
     * 
     * @return response of success/failure details
     *
     */
    public function amenities_delete(Request $request) {

        try {

            DB::beginTransaction();

            $amenities_details = Lookups::find($request->amenity_id);

            if(!$amenities_details) {

                throw new Exception(tr('amenity_not_found'), 101);
                
            }

            if($amenities_details->delete()) {

                DB::commit();

                return redirect()->route('admin.amenities.index')->with('flash_success',tr('amenity_deleted_success')); 
            } 

            throw new Exception(tr('amenity`_delete_failed'));
            
        } catch(Exception $e) {

            DB::rollback();

            return redirect()->route('admin.amenities.index')->with('flash_error', $e->getMessage());

        }
   
    }

    /**
     * @method amenities_status
     *
     * @uses To update amenity status as DECLINED/APPROVED based on amenity id
     *
     * @created anjana
     *
     * @updated vithya
     *
     * @param integer $amenity_id
     * 
     * @return response success/failure message
     *
     */
    public function amenities_status(Request $request) {

        try {

            DB::beginTransaction();

            $amenity_details = Lookups::find($request->amenity_id);

            if(!$amenity_details) {

                throw new Exception(tr('amenity_not_found'), 101);
                
            }

            $amenity_details->status = $amenity_details->status ? DECLINED : APPROVED;

            if($amenity_details->save()) {

                DB::commit();

                $message = $amenity_details->status ? tr('amenity_approve_success') : tr('amenity_decline_success');

                return redirect()->back()->with('flash_success', $message);
            }

            throw new Exception(tr('amenity_status_change_failed'));

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->back()->with('flash_error', $e->getMessage());

        }

    }

    /**
     * @method amenities_bulk_action()
     * 
     * @uses To delete,approve,decline multiple amenities
     *
     * @created Arun
     *
     * @updated 
     *
     * @param 
     *
     * @return success/failure message
     */
    public function amenities_bulk_action(Request $request) {

        try {

            $action_name = $request->action_name ;

            $amenitiy_ids = explode(',', $request->selected_amenities);

            if (!$amenitiy_ids && !$action_name) {

                throw new Exception(tr('amenities_action_is_empty'));

            }

            DB::beginTransaction();

            if($action_name == 'bulk_delete'){

                $amenitiy = Lookups::whereIn('id', $amenitiy_ids)->delete();

                if ($amenitiy) {

                    DB::commit();

                    return back()->with('flash_success',tr('admin_amenities_delete_success'))->with('bulk_action','true');

                }

                throw new Exception(tr('amenities_delete_failed'));


            }elseif($action_name == 'bulk_approve'){

                $amenitiy =  Lookups::whereIn('id', $amenitiy_ids)->update(['status' => APPROVED]);

                if ($amenitiy) {

                    DB::commit();

                    return back()->with('flash_success',tr('admin_amenities_approve_success'))->with('bulk_action','true');

                }

                throw new Exception(tr('amenities_approve_failed'));

            }elseif($action_name == 'bulk_decline'){
                
                $amenitiy =  Lookups::whereIn('id', $amenitiy_ids)->update(['status' => DECLINED]);

                if ($amenitiy) {

                    DB::commit();

                    return back()->with('flash_success',tr('admin_amenities_decline_success'))->with('bulk_action','true');

                }

                throw new Exception(tr('amenities_decline_failed'));
            }

        } catch( Exception $e) {

            DB::rollback();

            return redirect()->back()->with('flash_error',$e->getMessage());
        }


    }

    /**
     * @method ios_control
     *
     * @uses Payment option enable and disable the ios app. For using app store update.
     *
     * @created Maheswari
     *
     * @update Maheswari
     *
     * @return Html view page
     *
     */

    public function ios_control(){

        return view('admin.settings.ios-control')->with('page',tr('admin_control'));
    }

    /**
     * @method test
     *
     * @uses Payment option enable and disable the ios app. For using app store update.
     *
     * @created Maheswari
     *
     * @update Maheswari
     *
     * @return Html view page
     *
     */

    public function test(){

        $host = Host::find(111);

        $provider_details = $host->providerDetails;

        return view('admin.test')->with('host', $host)->with('provider_details', $provider_details);
    }

     /**
     * @method providers_document_verify_status()
     *
     * @uses Used to convert the provider into verified provider
     *
     * @created Akshata
     *
     * @updated 
     *
     * @param request $provider_id
     * 
     * @return response success/failure message
     *
     **/

    public function providers_document_verify_status(Request $request) {

        try {

            DB::beginTransaction();

            $provider_details = Provider::find($request->provider_id);

            if(!$provider_details) {

                throw new Exception(tr('provider_not_found'), 101);                
            }

            if($request->status == APPROVE_ALL) {

                ProviderDocument::where('provider_id', $request->provider_id)->update(['status' => APPROVED]);

                $provider_details->is_document_verified = PROVIDER_DOCUMENT_VERIFIED;

            } else if($request->status == DECLINE_ALL) {

                ProviderDocument::where('provider_id', $request->provider_id)->update(['status' => DECLINED]);

                $provider_details->is_document_verified = PROVIDER_DOCUMENT_UPLOADED;

            } else {

                if($provider_details->is_document_verified !=  PROVIDER_DOCUMENT_VERIFIED) {

                    $provider_details->is_document_verified = PROVIDER_DOCUMENT_VERIFIED;

                } else {

                    throw new Exception(tr('provider_already_verified'), 101);
                }
            }

            if($provider_details->save()) {

                DB::commit();

                // Email & Push Notification for Documents Verify
                $job_data['provider_details'] = $provider_details;

                $job_data['timezone'] = $this->timezone;

                $this->dispatch(new ProvidersDocumentVerifyJob($job_data));

                $message = $provider_details->is_document_verified == PROVIDER_DOCUMENT_VERIFIED ? tr('provider_verify_done') : tr('provider_unverify_done');

                return redirect()->back()->with('flash_success', $message);
            }
            
            throw new Exception(tr('provider_verify_failed'));

        } catch(Exception $e) {

            DB::rollback();

            return redirect()->back()->with('flash_error', $e->getMessage());

        }

    }

    /**
     * @method custom_push()
     * 
     * @uses to display/update Custom Push notification
     * 
     * @created Arun
     *
     * @updated Arun
     *
     * @param 
     *
     * @return view page
     */
    public function custom_push() {

        $is_push_enabled = Setting::get('is_push_notification') == YES;

        return view('admin.settings.push')
                    ->with('page' , "custom-push")
                    ->with('sub_page' , "push-view")
                    ->with('is_push_enabled' , $is_push_enabled);

    }

    /**
     * @method custom_push_save()
     * 
     * @uses to save Custom Push notification
     * 
     * @created Arun
     *
     * @updated Arun
     *
     * @param $request details
     *
     * @return success/failure message
     */
    public function custom_push_save(Request $request) {

        try {

            $rules = [
                'message' => 'required',
            ];
            
            Helper::custom_validator($request->all(),$rules);

            $message = $request->message;

            $title = Setting::get('site_name');

            $success_message = "<h4>".tr('admin_push_notification_success')."</h4>";

            $device_type = [DEVICE_ANDROID,DEVICE_IOS];

            $user_device_tokens = User::VerifiedUser()->where('device_token' , '!=' , "")->where('push_notification_status' , YES);

            $provider_device_tokens = Provider::VerifiedProvider()->where('device_token' , '!=' , "")->where('push_notification_status' , YES);

            if ($request->device_type == 'both') {

                $user_device_tokens = $user_device_tokens->whereIN('device_type',$device_type)->pluck('device_token')->toArray();

                $provider_device_tokens = $provider_device_tokens->whereIN('device_type',$device_type)->pluck('device_token')->toArray();

            }
            else{

                $user_device_tokens = $user_device_tokens->where('device_type' , $request->device_type)->pluck('device_token')->toArray();

                $provider_device_tokens = $provider_device_tokens->where('device_type' , $request->device_type)->pluck('device_token')->toArray();
                
            }


            if ($request->custom_push_type == USER) {

                $device_tokens = $user_device_tokens;

                $total_users = count($device_tokens);
                
                $success_message .= "<br> <p>Total Users: $total_users</p>";
                
            }
            elseif ($request->custom_push_type == PROVIDER) {
            
                $device_tokens = $provider_device_tokens;

                $total_providers = count($device_tokens);

                $success_message .= "<br> <p>Total Providers: $total_providers</p>";
            }
            else{

                $device_tokens = array_merge($user_device_tokens,$provider_device_tokens);

                $total_users = count($user_device_tokens);

                $total_providers = count($provider_device_tokens);

                $success_message .= "<br> <p>Total Users: $total_users</p>";

                $success_message .= "<br> <p>Total Providers: $total_providers</p>";

            }

            if($device_tokens) {

                // If user - YES, Provider - NO
                fcm_config_update(YES);

                \Notification::send($device_tokens, new PushNotification($title , $message, [], $device_tokens));

                return back()->with('flash_success', $success_message);

            }

            throw new Exception(tr('no_device'));
       
        } catch (Exception $e) {

            return back()->with('flash_error',$e->getMessage());
        }
    }

    /**
     * @method bookings_cancel()
     *
     * @uses cancel the bookings based on bookings id
     *
     * @created Jeevan 
     *
     * @updated Jeevan
     *
     * @param object $request - booking Id
     * 
     * success/failure message
     *
     */
    public function bookings_cancel(Request $request) {

        try {

            $booking_details = Booking::find($request->booking_id);
           
            if(!$booking_details) {

                throw new Exception(tr('booking_not_found'), 101);   
            }

            DB::beginTransaction();

            $booking_details->status = BOOKING_CANCELLED_BY_ADMIN;

            $booking_details->cancelled_reason = $request->cancelled_reason ?: "";

            $booking_details->cancelled_date = date('Y-m-d H:i:s');

            if($booking_details->save()) {

                // Reduce the provider amount from provider redeems
                BookingRepo::revert_provider_redeems($booking_details);

                // Add refund amount to the user
                BookingRepo::add_user_refund($booking_details);

                BookingRepo::bookings_cancel_revert_availability($booking_details);

                DB::commit();

                $job_data['booking_details'] = $booking_details;
                
                $job_data['timezone'] = $this->timezone;
                
                $this->dispatch(new AdminBookingCancelJob($job_data));

                return back()->with('flash_success', tr('admin_cancel_success'));      

            } else {
                
                 return back()->with('flash_danger', tr('admin_booking_cancel_failed'));

            }

        } catch(Exception $e) {

             return back()->with('flash_error',$e->getMessage());
        }

    }

}