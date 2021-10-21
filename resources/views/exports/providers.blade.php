<table>
    <thead>
    <tr>
        <th> {{tr('id')}} </th>
        <th> {{tr('name')}} </th>
        <th> {{tr('email')}} </th>
        <th> {{tr('description')}} </th>
        <th> {{tr('mobile')}} </th>
        <th> {{tr('picture')}} </th>
        <th> {{tr('device_type')}} </th>
        <th> {{tr('register_type')}} </th>
        <th> {{tr('login_by')}} </th>
        <th> {{tr('latitude')}} </th>
        <th> {{tr('longitude')}} </th>
        <th> {{tr('full_address')}} </th>
        <th> {{tr('street_details')}} </th>
        <th> {{tr('city')}} </th>
        <th> {{tr('state')}} </th>
        <th> {{tr('zipcode')}} </th>
        <th> {{tr('payment_mode')}} </th>
        <th> {{tr('timezone')}} </th>
        <th> {{tr('is_verified')}} </th>
        <th> {{tr('status')}} </th>
        <th> {{tr('created_at')}} </th>
        <th> {{tr('updated_at')}} </th>
        <th> {{tr('identity_verification_file')}} </th>
        <th> {{tr('paypal_email')}} </th> 
    </tr>
    </thead>
   
    <tbody>
       
        @foreach($providers as $i => $provider_details)

            <tr>
                <td> {{$provider_details->id}} </td>
                <td> {{$provider_details->name}} </td>
                <td> {{$provider_details->email}} </td>
                <td> {{$provider_details->description}} </td>
                <td> {{$provider_details->mobile}} </td>
                <td> {{$provider_details->picture}} </td>
                <td> {{$provider_details->device_type}} </td>
                <td> {{$provider_details->register_type}} </td>
                <td> {{$provider_details->login_by}} </td>
                <td> {{$provider_details->latitude}} </td>
                <td> {{$provider_details->longitude}} </td>
                <td> {{$provider_details->full_address}} </td>
                <td> {{$provider_details->street_details}} </td>
                <td> {{$provider_details->city}} </td>
                <td> {{$provider_details->state}} </td>
                <td> {{$provider_details->zipcode}} </td>
                <td> {{$provider_details->payment_mode}} </td>
                <td> {{$provider_details->timezone}} </td>
                <td> 
                    @if($provider_details->is_verified == PROVIDER_EMAIL_VERIFIED)
                        {{ tr('verified') }}
                    @else
                        {{ tr('verify') }}
                    @endif
                </td>
                <td>  
                    @if($provider_details->status == PROVIDER_APPROVED)
                        {{ tr('approved') }} 
                    @else
                        {{ tr('declined') }} 
                    @endif
                </td>
                <td> {{$provider_details->created_at}} </td>
                <td> {{$provider_details->updated_at}} </td>
                <td> {{$provider_details->identity_verification_file}} </td>
                <td> {{$provider_details->paypal_email}} </td> 
            </tr>

        @endforeach

    </tbody>

</table>
