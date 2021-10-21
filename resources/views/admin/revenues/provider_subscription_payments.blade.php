@extends('layouts.admin') 

@section('title', tr('subscription_payments'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a>{{tr('revenues')}}</a></li>

    <li class="breadcrumb-item active" aria-current="page">
    	<span>{{tr('subscription_payments')}}</span>
    </li>
           
@endsection 

@section('content') 

	<div class="col-lg-12 grid-margin">
        
        <div class="card">

            <div class="card-header bg-card-header ">

                <h4 style="display: inline-block;">{{tr('subscription_payments')}}</h4>

                @if(count($provider_subscription_payments) > 0 )
                    <button class="btn btn-secondary dropdown-toggle pull-right" type="button" id="dropdownMenuButton2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    {{tr('export')}}
                    </button>
                    <div class="dropdown-menu " aria-labelledby="dropdownMenuButton2" x-placement="bottom-start" style="position: absolute; transform: translate3d(0px, 33px, 0px); top: 0px; left: 0px; will-change: transform;">
                        <a class="dropdown-item" href="{{ route('admin.export.provider_subscription_payments', ['format' => 'xlsx'])}}">XLSX</a>
                        <a class="dropdown-item" href="{{ route('admin.export.provider_subscription_payments', ['format' => 'csv'])}}">CSV</a>
                        <a class="dropdown-item" href="{{ route('admin.export.provider_subscription_payments', ['format' => 'xls'])}}">XLS</a>
                        <!-- <a class="dropdown-item" href="{{ route('admin.export.provider_subscription_payments', ['format' => 'pdf'])}}">PDF</a> -->
                    </div>

                @endif

            </div>

            <div class="card-body">

                <div class="table-responsive">

                <form class="col-6 row pull-right" action="{{route('admin.provider_subscriptions.payments')}}" method="GET" role="search">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search_key" value="{{Request::get('search_key')??''}}"
                                placeholder="{{tr('subscription_payment_search_placeholder')}}" required> <span class="input-group-btn">
                                &nbsp
                                <button type="submit" class="btn btn-default">
                                    <span class="glyphicon glyphicon-search"> <i class="fa fa-search" aria-hidden="true"></i>
                                    </span>
                                </button>
                                  <a href="{{route('admin.provider_subscriptions.payments')}}" type="reset" class="btn btn-default reset-btn" >
                                    <span class="glyphicon glyphicon-search"> <i class="fa fa-eraser" aria-hidden="true"></i>
                                    </span>
                                  </a>
                            </span>
                        </div>
                
                    </form>
                
                	<table id="order-listing" class="table">
                        
                        <thead>
                            <tr>
                                <th>{{ tr('s_no') }}</th>
                                <th>{{ tr('provider') }}</th>
                                <th>{{ tr('subscriptions') }}</th>
                                <th>{{ tr('payment_id') }}</th>
                                <th>{{ tr('total') }} </th>
                                <th>{{ tr('status') }}</th>
                                <!-- <th>{{ tr('is_cancelled') }}</th> -->
                                <th>{{ tr('action') }}</th>
                            </tr>

                        </thead>
                                               
                        <tbody>

                            @foreach($provider_subscription_payments as $i => $provider_subscription_payment_details)

                                <tr>
                                    <td>{{$i+$provider_subscription_payments->firstItem()}}</td>

                                    <td>
                                        <a href="{{ route('admin.providers.view' , ['provider_id' => $provider_subscription_payment_details->provider_id ]) }}">{{ $provider_subscription_payment_details->providerDetails->name ?? tr('provider_not_avail') }}</a>
                                    </td>

                                    <td>
                                        @if($provider_subscription_payment_details->providerSubscriptionDetails)
                                            <a href="{{ route('admin.provider_subscriptions.view' ,['provider_subscription_id' => $provider_subscription_payment_details->provider_subscription_id]) }}">{{ substr($provider_subscription_payment_details->providerSubscriptionDetails->title,0,10) ?? tr('provider_subscription_not_avail') }}..</a>
                                        @else 
                                            {{ tr('provider_subscription_not_avail') }}
                                        @endif
                                        <br>
                                        {{plan_text( $provider_subscription_payment_details->providerSubscriptionDetails->plan ?? '0',  $provider_subscription_payment_details->providerSubscriptionDetails->plan_type ?? '')}}
                                        <br>
                                        <small class="text-success">{{tr('expiry_at')}}: {{common_date($provider_subscription_payment_details->expiry_date, Auth::guard('admin')->user()->timezone)}}</small>
                                    </td>
                                  
                                    <td>
                                        {{$provider_subscription_payment_details->payment_id}}
                                        <br>
                                        <small>{{tr('paid_at')}}: {{common_date($provider_subscription_payment_details->updated_at, Auth::guard('admin')->user()->timezone)}}</small>
                                    </td>
<!-- 
                                    <td>
                                        {{plan_text( $provider_subscription_payment_details->providerSubscriptionDetails->plan ?? '0',  $provider_subscription_payment_details->providerSubscriptionDetails->plan_type ?? '')}}

                                        <br>
                                        <small class="text-success">{{tr('expiry_at')}}: {{common_date($provider_subscription_payment_details->expiry_date, Auth::guard('admin')->user()->timezone)}}</small>

                                    </td> -->

                                    <td>
                                        {{ formatted_amount($provider_subscription_payment_details->paid_amount) }}
                                    </td>

                                    <td>
                                        @if($provider_subscription_payment_details->status ) 
                                           
                                           <span class="badge badge-success badge-fw">{{ tr('paid')}}</span>

                                        @else
                                           
                                            <span class="badge badge-danger badge-fw">{{ tr('not_paid')}}</span>
                                        @endif
                                    </td>
<!-- 
                                    <td>
                                        @if( $provider_subscription_payment_details->is_cancelled ) 
                                            <span class="badge badge-success badge-fw">{{ tr('yes') }}</span>
                                        @else
                                            <span class="badge badge-danger badge-fw">{{ tr('no') }}</span>

                                        @endif
                                    </td> -->

                                     <td>
                                            <a class="btn btn-primary" href="{{ route('admin.provider.subscriptions.payments.view', ['id' => $provider_subscription_payment_details->id] )}}">
                                                {{tr('view')}}
                                            </a> 
                                    </td>                               

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                    <div class="pull-right">{{$provider_subscription_payments->appends(request()->query())->links()}}</div>
                     
                </div>

            </div>

        </div>

    </div>	
    
@endsection