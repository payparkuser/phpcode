@extends('layouts.admin') 

@section('title', tr('ios_control'))

@section('breadcrumb')

<li class="breadcrumb-item active" aria-current="page">

    <span>{{ tr('ios_control') }}</span>
</li>

@endsection

@section('content')


<div class="col-lg-12 grid-margin stretch-card">

    <div class="row flex-grow">

        <div class="col-12 grid-margin">

            <div class="card">

                <form class="forms-sample" action="{{ route('admin.settings.save') }}" method="POST" enctype="multipart/form-data" role="form">

                    @csrf

                    <div class="card-header bg-card-header ">

                        <h4 class="">{{tr('ios_control')}}</h4>

                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="form-group col-md-6">
                                 
                                <label>{{ tr('is_ios_control_enabled') }}</label>
                                <br>
                                <label>
                                    <input required type="radio" name="is_appstore_updated" value="1" class="flat-red" @if(Setting::get('is_appstore_updated') == 1) checked @endif>
                                    {{tr('yes')}}
                                </label>

                                <label>
                                    <input required type="radio" name="is_appstore_updated" class="flat-red"  value="0" @if(Setting::get('is_appstore_updated') == 0) checked @endif>
                                    {{tr('no')}}
                                </label>
                            </div>                        
                        </div>

                        <div class="clearfix"></div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-success mr-2">{{ tr('submit') }}</button>
                    </div>

                </form>

            </div>

        </div>
    
    </div>

</div>
@endsection