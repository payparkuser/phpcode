<table>
    <thead>
        
        <tr>

            <th> {{tr('id')}} </th>

            <th> {{tr('booking_id')}} </th>

            <th> {{tr('user')}} </th>

            <th> {{tr('provider')}} </th>

            <th> {{tr('host')}} </th>

            <th> {{tr('payment_id')}} </th>

            <th> {{tr('payment_mode')}} </th>

            <!-- <th> {{tr('currency')}} </th> -->

            <th> {{tr('total_time')}} </th>

            <th> {{tr('per_day')}} </th>

            <th> {{tr('per_month')}} </th>

            <th> {{tr('sub_total')}} </th>

            <th> {{tr('total')}} </th>

            <th> {{tr('paid_amount')}} </th>

            <th> {{tr('paid_date')}} </th>

            <th> {{tr('admin_amount')}} </th>

            <th> {{tr('provider_amount')}} </th>

            <th> {{tr('status')}} </th>

            <th> {{tr('created_at')}} </th>

            <th> {{tr('updated_at')}} </th>

        </tr>

    </thead>
   
    <tbody>
       
        @foreach($booking_payments as $i => $booking_payments_details)

        <tr>
            <td> {{$booking_payments_details->id}} </td>

            <td> #{{$booking_payments_details->bookingDetails->unique_id ?? ''}} </td>

            <td> {{$booking_payments_details->userDetails->name ?? tr('user_not_avail')}} </td>

            <td> {{ $booking_payments_details->providerDetails->name ?? tr('provider_not_avail')}} </td>
           
            <td> {{ $booking_payments_details->hostDetails->host_name ?? tr('host_not_avail')}} </td>

            <td> {{$booking_payments_details->payment_id}} </td>

            <td> {{$booking_payments_details->payment_mode}} </td>

            <!-- <td> {{$booking_payments_details->currency}} </td> -->

            <td> {{$booking_payments_details->bookingDetails->duration ?? ''}} </td>

            <td> {{$booking_payments_details->bookingDetails->per_day ?? '0'}} </td>

            <td> {{$booking_payments_details->bookingDetails->per_month ?? '0'}} </td>

            <td> {{$booking_payments_details->actual_total}} </td>

            <td> {{formatted_amount($booking_payments_details->total)}} </td>

            <td> {{$booking_payments_details->paid_amount}} </td>

            <td> {{common_date($booking_payments_details->paid_date,Auth::guard('admin')->user()->timezone)}} </td>

            <td> {{formatted_amount($booking_payments_details->admin_amount)}}</td>

            <td> {{$booking_payments_details->provider_amount}} </td>

            <td>
                @if($booking_payments_details->status == PAID_STATUS)
                    {{ tr('paid')}}
                @endif
            </td>

            <td> {{common_date($booking_payments_details->created_at,Auth::guard('admin')->user()->timezone)}}  </td>

            <td> {{common_date($booking_payments_details->updated_at,Auth::guard('admin')->user()->timezone)}} </td>

        </tr>
        
        @endforeach

    </tbody>

</table>
