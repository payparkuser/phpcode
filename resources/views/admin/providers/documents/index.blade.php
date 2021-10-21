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
        
        <div class="card-header bg-card-header">

            <h4 class="">

                {{tr('documents')}}

                <a class="btn btn-secondary pull-right" href="{{route('admin.providers.index')}}">
                    <i class="fa fa-eye"></i> {{tr('view_providers')}}
                </a>
                
            </h4>

        </div>
    
        <div class="card-body">

            <div class="table-responsive">

            <form class="col-6 row pull-right" action="{{route('admin.providers.documents.index')}}" method="GET" role="search">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search_key"
                                placeholder="{{tr('provider_documents_search_placeholder')}}" required> <span class="input-group-btn">
                                &nbsp
                                <button type="submit" class="btn btn-default">
                                    <span class="glyphicon glyphicon-search"> <i class="fa fa-search" aria-hidden="true"></i>
                                    </span>
                                </button>
                                <a href="{{route('admin.providers.documents.index')}}" class="btn btn-default reset-btn">
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
                            <th>{{ tr('documents') }}</th>                            
                            <th>{{ tr('action') }}</th>                            
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($provider_documents as $index => $provider_document_details)

                        <tr>

                            <td>{{ $index+1 }}</td>
                            
                            <td>
                                <a href="{{ route('admin.providers.view', ['provider_id' => $provider_document_details->provider_id]) }}">{{ $provider_document_details->providerDetails->name ??  "-" }}</a>
                            </td>

                            <td>
                                <a href="{{ route('admin.providers.documents.view',['provider_id' => $provider_document_details->provider_id ]) }}"> 
                                {{ $provider_document_details->total_documents ?? "0" }} {{tr('documents')}}</a>
                            </td>

                            <td>
                                <a href="{{ route('admin.providers.documents.view',['provider_id' => $provider_document_details->provider_id ]) }}"><span class="btn btn-success btn-large"><i class="fa fa-eye"></i> {{ tr('view') }}</span>
                                </a>
                            </td>
                            
                        </tr>

                        @endforeach

                    </tbody>


                </table>

                <div class="pull-right">{{$provider_documents->appends(request()->query())->links()}}</div>
            
                <div class="pull-right">{{ $provider_documents->links()}}</div>

            </div>
        
        </div>
    
    </div>

</div>

@endsection