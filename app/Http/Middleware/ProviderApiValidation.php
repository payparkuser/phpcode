<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Http\Request;

use App\Helpers\Helper;

use Validator;

use Log;

use App\Provider;

use DB;

use Setting;

class ProviderApiValidation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $validator = Validator::make($request->all(),
                [
                        'token' => 'required|min:5',
                        'id' => 'required|integer|exists:providers,id'
                ],[
                    'exists' => api_error(1006),
                    'id' => api_error(1005)
                ]);

        if ($validator->fails()) {

            $error = implode(',', $validator->messages()->all());

            $response = ['success' => false, 'error' => $error , 'error_code' => 1006];

            return response()->json($response,200);

        } else {

            $token = $request->token;

            $provider_id = $request->id;

            if (!Helper::is_token_valid(PROVIDER, $provider_id, $token, $error)) {

                $response = response()->json($error, 200);
                
                return $response;

            } else {

                $provider_details = Provider::IsNotDeleted()->find($request->id);

                if(!$provider_details) {
                    
                    $response = ['success' => false, 'error' => api_error(1006) , 'error_code' => 1006];

                    return response()->json($response,200);

                }

                // FOR DOCUMENT UPLOAD, WE NO NEED TO HANDLE THIS

                // Bhawya - Commented.
                // Handled middleware only on the time of Add Space. provider has to check verified or not. All other routes can be accessed by provider without verification.

                /*if(in_array($provider_details->status , [PROVIDER_DECLINED , PROVIDER_PENDING])) {
                    
                    $response = ['success' => false , 'error' => api_error(1000) , 'error_code' => 1000];

                    return response()->json($response, 200);
               
                }*/

                if($provider_details->is_verified == PROVIDER_EMAIL_NOT_VERIFIED) {

                    if(Setting::get('is_account_email_verification') && !in_array($provider_details->login_by, ['facebook' , 'google'])) {

                        // Check the verification code expiry

                        Helper::check_email_verification("" , $provider_details, $error, PROVIDER);
                    
                        $response = ['success' => false , 'error' => api_error(1001) , 'error_code' => 1001];

                        return response()->json($response, 200);

                    }
                
                }
            }
       
        }

        return $next($request);
    }
}
