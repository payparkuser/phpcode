@extends('layouts.admin') 

@section('title', tr('view_user'))

@section('breadcrumb')

    <li class="breadcrumb-item">
        <a href="{{route('admin.users.index')}}">{{tr('users')}}</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">
        <span>{{tr('view_user')}}</span>
    </li>
           
@endsection  

@section('content')
    
    <div class="row">

        <div class="col-md-12">

            <!-- Card group -->
            <div class="card-group">

                <!-- Card -->
                <div class="card mb-4">

                    <!-- Card image -->
                    <div class="view overlay">

                        <img class="card-img-top" src="{{$user_details->picture}}">
                        <a href="#!">
                            <div class="mask rgba-white-slight"></div>
                        </a>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-6">

                                @if(Setting::get('is_demo_control_enabled') == YES)

                                    <a href="javascript:;" class="btn btn-primary btn-block">{{tr('edit')}}</a>

                                    <a href="javascript:;" class="btn btn-danger btn-block">{{tr('delete')}}</a>

                                @else

                                    <a class="btn btn-primary btn-block" href="{{ route('admin.users.edit', ['user_id' => $user_details->id])}}">{{tr('edit')}}</a>

                                    <a class="btn btn-danger btn-block" href="{{route('admin.users.delete', ['user_id' => $user_details->id])}}" onclick="return confirm(&quot;{{tr('user_delete_confirmation' , $user_details->name)}}&quot;);">{{tr('delete')}}</a>

                                @endif

                                @if($user_details->status == USER_APPROVED)

                                    <a class="btn btn-danger btn-block" href="{{ route('admin.users.status', ['user_id' => $user_details->id]) }}" onclick="return confirm(&quot;{{$user_details->first_name}} - {{tr('user_decline_confirmation')}}&quot;);" >
                                        {{ tr('decline') }} 
                                    </a>

                                @else
                                    
                                    <a class="btn btn-success btn-block" href="{{ route('admin.users.status', ['user_id' => $user_details->id]) }}">
                                        {{ tr('approve') }} 
                                    </a>
                                       
                                @endif

                            </div>
                            
                            <div class="col-md-6">

                                @if($user_details->is_verified == USER_EMAIL_NOT_VERIFIED) 

                                    <a class="btn btn-primary btn-block" href="{{ route('admin.users.verify', ['user_id' => $user_details->id]) }}"> {{ tr('verify') }} 
                                    </a>   

                                @endif 

                                <a class="btn btn-info btn-block" href="{{ route('admin.bookings.index', ['user_id' => $user_details->id]) }}">
                                  {{ tr('bookings') }} 
                                </a> 

                                <a class="btn btn-warning btn-block" href="{{ route('admin.reviews.users', ['user_id' => $user_details->id]) }}">
                                  {{ tr('reviews') }} 
                                </a> 

                                <a class="btn btn-warning btn-block" href="{{ route('admin.wishlists.index', ['user_id' => $user_details->id]) }}">
                                  {{ tr('wishlist') }} 
                                </a>  

                            </div>
                                
                        </div>

                        <hr>

                        <div class="row">
                            @if($user_details->description)
                                <h5 class="col-md-12">{{tr('description')}}</h5>

                                <p class="col-md-12 text-muted">{{$user_details->description}}</p>
                            @endif
                        </div>


                    </div>
                
                </div>
                <!-- Card -->

                <!-- Card -->
                <div class="card mb-4">

                    <!-- Card content -->
                    <div class="card-body">

                        <div class="template-demo">

                            <table class="table mb-0">

                              <tbody>

                                <tr>
                                    <td class="pl-0"><b>{{ tr('name') }}</b></td>
                                    <td class="pr-0 text-right"><div >{{$user_details->name}}</div></td>
                                </tr>

                                <tr>
                                    <td class="pl-0"><b>{{ tr('email') }}</b></td>
                                    <td class="pr-0 text-right"><div >{{$user_details->email}}</div></td>
                                </tr>
                                

                                <tr>

                                  <td class="pl-0"> <b>{{ tr('status') }}</b></td>

                                  <td class="pr-0 text-right">

                                        @if($user_details->status == USER_PENDING)

                                            <span class="card-text badge badge-danger badge-md text-uppercase">{{tr('pending')}}</span>

                                        @elseif($user_details->status == USER_APPROVED)

                                            <span class="card-text  badge badge-success badge-md text-uppercase">{{tr('approved')}}</span>

                                        @else

                                            <span class="card-text label label-rouded label-menu label-danger">{{tr('declined')}}</span>

                                        @endif

                                  </td>

                                </tr>

                                <tr>
                                    <td class="pl-0"><b>{{ tr('device_type') }}</b></td>
                                    <td class="pr-0 text-right text-capitalize"><div >{{$user_details->device_type}}</div></td>
                                </tr>

                                <tr>
                                    <td class="pl-0"><b>{{ tr('login_by') }}</b></td>
                                    <td class="pr-0 text-right text-capitalize"><div>{{ $user_details->login_by }}</div></td>
                                </tr>

                                <tr>
                                    <td class="pl-0"><b>{{ tr('register_type') }} </b></td>
                                    <td class="pr-0 text-right text-capitalize"><div>{{ $user_details->register_type }}</div></td>
                                </tr>


                                <tr>
                                    <td class="pl-0"> <b>{{ tr('timezone') }}</b></td>
                                    <td class="pr-0 text-right"><div>{{$user_details->timezone}}</div></td>
                                </tr>

                                <tr>
                                    <td class="pl-0"> <b>{{ tr('created_at') }}</b></td>
                                    <td class="pr-0 text-right"><div>{{ common_date($user_details->created_at,Auth::guard('admin')->user()->timezone) }}</div></td>
                                </tr>

                                <tr>
                                    <td class="pl-0"> <b>{{ tr('updated_at') }}</b></td>
                                    <td class="pr-0 text-right"><div>{{ common_date($user_details->updated_at,Auth::guard('admin')->user()->timezone) }}</div></td>
                                </tr>

                              </tbody>

                            </table>

                        </div>
                        <!-- </div> -->

                    </div>
                    <!-- Card content -->

                </div>

                <div class="card mb-4">
                    <!-- Card content -->
                    <div class="card-body">

                        <div class="template-demo">

                            <table class="table mb-0">

                              <tbody>


                                <tr>
                                    <td class="pl-0"><b>{{ tr('payment_mode') }} </b></td>
                                    <td class="pr-0 text-right"><div >{{$user_details->payment_mode}}</div></td>
                                </tr>

                                <tr>
                                    <td class="pl-0"> <b> {{ tr('account_name') }} </b> </td>
                                    <td class="pr-0  text-right">
                                        <div> {{ $user_billing_info->account_name ?: tr('not_available') }} </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="pl-0"> <b> {{ tr('account_no')}} </b> </td>
                                    <td class="pr-0  text-right">
                                        <div>{{ $user_billing_info->account_no ?: tr('not_available') }} </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="pl-0"> <b> {{ tr('paypal_email')}} </b> </td>
                                    <td class="pr-0  text-right">
                                        <div>{{ $user_billing_info->paypal_email ?: tr('not_available') }} </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="pl-0"> <b> {{ tr('ifsc_code')}} </b> </td>
                                    <td class="pr-0  text-right">
                                        <div>{{ $user_billing_info->route_no ?: tr('not_available') }} </div>
                                    </td>
                                </tr>

                                <tr> 

                                    <td class="pl-0"><b>{{ tr('is_email_verified') }}</b></td>
                                    
                                    <td class="pr-0 text-right">

                                        @if($user_details->is_verified == USER_EMAIL_NOT_VERIFIED)

                                            <span class="card-text label label-rouded label-menu label-danger">{{ tr('no') }}</span>

                                        @else

                                            <span class="card-text badge badge-success badge-md text-uppercase">{{ tr('yes') }}</span>

                                        @endif
                                    </td>

                                </tr>

                                <tr> 

                                    <td class="pl-0"><b>{{ tr('push_notification') }}</b></td>
                                    
                                    <td class="pr-0 text-right">

                                        @if($user_details->push_notification_status)

                                            <span class="card-text label label-rouded label-menu label-danger">{{ tr('on') }}</span>

                                        @else

                                            <span class="card-text badge badge-success badge-md text-uppercase">{{ tr('off') }}</span>

                                        @endif
                                    </td>

                                </tr>

                                <tr> 

                                    <td class="pl-0"><b>{{ tr('email_notification') }}</b></td>
                                    
                                    <td class="pr-0 text-right">

                                        @if($user_details->email_notification_status)

                                            <span class="card-text label label-rouded label-menu label-danger">{{ tr('on') }}</span>

                                        @else

                                            <span class="card-text badge badge-success badge-md text-uppercase">{{ tr('off') }}</span>

                                        @endif
                                    </td>

                                </tr>

                              

                              </tbody>

                            </table>

                        </div>
                        <!-- </div> -->

                    </div>
                    <!-- Card content -->
                </div>

                <!-- Card -->

            </div>
            <!-- Card group -->

        </div>

    </div>
    
    <div class="row" id="vehicle_details">

        <div class="col-lg-12 grid-margin stretch-card">
            
            <div class="card">

                <div class="card-header">

                    <h4 class="">{{tr('vehicle_details')}}
                    
                        <a class="btn btn-success pull-right" href="{{ route('admin.vehicle_details.create', ['user_id' => $user_details->id]) }}"> <i class="fa fa-plus"></i>{{tr('add_vehicle')}}
                        </a>

                    </h4>

                </div>

                <div class="card-body">

                    <div class="table-responsive">
                    
                        <table id="order-listing" class="table">
                           
                            <thead>
                               
                                <tr>
                                    <th>{{ tr('vehicle_number') }}</th>
                                    <th>{{ tr('vehicle_type') }}</th>
                                    <th>{{ tr('vehicle_brand') }}</th>
                                    <th>{{ tr('vehicle_model') }}</th>
                                    <th>{{ tr('action') }}</th>
                                </tr>

                            </thead>
                           
                            <tbody>

                                @foreach($vehicles as $i => $vehicle_details)
                               
                                    <tr>
                                        
                                        <td>{{ $vehicle_details->vehicle_number }} </td>

                                        <td>{{ $vehicle_details->vehicle_type }} </td>

                                        <td>{{ $vehicle_details->vehicle_brand }} </td>

                                        <td>{{ $vehicle_details->vehicle_model }} </td>

                                        <td>
                                           
                                            <a href="{{ route('admin.vehicle_details.edit', ['vehicle_id' => $vehicle_details->id] ) }}" class="btn btn-primary" title="{{tr('edit')}}"><i class="mdi mdi-border-color"></i>{{tr('edit')}}</a>
                                            
                                            <a href="{{ route('admin.vehicle_details.delete', ['vehicle_id' => $vehicle_details->id] ) }}" class="btn btn-danger" title="{{tr('delete')}}"><i class="mdi mdi-delete"></i>{{tr('delete')}}</a>
                                       
                                        </td>

                                    </tr>

                                @endforeach
                                                                     
                            </tbody>

                        </table>

                    </div>

                </div>

            </div>
        
        </div>

    </div>

@endsection