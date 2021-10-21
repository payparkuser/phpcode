@extends('layouts.admin') 

@section('title', tr('bookings_payments'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a>{{tr('revenues')}}</a></li>

    <li class="breadcrumb-item active" aria-current="page">
    	<span>{{tr('bookings_payments')}}</span>
    </li>
           
@endsection 

@section('content') 

	<div class="col-lg-12 grid-margin">
        
        <div class="card">

            <div class="card-header bg-card-header ">

                <h4 style="display: inline-block;">{{tr('bookings_payments')}}</h4>
               
                @if(count($booking_payments) > 0 )
                    <button class="btn btn-secondary dropdown-toggle pull-right" type="button" id="dropdownMenuButton2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    {{tr('export')}}
                    </button>
                    <div class="dropdown-menu " aria-labelledby="dropdownMenuButton2" x-placement="bottom-start" style="position: absolute; transform: translate3d(0px, 33px, 0px); top: 0px; left: 0px; will-change: transform;">
                        <a class="dropdown-item" href="{{ route('admin.export.booking_payments', ['format' => 'xlsx'])}}">XLSX</a>
                        <a class="dropdown-item" href="{{ route('admin.export.booking_payments', ['format' => 'csv'])}}">CSV</a>
                        <a class="dropdown-item" href="{{ route('admin.export.booking_payments', ['format' => 'xls'])}}">XLS</a>
                        <!-- <a class="dropdown-item" href="{{ route('admin.export.booking_payments', ['format' => 'pdf'])}}">PDF</a> -->
                    </div>

                @endif

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <form class="col-6 row pull-right" action="{{route('admin.bookings.payments')}}" method="GET" role="search">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search_key"
                                placeholder="{{tr('booking_payments_search_placeholder')}}" required> <span class="input-group-btn">
                                &nbsp
                                <button type="submit" class="btn btn-default">
                                    <span class="glyphicon glyphicon-search"> <i class="fa fa-search" aria-hidden="true"></i>
                                    </span>
                                </button>
                                <button type="reset" class="btn btn-default">
                                    <span class="glyphicon glyphicon-search"> <i class="fa fa-eraser" aria-hidden="true"></i>
                                    </span>
                                </button>
                            </span>
                        </div>
                
                    </form>

                	<table id="order-listing" class="table">
                        
                        <thead>

                            <tr>
								<th>{{tr('s_no')}}</th>
                                <th>{{tr('booking_id')}}</th>
								<th>{{tr('user')}}</th>
								<th>{{tr('provider')}}</th>
                                <th>{{tr('host')}}</th>
								<th>{{tr('pay_via')}}</th>
								<th>{{tr('total')}}</th>
                                <th>{{tr('status')}}</th>
								<th>{{tr('action')}}</th>
                            </tr>

                        </thead>
                        
                        <tbody>

                            @if(count($booking_payments) > 0 )
                            
                                @foreach($booking_payments as $i => $booking_payment_details)

                                    <tr>
                                        <td>{{ $i+$booking_payments->firstItem()}}</td>

                                        <td>
                                            <a href="{{route('admin.bookings.view', ['booking_id' => $booking_payment_details->booking_id])}}">#{{ $booking_payment_details->booking_unique_id}}
                                            </a> 
                                        </td>
                                                                                
                                        <td> 
                                            @if(empty($booking_payment_details->user_name))

                                                {{ tr('user_not_avail') }}
                                            
                                            @else
                                                <a href="{{ route('admin.users.view',['user_id' => $booking_payment_details->user_id])}}">{{ $booking_payment_details->user_name }}</a>
                                            @endif
                                        </td>

                                        <td>
                                            @if(empty($booking_payment_details->provider_name))

                                                {{ tr('provider_not_avail') }}
                                            
                                            @else
                                                <a href="{{ route('admin.providers.view',['provider_id' => $booking_payment_details->provider_id])}}">{{ $booking_payment_details->provider_name }}</a>
                                            @endif
                                        </td>

                                        <td>
                                            @if(empty($booking_payment_details->host_name))

                                                {{ tr('host_not_avail') }}
                                            
                                            @else
                                                <a href="{{ route('admin.spaces.view',['host_id' => $booking_payment_details->host_id])}}">{{ $booking_payment_details->host_name }}</a>
                                            @endif
                                        </td>
                                        
                                        <td> 
                                            {{ $booking_payment_details->payment_mode }}
                                        </td>

                                        <td>
                                            {{formatted_amount($booking_payment_details->total)}}                   
                                        </td>

                                        <td>
                                            @if($booking_payment_details->status == PAID_STATUS)

                                                <div class="badge badge-success badge-fw">{{ tr('paid')}}</div>
                                            @else 

                                                <div class="badge badge-danger badge-fw">{{ tr('not_paid')}}</div>
                                          
                                            @endif
                                        </td>

                                        <td>
                                            <a class="btn btn-primary" href="{{ route('admin.bookings.view', ['booking_id' => $booking_payment_details->booking_id] )}}">
                                                {{tr('view')}}
                                            </a> 
                                        </td>

                                    </tr>

                                @endforeach

                            @else

                                <tr>
                                    <td>{{ tr('no_result_found') }}</td>
                                </tr>

                            @endif

                        </tbody>

                    </table>

                    <div class="pull-right">{{$booking_payments->appends(request()->query())->links()}}</div>

                </div>

            </div>

        </div>

    </div>	

    
@endsection