<table>
    <thead>
    <tr>
        <th> {{tr('id')}} </th>
        <th> {{tr('name')}} </th>
        <th> {{tr('email')}} </th>
        <th> {{tr('email_verified_at')}} </th>
        <th> {{tr('description')}} </th>
        <th> {{tr('mobile')}} </th>
        <th> {{tr('picture')}} </th>
        <th> {{tr('user_type')}} </th>
        <th> {{tr('device_type')}} </th>
        <th> {{tr('register_type')}} </th>
        <th> {{tr('login_by')}} </th>
        <th> {{tr('payment_mode')}} </th>
        <th> {{tr('timezone')}} </th>
        <th> {{tr('is_verified')}} </th>
        <th> {{tr('status')}} </th>
        <th> {{tr('created_at')}} </th>
        <th> {{tr('updated_at')}} </th>
        <th> {{tr('paypal_email')}} </th>
    </tr>
    </thead>
   
    <tbody>
       
        @foreach($users as $i => $user_details)

            <tr>
                <td> {{$i+1}} </td>
                
                <td> {{$user_details->name}} </td>
                
                <td> {{$user_details->email}} </td>
                                
                <td> {{$user_details->email_verified_at}} </td>
                                                
                <td> {{$user_details->description}} </td>
                
                <td> {{$user_details->mobile}} </td>
                
                <td> {{$user_details->picture}} </td>
                                                
                <td> {{$user_details->user_type}} </td>
                                                
                <td> {{$user_details->device_type}} </td>
                
                <td> {{$user_details->register_type}} </td>
                
                <td> {{$user_details->login_by}} </td>
                                                
                <td> {{$user_details->payment_mode}} </td>
                                
                <td> {{$user_details->timezone}} </td>

                <td>
                    @if($user_details->is_verified == USER_EMAIL_VERIFIED) 
                        {{ tr('verified') }}
                    @else
                        {{ tr('verify') }}
                    @endif 
                </td>
                
                <td> 
                    @if($user_details->status == USER_APPROVED)
                        {{ tr('approved') }}
                    @else
                        {{ tr('declined') }}
                    @endif
                </td>
                                
                <td> {{$user_details->created_at}} </td>
                
                <td> {{$user_details->updated_at}} </td>
                
                <td> {{$user_details->paypal_email}} </td>

            </tr>

        @endforeach

    </tbody>

</table>
