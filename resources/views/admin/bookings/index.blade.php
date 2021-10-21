

@extends('layouts.admin') 

@section('title', tr('history'))

@section('breadcrumb')

    <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">{{tr('bookings')}}</a></li>
  
    <li class="breadcrumb-item active" aria-current="page">
        <span>{{ tr('history') }}</span>
    </li>
           
@endsection 

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">
        
        <div class="card">

            <div class="card-header bg-card-header ">

                <h4 class="booking-header">{{tr('history')}}</h4>
                
                @if($bookings)

                @switch(Request::get('status'))

                    @case(BOOKING_COMPLETED)
                        - <h4 class="booking-header">{{tr('completed')}}</h4>
                    @break;

                    @case(BOOKING_CANCELLED_BY_USER)
                        - <h4 class="booking-header">{{tr('user_cancelled')}}</h4>
                    @break;

                    @case(BOOKING_CANCELLED_BY_PROVIDER)
                        - <h4 class="booking-header">{{tr('provider_cancelled')}}</h4>
                    @break;

                    @case(BOOKING_CHECKIN)
                        - <h4 class="booking-header">{{tr('checkin')}}</h4>
                    @break;

                    @case(BOOKING_CHECKOUT)
                        - <h4 class="booking-header">{{tr('checkout')}}</h4>
                    @break;

                @endswitch

                @if(Request::get('status') == BOOKING_COMPLETED)
                    
                @endif

                <button class="btn btn-secondary dropdown-toggle pull-right" type="button" id="dropdownMenuButton2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                {{tr('export')}}
                </button>
                
                <div class="dropdown-menu " aria-labelledby="dropdownMenuButton2" x-placement="bottom-start" style="position: absolute; transform: translate3d(0px, 33px, 0px); top: 0px; left: 0px; will-change: transform;">
                    <a class="dropdown-item" href="{{ route('admin.export.bookings', ['format' => 'xlsx'])}}">XLSX</a>
                    <a class="dropdown-item" href="{{ route('admin.export.bookings', ['format' => 'csv'])}}">CSV</a>
                    <a class="dropdown-item" href="{{ route('admin.export.bookings', ['format' => 'xls'])}}">XLS</a>
                    <!-- <a class="dropdown-item" href="{{ route('admin.export.bookings', ['format' => 'pdf'])}}">PDF</a> -->
                </div>


                @endif

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <form class="col-6 row pull-right" action="{{route('admin.bookings.index')}}" method="GET" role="search">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search_key" value="{{Request::get('search_key')}}"
                                placeholder="{{tr('bookings_search_placeholder')}}" > <span class="input-group-btn">
                                &nbsp
                                <button type="submit" class="btn btn-default">
                                    <span class="glyphicon glyphicon-search"> <i class="fa fa-search" aria-hidden="true"></i>
                                    </span>
                                </button>
                                <a  href="{{route('admin.bookings.index')}}" class="btn btn-default reset-btn">
                                    <span class="glyphicon glyphicon-search"> <i class="fa fa-eraser" aria-hidden="true"></i>
                                    </span>
                                  </a>
                            </span>
                        </div>
                
                    </form>
                    
                    <table id="order-listing" class="table">
                        
                        <thead>
                            <tr>
                                <th width="32px">{{tr('s_no')}}</th>
                                <th>{{tr('user') }}</th>
                                <th>{{tr('provider') }}</th>
                                <th>{{tr('booking_id') }}</th>
                                <th>{{tr('parking_space') }}</th>
                                <th>{{tr('checkin') }}-{{tr('checkout') }}</th>
                                <th>{{tr('status')}}</th>
                                <th>{{tr('action')}}</th>
                            </tr>
                        </thead>
                        
                        <tbody>
                         
                            @foreach($bookings as $i => $booking_details)

                                <tr>
                                    <td>{{$i+$bookings->firstItem()}}</td>

                                    <td class="white-space-nowrap">
                                        @if($booking_details->userDetails->name)
                                            <a href="{{ route('admin.users.view',['user_id' => $booking_details->user_id ])}}"> {{ $booking_details->userDetails->name}}</a>
                                        @else
                                            {{tr('user_not_avail')}}
                                        @endif
                                    </td>

                                    <td class="white-space-nowrap">
                                        <a href="{{ route('admin.providers.view',['provider_id' => $booking_details->provider_id])}}">{{ $booking_details->providerDetails->name ?? tr('provider_not_avail')}}</a>
                                    </td>

                                    <td class="white-space-nowrap"> 
                                        {{$booking_details->unique_id}}
                                        </a>
                                    </td>
                                    <td class="white-space-nowrap"> 
                                        @if(empty($booking_details->host_name))

                                            {{ tr('host_not_avail') }}
                                        
                                        @else
                                            <a href="{{ route('admin.spaces.view',['host_id' => $booking_details->host_id])}}">
                                                {{$booking_details->host_name}}
                                            </a>
                                        @endif

                                    </td>

                                    <td class="white-space-nowrap">
                                        {{common_date($booking_details->checkin, Auth::guard('admin')->user()->timezone, 'd M Y')}}
                                        -
                                        {{common_date($booking_details->checkout, Auth::guard('admin')->user()->timezone, 'd M Y')}}

                                    </td>
                                  
                                    <td>                                    
                                        <span class="text-info white-space-nowrap">{{ booking_status( $booking_details->status) }}</span>
                                    </td>
                                   
                                    <td>   
                                        <div class="template-demo">

                                            <div class="dropdown">

                                                <button class="btn btn-outline-primary  dropdown-toggle" type="button" id="dropdownMenuOutlineButton1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    {{tr('action')}}
                                                </button>

                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuOutlineButton1">
                                                  
                                                    <a class="dropdown-item" href="{{ route('admin.bookings.view', ['booking_id' => $booking_details->id])}}">
                                                        {{tr('view')}}
                                                    </a>
                                                    
                                                    @if($booking_details->status == BOOKING_INITIATE)
                                                    <div class="dropdown-divider"></div>

                                                    <a class="dropdown-item" href="{{ route('admin.bookings.cancel', ['booking_id' => $booking_details->id])}}"> {{tr('cancel')}}
                                                    </a>
                                                    @endif
                                                    <div class="dropdown-divider"></div>

                                                    
                                                </div>

                                            </div>

                                        </div>
                                        
                                    </td>

                                </tr>
                                
                            @endforeach
                                                                 
                        </tbody>
                    
                    </table>

                    <div class="pull-right"> {{ $bookings->appends(request()->query())->links()}}</div>

                </div>

            </div>

        </div>

    </div>

@endsection