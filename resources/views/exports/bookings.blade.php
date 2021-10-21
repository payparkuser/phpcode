<table>
    <thead>
        <tr>
            <th> {{tr('id')}} </th>

            <th> {{tr('unique_id')}} </th>

            <th> {{tr('user')}} </th>

            <th> {{tr('provider')}} </th>

            <th> {{tr('host')}} </th>

            <th> {{tr('checkin')}} </th>

            <th> {{tr('checkout')}} </th>

            <th> {{tr('total_days')}} </th>

            <th> {{tr('per_day')}} </th>

            <th> {{tr('total')}} </th>

            <th> {{tr('payment_mode')}} </th>

            <th> {{tr('status')}} </th>

            <th> {{tr('cancelled_reason')}} </th>

            <th> {{tr('cancelled_date')}} </th>

            <th> {{tr('duration')}} </th>

            <th> {{tr('per_hour')}} </th>

            <th> {{tr('per_month')}} </th>

            <th> {{tr('vehicle_number')}} </th>

            <th> {{tr('vehicle_type')}} </th>

        </tr>

    </thead>
   
    <tbody>
       
        @foreach($bookings as $i => $booking_details)

        <tr>
            <td> {{$booking_details->id}} </td>

            <td> {{$booking_details->unique_id}} </td>

            <td> {{$booking_details->userDetails->name ?? tr('user_not_avail')}} </td>

            <td> {{ $booking_details->providerDetails->name ?? tr('provider_not_avail')}} </td>
            
            <td> {{ $booking_details->hostDetails->host_name ?? tr('host_not_avail')}} </td>

            <td> {{$booking_details->checkin}} </td>

            <td> {{$booking_details->checkout}} </td>

            <td> {{$booking_details->total_days}} </td>

            <td> {{formatted_amount($booking_details->per_day)}} </td>

            <td> {{formatted_amount($booking_details->total)}} </td>

            <td> {{$booking_details->payment_mode}} </td>

            <td> {{ booking_status( $booking_details->status) }} </td>

            <td> {{$booking_details->cancelled_reason}} </td>

            <td> {{$booking_details->cancelled_date}} </td>

            <td> {{$booking_details->duration}} </td>

            <td> {{$booking_details->per_hour}} </td>

            <td> {{$booking_details->per_month}} </td>

            <td> {{$booking_details->bookingUserVehicle->vehicle_number ?? ''}} </td>

            <td> {{$booking_details->bookingUserVehicle->vehicle_type ?? ''}} </td>

        </tr>
        
        @endforeach

    </tbody>

</table>
