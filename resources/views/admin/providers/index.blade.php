@extends('layouts.admin') 

@section('title', tr('view_providers'))

@section('breadcrumb')

    <li class="breadcrumb-item"><a href="{{ route('admin.providers.index') }}">{{tr('providers')}}</a></li>

    <li class="breadcrumb-item active" aria-current="page">
        <span>{{ tr('view_providers') }}</span>
    </li>
                      
@endsection 

@section('content')

    <div class="col-lg-12 grid-margin stretch-card">

        <div class="card">

            <div class="card-header bg-card-header ">

                <h4 class="">{{tr('view_providers')}}
                    
                    @if(count($providers) > 0 )

                    <button class="btn btn-secondary dropdown-toggle pull-right" type="button" id="dropdownMenuButton2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="margin-left:5px">
                    {{tr('export')}}
                    </button>
                    <div class="dropdown-menu " aria-labelledby="dropdownMenuButton2" x-placement="bottom-start" style="position: absolute; transform: translate3d(0px, 33px, 0px); top: 0px; left: 0px; will-change: transform;">
                        <a class="dropdown-item" href="{{ route('admin.export.providers', ['format' => 'xlsx'])}}">xlsx</a>
                        <a class="dropdown-item" href="{{ route('admin.export.providers', ['format' => 'csv'])}}"> CSV</a>
                        <a class="dropdown-item" href="{{ route('admin.export.providers', ['format' => 'xls'])}}"> XLS</a>
                        <!-- <a class="dropdown-item" href="{{ route('admin.export.providers', ['format' => 'pdf'])}}">PDF</a> -->
                    </div>
                    
                    @endif

                    <a class="btn btn-secondary pull-right" href="{{route('admin.providers.create')}}">
                        <i class="fa fa-plus"></i> {{tr('add_provider')}}
                    </a>

                    <div class="admin-action template-demo pull-right">

                        <div class="dropdown">

                            <button class="btn btn-secondar dropdown-toggle" type="button" id="dropdownMenuOutlineButton1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{tr('bulk_action')}}
                            </button>

                            <div class="dropdown-menu" aria-labelledby="dropdownMenuOutlineButton1">
                                                  
                                <a class="dropdown-item action_list" href="#" id="bulk_delete">
                                    {{tr('delete')}}
                                </a>
                                                   
                                <a class="dropdown-item action_list" href="#" id="bulk_approve">
                                    {{ tr('approve') }} 
                                </a> 

                                <a class="dropdown-item action_list" href="#" id="bulk_decline">
                                    {{ tr('decline') }} 
                                </a>  

                            </div>

                        </div>

                    </div>

                    <div class="bulk_action">

                        <form  action="{{route('admin.providers.bulk_action')}}" id="providers_form" method="POST" role="search">

                            @csrf

                            <input type="hidden" name="action_name" id="action" value="">

                            <input type="hidden" name="selected_providers" id="selected_ids" value="">

                            <input type="hidden" name="page_id" id="page_id" value="{{ (request()->page) ? request()->page : '1' }}">

                        </form>
                        
                    </div>

                </h4>

            </div>
        
            <div class="card-body">
                
                <div class="table-responsive">
                   
                    <form class="col-6 row pull-right" action="{{route('admin.providers.index')}}" method="GET" role="search">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search_key" value="{{Request::get('search_key')??''}}"
                                placeholder="{{tr('provider_search_placeholder')}}" required> <span class="input-group-btn">
                                &nbsp
                                <button type="submit" class="btn btn-default">
                                    <span class="glyphicon glyphicon-search"> <i class="fa fa-search" aria-hidden="true"></i>
                                    </span>
                                </button>
                                  <a href="{{route('admin.providers.index')}}" type="reset" class="btn btn-default reset-btn" >
                                    <span class="glyphicon glyphicon-search"> <i class="fa fa-eraser" aria-hidden="true"></i>
                                    </span>
                                  </a>
                            </span>
                        </div>
                
                    </form>

                    <table id="order-listing" class="table">
                    
                        <thead>
                            <tr>
                                <th>
                                    <input id="check_all" type="checkbox">
                                </th>
                                <th>{{tr('s_no')}}</th>
                                <th>{{tr('name')}}</th>
                                <th>{{tr('email')}}</th>
                                <th>{{tr('mobile')}}</th>
                                <th>{{tr('status')}}</th>
                                <th>{{tr('verify')}}</th>
                                <th>{{tr('document_verified_status')}}</th>
                                <th>{{tr('action')}}</th>
                            </tr>
                        </thead>
                    
                        <tbody>
                         
                            @foreach($providers as $i => $provider_details)
                           
                                <tr>

                                    <td><input type="checkbox" name="row_check" class="faChkRnd" id="{{$provider_details->id}}" value="{{$provider_details->id}}"></td>

                                    <td>{{$i+$providers->firstItem()}}</td>
                                  
                                    <td>
                                        <a href="{{route('admin.providers.view' , ['provider_id' => $provider_details->id])}}">    
                                            {{$provider_details->name}}
                                        </a>
                                        @if($provider_details->provider_type == NO)    
                                            <i class="fa fa-times-circle  text-danger"></i>
                                        @elseif($provider_details->provider_type == YES)
                                            <i class="fa fa-check-circle text-success"></i>
                                        @endif
                                       
                                    </td>

                                    <td> {{$provider_details->email}} </td>

                                    <td> {{$provider_details->mobile ?: tr('not_available')}}</td>

                                    <td>

                                        @if($provider_details->status == PROVIDER_APPROVED)
                                         
                                            <span class="badge badge-outline-success">
                                                {{ tr('approved') }} 
                                            </span>

                                        @else

                                            <span class="badge badge-outline-danger">
                                                {{ tr('declined') }} 
                                            </span>
                                                 
                                        @endif

                                    </td>
                                    
                                    <td>   

                                        @if($provider_details->is_verified == PROVIDER_EMAIL_VERIFIED)                   
                                            
                                            <span class="badge badge-outline-success">
                                                {{ tr('verified') }} 
                                            </span>

                                        @else
                                            
                                            <a class="badge badge-outline-info"  href="{{ route('admin.providers.verify', ['provider_id' => $provider_details->id]) }} "> {{ tr('verify') }} </a>

                                        @endif
                                    </td>

                                    <td>   

                                        @if($provider_details->is_document_verified == PROVIDER_DOCUMENT_VERIFIED)                   
                                            
                                            <span class="badge badge-outline-success">
                                                {{ tr('verified') }} 
                                            </span>

                                        @else
                                            
                                            <span class="badge badge-outline-info">
                                                {{ tr('pending') }} 
                                            </span>

                                        @endif
                                    </td>
                                    
                                    <td>                                    
                                        
                                        <div class="template-demo">

                                            <div class="dropdown">

                                                <button class="btn btn-outline-primary  dropdown-toggle btn-sm" type="button" id="dropdownMenuOutlineButton1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    {{tr('action')}}
                                                </button>

                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuOutlineButton1">

                                                    <a class="dropdown-item" href="{{ route('admin.providers.view', ['provider_id' => $provider_details->id]) }}">
                                                        {{tr('view')}}
                                                    </a>
                                                      
                                                    @if(Setting::get('is_demo_control_enabled') == NO)
                                                        
                                                        <a class="dropdown-item" href="{{ route('admin.providers.edit', ['provider_id' => $provider_details->id]) }}">{{tr('edit')}}
                                                        </a>
                                                                                                        
                                                        <a class="dropdown-item" onclick="return confirm(&quot;{{tr('provider_delete_confirmation' , $provider_details->name)}}&quot;);" href="{{ route('admin.providers.delete', ['provider_id' => $provider_details->id,'page'=>request()->input('page'),'search_key'=>request()->input('search_key')])}}">{{ tr('delete') }}
                                                        </a>

                                                    @else

                                                        <a class="dropdown-item text-muted" href="javascript:;">{{tr('edit')}}
                                                        </a>
                                                        
                                                        <a class="dropdown-item text-muted" href="javascript:;">{{ tr('delete') }}
                                                        </a>

                                                    @endif

                                                    <div class="dropdown-divider"></div>

                                                    @if($provider_details->is_verified == NO) 
                                                        
                                                        <a class="dropdown-item" href="{{ route('admin.providers.verify', ['provider_id' => $provider_details->id]) }} ">{{tr('verify')}} 
                                                        </a>

                                                    @endif
                                                    

                                                    @if($provider_details->status == APPROVED)

                                                        <a class="dropdown-item" href="{{ route('admin.providers.status', ['provider_id' => $provider_details->id])}}" onclick="return confirm(&quot;{{$provider_details->name}} - {{tr('provider_decline_confirmation')}}&quot;);" >
                                                            {{tr('decline')}} 
                                                        </a>      

                                                    @else

                                                        <a class="dropdown-item" href="{{ route('admin.providers.status', ['provider_id' => $provider_details->id])}}">
                                                            {{tr('approve')}}
                                                        </a>
                                                           
                                                    @endif

                                                    <div class="dropdown-divider"></div>

                                                    <a class="dropdown-item" href="{{ route('admin.providers.revenues', ['provider_id' => $provider_details->id]) }}">
                                                        {{tr('revenues')}}
                                                    </a>

                                                     <a class="dropdown-item" href="{{ route('admin.provider_subscriptions.plans' , ['provider_id' => $provider_details->id]) }}" >{{ tr('plans') }}</a>

                                                    <a class="dropdown-item" href="{{route('admin.providers.documents.view', array('provider_id'=>$provider_details->id))}}">
                                                        {{tr('documents')}}
                                                    </a>

                                                    <a class="dropdown-item" href="{{ route('admin.bookings.index', ['provider_id' => $provider_details->id]) }}">
                                                        {{ tr('bookings') }} 
                                                    </a>

                                                    <a class="dropdown-item" href="{{ route('admin.reviews.providers', ['provider_id' => $provider_details->id]) }}">
                                                        {{ tr('reviews') }} 
                                                    </a> 
                                                        
                                                </div>

                                            </div>

                                        </div>
                                   
                                    </td>
                                
                                </tr>

                            @endforeach
                                                                 
                        </tbody>
  
                    </table>

                    <div class="pull-right">{{$providers->appends(request()->query())->links()}}</div>
  
                </div>
  
            </div>
  
        </div>
  
    </div>

@endsection

@section('scripts')
    
@if(Session::has('bulk_action'))
<script type="text/javascript">
    $(document).ready(function(){
        localStorage.clear();
    });
</script>
@endif

<script type="text/javascript">

    $(document).ready(function(){
        get_values();

        // Call to Action for Delete || Approve || Decline
        $('.action_list').click(function(){
            var selected_action = $(this).attr('id');
            if(selected_action != undefined){
                $('#action').val(selected_action);
                if($("#selected_ids").val() != ""){
                    if(selected_action == 'bulk_delete'){
                        var message = "{{ tr('admin_providers_delete_confirmation') }}";
                    }else if(selected_action == 'bulk_approve'){
                        var message = "{{ tr('admin_providers_approve_confirmation') }}";
                    }else if(selected_action == 'bulk_decline'){
                        var message = "{{ tr('admin_providers_decline_confirmation') }}";
                    }
                    var confirm_action = confirm(message);

                    if (confirm_action == true) {
                      $( "#providers_form" ).submit();
                    }
                    // 
                }else{
                    alert('Please select the check box');
                }
            }
        });
    // single check
    var page = $('#page_id').val();
    $(':checkbox[name=row_check]').on('change', function() {
        var checked_ids = $(':checkbox[name=row_check]:checked').map(function() {
            return this.id;
        })
        .get();

        localStorage.setItem("provider_checked_items"+page, JSON.stringify(checked_ids));

        get_values();

    });
    // select all checkbox
    $("#check_all").on("click", function () {
        if ($("input:checkbox").prop("checked")) {
            $("input:checkbox[name='row_check']").prop("checked", true);
            var checked_ids = $(':checkbox[name=row_check]:checked').map(function() {
                return this.id;
            })
            .get();
            
            console.log("provider_checked_items"+page);

            localStorage.setItem("provider_checked_items"+page, JSON.stringify(checked_ids));
            get_values();
        } else {
            $("input:checkbox[name='row_check']").prop("checked", false);
            localStorage.removeItem("provider_checked_items"+page);
            get_values();
        }

    });

    // Get Id values for selected Providers
    function get_values(){
        var pageKeys = Object.keys(localStorage).filter(key => key.indexOf('provider_checked_items') === 0);
        var values = Array.prototype.concat.apply([], pageKeys.map(key => JSON.parse(localStorage[key])));

        if(values){
            $('#selected_ids').val(values);
        }

        for (var i=0; i<values.length; i++) {
            $('#' + values[i] ).prop("checked", true);
        }
    }

});
</script>

@endsection