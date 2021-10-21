@extends('layouts.admin') 

@section('title', tr('user_refunds'))

@section('breadcrumb')

    <li class="breadcrumb-item"><a>{{tr('revenues')}}</a></li>

    <li class="breadcrumb-item active" aria-current="page">
    	<span>{{tr('user_refunds')}}</span>
    </li>
           
@endsection 

@section('content') 

	<div class="col-lg-12 grid-margin">
        
        <div class="card">

            <div class="card-header bg-card-header ">

                <h4 class="">{{tr('user_refunds')}}</h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                <form class="col-6 row pull-right" action="{{route('admin.user_refunds.index')}}" method="GET" role="search">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search_key" value="{{Request::get('search_key')??''}}"
                                placeholder="{{tr('user_refunds_search_placeholder')}}" required> <span class="input-group-btn">
                                &nbsp
                                <button type="submit" class="btn btn-default">
                                    <span class="glyphicon glyphicon-search"> <i class="fa fa-search" aria-hidden="true"></i>
                                    </span>
                                </button>
                                  <a href="{{route('admin.user_refunds.index')}}" type="reset" class="btn btn-default reset-btn" >
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
								<th>{{tr('user')}}</th>                          
								<th>{{tr('total')}}</th>
                                <th>{{tr('paid_amount')}}</th>
                                <th>{{tr('remaining')}}</th>
                                <th>{{tr('paid_date')}}</th>
								<th>{{tr('action')}}</th>
                            </tr>

                        </thead>
                        
                        <tbody>

                            @if(count($user_refunds) > 0 )
                            
                                @foreach($user_refunds as $i => $user_refund_details)

                                    <tr>
                                        <td>{{ $i+$user_refunds->firstItem() }}</td>
                                                                                

                                        <td>
                                            @if(empty($user_refund_details->user_name))

                                                {{ tr('user_not_avail') }}
                                            
                                            @else
                                                <a href="{{ route('admin.users.view',['user_id' => $user_refund_details->user_id])}}">{{ $user_refund_details->user_name }}</a>
                                            @endif
                                        </td>
                                        
                                        <td>
                                            {{formatted_amount($user_refund_details->total)}}                   
                                        </td>

                                        <td>
                                            {{formatted_amount($user_refund_details->paid_amount)}}                   
                                        </td>

                                        <td>
                                            {{formatted_amount($user_refund_details->remaining_amount)}}                   
                                        </td>

                                        <td>
                                            {{common_date($user_refund_details->paid_date,Auth::guard('admin')->user()->timezone) ?: tr('not_available') }}                   
                                        </td>

                                        <td>
                                            @if($user_refund_details->remaining_amount >0)

                                                <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#UserRefundModel{{$i}}">{{tr('pay_now')}}</button>
                                        
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

                    <div class="pull-right">{{$user_refunds->appends(request()->input())->links()}}</div>

                </div>

            </div>

        </div>

    </div>

    @foreach($user_refunds as $i => $user_refund_details)

        @if($user_refund_details->remaining_amount)

            <div id="UserRefundModel{{$i}}" class="modal fade" role="dialog">

                <div class="modal-dialog">

                    <div class="modal-content">
                
                        <div class="modal-header">
                            
                            <h4 class="modal-title pull-left"><a href="{{ route('admin.users.view',['user_id' => $user_refund_details->user_id])}}">{{$user_refund_details->user_name}}</a></h4>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>

                        </div>

                        <div class="modal-body">

                            @if($user_refund_details->paynow_btn_status == NO)
                                <p>{{tr('billing_info_update_note')}}</p>
                            @endif
                            <div class="row">
                                <div class="col-sm">
                                    <b>{{tr('account_name')}}</b>
                                    <p>{{$user_refund_details->account_name}}</p>
                                </div>
                                <div class="col-sm">
                                    <b>{{tr('account_no')}}</b>
                                    <p>{{$user_refund_details->account_no}}</p>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm">
                                    <b>{{tr('ifsc_code')}}</b>
                                    <p>{{$user_refund_details->route_no}}</p>
                                </div>
                                <div class="col-sm">
                                    <b>{{tr('remaining')}}</b>
                                    <p>{{formatted_amount($user_refund_details->remaining_amount)}}</p>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm">
                                    <b>{{tr('paypal_email')}}</b>
                                    <p>{{$user_refund_details->paypal_email}}</p>
                                </div>
                            </div>

                        </div>

                        <div class="modal-footer">
                    
                            <form class="forms-sample" action="{{ route('admin.user_refunds.payment') }}" method="POST" enctype="multipart/form-data" role="form" >
                                @csrf

                                <input type="hidden" name="user_refund_id" id="user_refund_id" value="{{$user_refund_details->id}}">

                                <input type="hidden" class="form-control" id="amount" name="amount" value="{{ $user_refund_details->remaining_amount}}" required>

                                    <button type="submit" class="btn btn-info btn-sm"  @if($user_refund_details->paynow_btn_status == NO) disabled @endif onclick="return confirm(&quot;{{tr('user_payment_confirmation')}}&quot;);">{{tr('pay_now')}}</button>
                            </form>
        
                            <button type="button" class="btn btn-default" data-dismiss="modal">{{tr('close')}}</button>

                        </div>

                    </div>

                </div>

            </div> 

        @endif
        
    @endforeach
    
@endsection