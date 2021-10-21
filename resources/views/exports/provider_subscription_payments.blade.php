<table>
    <thead>
        
        <tr>
            <th> {{tr('id')}} </th>

            <th> {{tr('provider')}} </th>

            <th> {{tr('subscription')}} </th>

            <th> {{tr('payment_id')}} </th>

            <th> {{tr('payment_mode')}} </th>

            <th> {{tr('paid_date')}} </th>

            <th> {{tr('expiry_date')}} </th>

            <th> {{tr('subscription_amount')}} </th>

            <th> {{tr('paid_amount')}} </th>

            <th> {{tr('status')}} </th>

            <th> {{tr('is_current_subscription')}} </th>

            <th> {{tr('is_cancelled')}} </th>

            <th> {{tr('cancelled_reason')}} </th>

            <th> {{tr('subscribed_by')}} </th>

            <th> {{tr('created_at')}} </th>

            <th> {{tr('updated_at')}} </th>  
        </tr>

    </thead>
   
    <tbody>
       
        @foreach($provider_subscription_payments as $i => $provider_subscription_payment_details)
        
        <tr>
            <td> {{$provider_subscription_payment_details->id}} </td>

            <td> {{$provider_subscription_payment_details->providerDetails->name ?? tr('provider_not_avail')}} </td>

            <td> {{ $provider_subscription_payment_details->providerSubscriptionDetails->title ?? tr('provider_subscription_not_avail') }} </td>

            <td> {{$provider_subscription_payment_details->payment_id}} </td>

            <td> {{$provider_subscription_payment_details->payment_mode}} </td>

            <td> {{common_date($provider_subscription_payment_details->paid_date,Auth::guard('admin')->user()->timezone)}} </td>

            <td> {{common_date($provider_subscription_payment_details->expiry_date,Auth::guard('admin')->user()->timezone)}} </td>

            <td> {{ formatted_amount($provider_subscription_payment_details->paid_amount) }} </td>

            <td> {{$provider_subscription_payment_details->paid_amount}} </td>

            <td>
                @if($provider_subscription_payment_details->status) 
                    {{tr('paid')}}
                @else
                    {{ tr('not_paid')}}
                @endif

            </td>

            <td> 
                @if($provider_subscription_payment_details->is_current_subscription) 
                   {{ tr('yes') }}
                @else
                   {{ tr('no') }}
                @endif
                
            </td>

            <td> 
                @if($provider_subscription_payment_details->is_cancelled) 
                    {{ tr('yes') }}
                @else
                    {{ tr('no') }}
                @endif 
            </td>

            <td> {{$provider_subscription_payment_details->cancelled_reason}} </td>

            <td> {{$provider_subscription_payment_details->subscribed_by}} </td>

            <td> {{common_date($provider_subscription_payment_details->created_at, Auth::guard('admin')->user()->timezone)}} </td>

            <td> {{common_date($provider_subscription_payment_details->updated_at, Auth::guard('admin')->user()->timezone)}} </td>    
        </tr>

        @endforeach

    </tbody>

</table>
