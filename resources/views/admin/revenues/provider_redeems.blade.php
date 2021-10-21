@extends('layouts.admin') 

@section('title', tr('provider_redeems'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a>{{tr('revenues')}}</a></li>

    <li class="breadcrumb-item active" aria-current="page">
    	<span>{{tr('provider_redeems')}}</span>
    </li>
           
@endsection 

@section('content') 

	<div class="col-lg-12 grid-margin">
        
        <div class="card">

            <div class="card-header bg-card-header ">

                <h4 class="">{{tr('provider_redeems')}}</h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                <form class="col-6 row pull-right" action="{{route('admin.provider_redeems.index')}}" method="GET" role="search">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search_key" value="{{Request::get('search_key')??''}}"
                                placeholder="{{tr('provider_redeems_search_placeholder')}}" required> <span class="input-group-btn">
                                &nbsp
                                <button type="submit" class="btn btn-default">
                                    <span class="glyphicon glyphicon-search"> <i class="fa fa-search" aria-hidden="true"></i>
                                    </span>
                                </button>
                                  <a href="{{route('admin.provider_redeems.index')}}" type="reset" class="btn btn-default reset-btn" >
                                    <span class="glyphicon glyphicon-search"> <i class="fa fa-eraser" aria-hidden="true"></i>
                                    </span>
                                  </a>
                            </span>
                        </div>
                
                    </form>




                	<table id="order-listing" class="table">
                        
                        <thead>

                            <tr>
								<th>{{tr('s_no')}}</th>
								<th>{{tr('provider')}}</th>                        
								<th>{{tr('total')}}</th>
                                <th>{{tr('paid')}}</th>
                                <th>{{tr('remaining')}}</th>
                                <th>{{tr('dispute')}}</th>
                                <th>{{tr('paid_date')}}</th>
								<th>{{tr('action')}}</th>
                            </tr>

                        </thead>
                        
                        <tbody>

                            @if(count($provider_redeems) > 0 )
                            
                                @foreach($provider_redeems as $i => $provider_redeem_details)

                                    <tr>
                                        <td>{{ $i+$provider_redeems->firstItem() }}</td>
                                                                                
                                        <td>
                                            @if(empty($provider_redeem_details->provider_name))

                                                {{ tr('provider_not_avail') }}
                                            
                                            @else
                                                <a href="{{ route('admin.providers.view',['provider_id' => $provider_redeem_details->provider_id])}}">{{ $provider_redeem_details->provider_name }}</a>
                                            @endif
                                        </td>
                                        
                                        <td>
                                            {{formatted_amount($provider_redeem_details->total)}}                   
                                        </td>

                                        <td>
                                            {{formatted_amount($provider_redeem_details->paid_amount)}}                   
                                        </td>

                                        <td>
                                            {{formatted_amount($provider_redeem_details->remaining_amount)}}                   
                                        </td>

                                        <td>
                                            {{formatted_amount($provider_redeem_details->dispute_amount)}}                   
                                        </td>

                                        <td>
                                            {{common_date($provider_redeem_details->paid_date,Auth::guard('admin')->user()->timezone) ?: tr('not_available')}}                   
                                        </td>

                                        <td>
                                            @if($provider_redeem_details->remaining_amount > 0)

                                                    <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#ProviderRedeemModel{{$i}}">{{tr('pay_now')}}</button>
                                                
                                            @else
                                                <div class="badge badge-success badge-fw">{{ tr('paid')}}</div>
                                            @endif
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

                    <div class="pull-right">{{$provider_redeems->appends(request()->input())->links()}}</div>

                </div>

            </div>

        </div>

    </div>

    @foreach($provider_redeems as $i => $provider_redeem_details)

        @if($provider_redeem_details->remaining_amount)
            <div id="ProviderRedeemModel{{$i}}" class="modal fade" role="dialog">

                <div class="modal-dialog">

                    <div class="modal-content">
                
                        <div class="modal-header">
                            
                            <h4 class="modal-title pull-left"><a href="{{ route('admin.providers.view',['provider_id' => $provider_redeem_details->provider_id])}}">{{$provider_redeem_details->provider_name}}</a> </h4>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>

                        <div class="modal-body">

                            @if($provider_redeem_details->paynow_btn_status == NO)
                                <p>{{tr('billing_info_update_note_provider')}}</p>
                            @endif

                            <div class="row">
                                <div class="col-sm">
                                    <b>{{tr('account_name')}}</b>
                                    <p>{{$provider_redeem_details->account_name }}</p>
                                </div>
                                <div class="col-sm">
                                    <b>{{tr('account_no')}}</b>
                                    <p>{{$provider_redeem_details->account_no}}</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm">
                                    <b>{{tr('ifsc_code')}}</b>
                                    <p>{{$provider_redeem_details->route_no}}</p>
                                </div>
                                <div class="col-sm">
                                    <b>{{tr('remaining')}}</b>
                                    <p>{{formatted_amount($provider_redeem_details->remaining_amount)}}</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm">
                                    <b>{{tr('paypal_email')}}</b>
                                    <p>{{$provider_redeem_details->paypal_email}}</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                    
                                <form class="forms-sample" action="{{ route('admin.provider_redeems.payment') }}" method="POST" enctype="multipart/form-data" role="form" >
                                    @csrf

                                    <input type="hidden" name="provider_redeems_id" id="provider_redeems_id" value="{{$provider_redeem_details->id}}">

                                    <input type="hidden" class="form-control" id="amount" name="amount" placeholder="{{ tr('amount') }}" value="{{ $provider_redeem_details->remaining_amount}}" required>

                                    <button type="submit" class="btn btn-info btn-sm" @if($provider_redeem_details->paynow_btn_status == NO) disabled @endif onclick="return confirm(&quot;{{tr('provider_payment_confirmation')}}&quot;);" >{{tr('pay_now')}}</button>
                                </form>
                            
                            <button type="button" class="btn btn-default" data-dismiss="modal">{{tr('close')}}</button>
                        </div>
                    </div>
                </div>
            </div> 
        @endif
    @endforeach 

@endsection