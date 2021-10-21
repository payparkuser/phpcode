@extends('layouts.admin')

@section('title', tr('custom_push'))

@section('content-header', tr('custom_push'))

@section('breadcrumb')

<li class="breadcrumb-item active" aria-current="page">

    <span>{{ tr('custom_push') }}</span>
</li>

@endsection

@section('content')

<div class="col-lg-12 grid-margin stretch-card">

    <div class="row flex-grow">

        <div class="col-12 grid-margin">

            <div class="card">

                @if($is_push_enabled)

                    <form class="forms-sample" action="{{route('admin.send.push')}}" method="POST" enctype="multipart/form-data" role="form">

                @else

                    <form class="forms-sample" role="form">

                @endif 

                @csrf
                
                    <div class="card-header bg-card-header">

                        <h4 class="">{{tr('custom_push')}}</h4>

                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="form-group col-md-12">

                                <p class="custom-push-note"> {{ tr('custom_push_note') }}</p>
                           
                            </div>

                        </div>

                        <div class="row">

                            <div class="form-group col-md-6">

                                <label for="custom_push_type">{{ tr('custom_push_type') }} <span class="admin-required">*</span></label>

                                <select id="custom_push_type" class="form-control select2" name="custom_push_type" required>

                                    <option value="user">{{ tr('user') }}</option>
                                    <option value="provider">{{ tr('provider') }}</option>
                                    <option value="both">{{ tr('both') }}</option>

                                </select>

                            </div>

                            <div class="form-group col-md-6">

                                <label for="device_type">{{ tr('device_type') }} <span class="admin-required">*</span></label>

                                <select id="device_type" class="form-control select2" name="device_type" required>

                                    <option value="android">{{ tr('android') }}</option>
                                    <option value="ios">{{ tr('ios') }}</option>
                                    <option value="both">{{ tr('both') }}</option>

                                </select>

                            </div>

                        </div>

                        <div class="row">

                            <div class="form-group col-md-12">

                                <label for="name"> {{ tr('message') }} <span class="admin-required">*</span></label>

                                <input type="text" class="form-control" name="message" id="message" placeholder="{{tr('enter_push_message')}}" required>
                           
                            </div>

                        </div>

                        <div class="row">

                            <div class="form-group col-md-12">

                                @if(!$is_push_enabled)

                                    <h4 class="text-danger"> {{ tr('push_notification_conf_fail') }}</h4>

                                @endif
                           
                            </div>

                        </div>

                    </div>

                    <div class="card-footer">

                        <button type="reset" class="btn btn-light">{{ tr('reset')}}</button>

                        @if(Setting::get('is_demo_control_enabled') == NO && $is_push_enabled)

                            <button type="submit" class="btn btn-success mr-2">{{ tr('submit') }} </button>

                        @else

                            <button type="button" class="btn btn-success mr-2" disabled>{{ tr('submit') }}</button>
                            
                        @endif

                    </div>

                </form>

            </div>

        </div>

    </div>
    
</div>

@endsection