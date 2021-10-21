@extends('layouts.admin') 

@section('title', tr('dashboard'))

@section('breadcrumb')

    <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">{{tr('bookings')}}</a></li>
  
    <li class="breadcrumb-item active" aria-current="page">
        <span>{{ tr('dashboard') }}</span>
    </li>
           
@endsection 

@section('content')

<div class="row">

    <div class="col-md-6 col-lg-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">                 
                <a href="{{route('admin.bookings.index')}}">
                    <div class="d-flex align-items-center justify-content-md-center">
                        <i class="icon-calendar icon-lg text-primary"></i>
                        <div class="ml-3">
                            <p class="mb-0">{{ tr('total_bookings') }}</p>
                            <h6>{{ $data->total_bookings }}</h6>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>      

    <div class="col-md-6 col-lg-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">                 
                <a href="{{route('admin.bookings.dashboard', ['status' =>BOOKING_COMPLETED ])}}">
                    <div class="d-flex align-items-center justify-content-md-center">
                        <i class="icon-calendar icon-lg text-success"></i>
                        <div class="ml-3">
                            <p class="mb-0">{{ tr('bookings_completed') }}</p>
                            <h6>{{ $data->bookings_completed }}</h6>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>    

    <div class="col-md-6 col-lg-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">                
                <a href="{{route('admin.bookings.dashboard', ['status' => BOOKING_CANCELLED_BY_PROVIDER])}}">
                    <div class="d-flex align-items-center justify-content-md-center">
                        <i class="icon-calendar icon-lg text-danger"></i>
                        <div class="ml-3">
                            <p class="mb-0">{{ tr('total_bookings_cancelled_by_provider') }}</p>
                            <h6>{{ $data->bookings_cancelled_by_provider }}</h6>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>    

    <div class="col-md-6 col-lg-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <a href="{{route('admin.bookings.dashboard', ['status' => BOOKING_CANCELLED_BY_USER])}}">
                <div class="d-flex align-items-center justify-content-md-center">
                    <i class="icon-calendar icon-lg text-danger"></i>
                    <div class="ml-3">
                        <p class="mb-0">{{ tr('total_bookings_cancelled_by_user') }}</p>
                        <h6>{{ $data->bookings_cancelled_by_user }}</h6>
                    </div>
                </div>
                </a>
            </div>
        </div>
    </div>    

    <div class="col-md-6 col-lg-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <a href="{{route('admin.bookings.dashboard', ['status' => BOOKING_CHECKIN])}}">
                <div class="d-flex align-items-center justify-content-md-center">
                    <i class="icon-calendar icon-lg text-info"></i>
                    <div class="ml-3">
                        <p class="mb-0">{{ tr('total_checkin') }}</p>
                        <h6>{{ $data->total_bookings_checkin }}</h6>
                    </div>
                </div>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <a href="{{route('admin.bookings.dashboard', ['status' => BOOKING_CHECKOUT])}}">
                <div class="d-flex align-items-center justify-content-md-center">
                    <i class="icon-calendar icon-lg text-warning"></i>
                    <div class="ml-3">
                        <p class="mb-0">{{ tr('total_checkout') }}</p>
                        <h6>{{ $data->total_bookings_checkout }}</h6>
                    </div>
                </div>
                </a>
            </div>
        </div>
    </div>
    @if($data->bookings->count()>0)
     <div class="col-12 grid-margin stretch-card">
        
        <div class="card">

            <div class="card-header bg-card-header ">

                <h4 class="">{{tr('bookings')}}
                    @if($data->status) - {{ booking_status($data->status) }} @endif
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">
                    
                    <table id="order-listing" class="table">
                        
                        <thead>
                            <tr>
                                <th>{{tr('s_no')}}</th>
                                <th>{{tr('user')}}</th>
                                <th>{{tr('provider')}}</th>
                                <th>{{tr('space')}}</th>
                                <th>{{tr('checkin_out') }}</th>
                                <th>{{tr('status')}}</th>
                                <th>{{tr('action')}}</th>
                            </tr>
                        </thead>
                        
                        <tbody>
                         
                            @foreach($data->bookings as $i => $booking_details)

                                <tr>
                                    <td>{{$i+1}}</td>

                                    <td>
                                        <a href="{{ route('admin.users.view',['user_id' => $booking_details->user_id ])}}"> {{ $booking_details->userDetails->name ?? tr('user_not_avail')}}</a>
                                       
                                    </td>

                                    <td>
                                        <a href="{{ route('admin.providers.view',['provider_id' => $booking_details->provider_id])}}">{{ $booking_details->providerDetails->name ??  tr('provider_not_avail')}}</a>
                                    </td>

                                    <td> 
                                        @if(empty($booking_details->host_name))

                                            {{ tr('host_not_avail') }}
                                        
                                        @else
                                            <a href="{{ route('admin.spaces.view',['host_id' => $booking_details->host_id])}}">
                                                {{$booking_details->host_name}}
                                            </a>
                                        @endif

                                    </td>

                                    <td>
                                        {{common_date($booking_details->checkin, Auth::guard('admin')->user()->timezone, 'd M Y')}}
                                        -
                                        {{common_date($booking_details->checkout, Auth::guard('admin')->user()->timezone, 'd M Y')}}

                                    </td>
                                  
                                    <td>                                    
                                        <span class="text-info">{{ booking_status( $booking_details->status) }}</span>
                                    </td>
                                   
                                    <td>   
                                        <a class="btn btn-primary" href="{{ route('admin.bookings.view', ['booking_id' => $booking_details->id])}}"><i class="fa fa-eye"></i>{{tr('view')}}</a>
                                        
                                    </td>

                                </tr>

                            @endforeach
                                                                 
                        </tbody>
                    
                    </table>
                    <div class="pull-right">{{$data->bookings->appends(request()->input())->links()}}</div>              

                </div>

            </div>

        </div>

    </div>
    @endif
</div>

@endsection