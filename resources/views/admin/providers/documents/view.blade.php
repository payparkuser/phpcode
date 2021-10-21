@extends('layouts.admin')

@section('title', tr('documents'))

@section('breadcrumb')

<li class="breadcrumb-item">
    <a href="{{ route('admin.providers.index') }}">{{ tr('providers') }}</a>
</li>
<li class="breadcrumb-item active" aria-current="page">
    <span>{{ tr('documents') }}</span>
</li>

@endsection 

@section('content') 

<div class="col-lg-12 grid-margin stretch-card">
    
    <div class="card">
        <div class="card-header bg-card-header ">
            <h4 class="card-title">{{ tr('documents') }}</h4>
        </div>

        <div class="card-body">

            @if($provider_details)

                <div class="row col-lg-12">

                    <p class="text-muted">{!!tr('provider_documents_note')!!}</p>
                </div>

                <div class="row">

                    <div class="col-lg-6">

                        <p>
                            <b>{{tr('name')}}:</b> 
                            <a href="{{ route('admin.providers.view', ['provider_id' => $provider_details->id]) }}"> {{$provider_details->name}}</a>
                            @if($provider_details->is_document_verified == PROVIDER_DOCUMENT_VERIFIED)
                                
                                <i class="fa fa-shield fa-2x text-success"></i>

                            @endif
                        </p>

                        <!-- <p>
                            <b>{{tr('status')}}:</b> 

                            @if($provider_details->status == APPROVED)
                                
                                <span class="text-success">{{tr('approved')}}</span>

                            @else

                                <span class="text-danger">{{tr('pending')}}</span>

                            @endif

                        </p> -->

                    </div>

                    <div class="col-lg-6">

                        @if(count($provider_documents) > 0)

                            <a href="{{route('admin.providers.documents.verify', ['status' => YES, 'provider_id' => $provider_details->id])}}" class="btn btn-success">{{tr('approve_all_documents')}}</a>
                            
                            <a href="{{route('admin.providers.documents.verify', ['status' => NO, 'provider_id' => $provider_details->id])}}" class="btn btn-outline-danger">{{tr('decline_all_documents')}}</a>

                        @endif

                    </div>

                </div>

                @if($provider_details->status == APPROVED)

                    <p class="text-danger">{{tr('provider_decline_text')}} 
                        <a href="{{ route('admin.providers.status', ['provider_id' => $provider_details->id]) }}" onclick="return confirm(&quot;{{tr('provider_decline_confirmation')}}&quot;);" class="btn btn-danger btn-sm">
                            {{ tr('yes') }} 
                        </a>

                    </p>

                @else
                    
                    <p class="text-success">{{tr('provider_approve_text')}}

                    <a class="btn btn-success btn-sm" href="{{ route('admin.providers.status', ['provider_id' => $provider_details->id]) }}">
                        {{ tr('yes') }} 
                    </a>
                       
                @endif
            @endif

            <div class="table-responsive">
              
                <table id="order-listing" class="table">
                    
                    <thead>
                        <tr>
                            <th>{{ tr('s_no') }}</th>
                            <th>{{ tr('provider') }}</th>
                            <th>{{ tr('documents') }}</th>
                            <th>{{ tr('updated_on') }}</th>
                            <th>{{ tr('file') }}</th>
                            <th>{{ tr('action')}}</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($provider_documents as $index => $provider_document_details)

                            <tr>
                                
                                <td>{{ showEntries($_GET,$index+1) }}</td>
                                <td>
                                    <a href="{{ route('admin.providers.view', ['provider_id' => $provider_document_details->id]) }}">{{ $provider_document_details->providerDetails->name  ?? "-" }}
                                    </a>                           
                                </td>
                                <td>
                                    <a href="{{ route('admin.documents.view',['document_id' => $provider_document_details->document_id ]) }}">{{ $provider_document_details->documentDetails->name ?? "-"}} </a>
                                </td>

                                <td>
                                    {{ common_date($provider_document_details->updated_at,Auth::guard('admin')->user()->timezone) }}
                                </td>

                                <td>
                                    <a href="{{ $provider_document_details->document_url ? $provider_document_details->document_url : " - " }}" target="_blank"><span class="btn btn-info btn-large">{{ tr('view') }}</span>
                                    </a>
                                </td>

                                <td>
                                    <div class="template-demo">

                                        @if($provider_document_details->status == APPROVED)
                                            <a href="{{ route('admin.providers.documents.status', ['provider_document_id' => $provider_document_details->id]) }}" onclick="return confirm(&quot;{{tr('provider_document_decline_confirmation')}}&quot;);" class="btn btn-outline-danger">
                                                {{ tr('decline') }} 
                                            </a>

                                        @else
                                            
                                            <a class="btn btn-success btn-large" href="{{ route('admin.providers.documents.status', ['provider_document_id' => $provider_document_details->id]) }}" class="btn btn-outline-success">
                                                {{ tr('approve') }} 
                                            </a>
                                               
                                        @endif

                                        

                                    </div>
                                </td>
                                
                            </tr>

                        @endforeach

                    </tbody>

                </table>
            
            </div>
        
        </div>
    
    </div>

</div>

@endsection