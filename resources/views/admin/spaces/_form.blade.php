    <div class="row">
    
    <div class="col-12 grid-margin">

        <div class="card">
            <div class="card-header bg-card-header ">
                <h4> @yield('title') </h4>
            </div>

            <div class="card-body">
                
                <h4 class="card-title"> @yield('title') </h4>

                <hr>

                <form class="forms-sample" id="example-form" action="{{ route('admin.spaces.save') }}" method="POST" enctype="multipart/form-data" role="form">

                    @csrf

                    <div>

                        <input type="hidden" name="host_id" value="{{ $host_details->id }}">

                        <h3 class="text-uppercase">{{tr('space_details')}}</h3>

                        <section>
                            <h4 class="heading_color">{{tr('location_details')}}</h4><hr>
                           
                            <div class="row">

                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label for="service_location_id">{{tr('choose_service_location')}}</label>

                                        <select class="form-control select2" id="service_location_id" name="service_location_id">
                                            <option value="">{{tr('choose_service_location')}}</option>

                                            @foreach($service_locations as $service_location_details)
                                                <option value="{{$service_location_details->id}}"@if($service_location_details->is_selected == YES) selected @endif>
                                                    {{$service_location_details->name}}
                                                </option>
                                            @endforeach

                                        </select>
                                    
                                    </div>

                                </div>

                                <div class="col-md-8">

                                    <div class="form-group">

                                        <label for="full_address">{{tr('choose_location')}}</label>

                                        <input type="text" class="form-control"  id="full_address" onFocus="geolocate()" name="full_address" placeholder="{{ tr('choose_location') }}" value="{{ old('full_address') ?: $host_details->full_address}}" required>

                                        <input type="hidden" name="latitude" id="latitude" value="{{old('latitude') ?: $host_details->latitude}}">

                                        <input type="hidden" name="longitude" id="longitude" value="{{old('longitude') ?: $host_details->longitude}}">
                                    
                                    </div>

                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="street_details">{{tr('street_details')}}</label>
                                        <input id="street_details" name="street_details" type="text" class="required form-control" value="{{old('street_details') ?: $host_details->street_details}}">
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="city">{{tr('city')}}</label>
                                        <input id="city" name="city" type="text" class="required form-control" value="{{old('city') ?:  $host_details->city }}">
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="state">{{tr('state')}}</label>
                                        <input id="state" name="state" type="text" class="required form-control" value="{{ old('state') ?: $host_details->state}}">
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="zipcode">{{tr('zipcode')}}</label>
                                        <input id="zipcode" name="zipcode" type="text" class="required form-control" value="{{old('zipcode') ?: $host_details->zipcode}}">
                                    </div>
                                </div>
                            </div>
                            <br>
                            <h4 class="heading_color">{{tr('space_details_text')}}</h4><hr>
                            <div class="row">
                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label for="provider_id">{{tr('providers')}}</label>

                                        <select class="form-control select2" id="provider_id" name="provider_id">
                                            <option value="">{{tr('choose_provider')}}</option>
                                            @foreach($providers as $provider)
                                                <option value="{{$provider->id}}"@if($provider->is_selected == YES) selected @endif>
                                                    {{$provider->name}}
                                                </option>
                                            @endforeach

                                        </select>
                                    
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="host_name">{{tr('space_name')}} *</label>
                                        <input id="host_name" name="host_name" type="text" class="required form-control" value="{{old('host_name') ?: $host_details->host_name}}">
                                    </div>
                                </div>

                                <div class=" col-md-4">
                                   
                                    <div class="form-group">

                                        <label for="host_type">{{tr('choose_host_type')}}</label>

                                        <select class="form-control select2" id="host_type" name="host_type">

                                            <option value="">{{tr('choose_host_type')}}</option>

                                            @foreach($host_types as $host_type_details)
                                                <option value="{{$host_type_details->key}}" @if($host_type_details->is_selected == YES) selected @endif>{{$host_type_details->value}}</option>
                                            @endforeach
                                            
                                        </select>

                                    </div>
                                
                                </div>

                                <div class=" col-md-4">
                                   
                                    <div class="form-group">

                                        <label for="category">{{tr('host_owner_type')}}</label>

                                        <select class="form-control select2" id="host_owner_type" name="host_owner_type">

                                            <option value="">{{tr('host_owner_type')}}</option>

                                            @foreach($host_owner_types as $host_owner_type)
                                                <option value="{{$host_owner_type->key}}" @if($host_owner_type->is_selected == YES) selected @endif>{{$host_owner_type->value}}</option>
                                            @endforeach

                                        </select>
                               
                                    </div>
                               
                                </div>

                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label>{{tr('total_spaces')}}* </label>

                                        <input type="number" name="total_spaces" class="form-control" value="{{old('total_spaces') ?: $host_details->total_spaces}}">

                                    </div>

                                </div>

                                <div class="col-md-12">

                                    <div class="form-group">

                                        <label for="description">{{tr('description')}} *</label>

                                        <textarea id="description" name="description" class="form-control">{{old('description') ?: $host_details->description}}</textarea>
                                    
                                    </div>

                                </div>


                            </div>
                          
                            
                        </section>

                        <h3 class="text-uppercase">{{tr('host_location_images')}}</h3>

                        <section>

                            <h4 class="heading_color">{{tr('host_upload_images')}}</h4><hr>

                            <div class="row">
                                
                                <div class="form-group col-md-12">

                                    <label>{{tr('images')}}</label>

                                    <input type="file" class="form-control" name="picture" multiple accept="image/*" placeholder="{{tr('upload_image')}}">

                                </div>
                                
                            </div>

                            <div class="row">
                                
                                <div class="form-group col-md-12">

                                    <label>{{tr('gallery')}}</label>

                                    <input type="file" class="form-control" name="pictures[]" multiple accept="image/*" placeholder="{{tr('gallery_image')}}">

                                </div>

                            </div>
                        <span class="text-muted">{{tr('image_note')}}</span>

                        </section>

                        <h3 class="text-uppercase">{{ tr('host_pricing_details') }}</h3>

                        <section>
                            <h4 class="heading_color">{{ tr('pricing_details') }}</h4>
                            <hr>
                            
                            <div class="row">

                                <div class="col-md-4">

                                    <div class="form-group">
                                        <label>{{tr('per_hour')}} *</label>
                                        <input type="number" min="0" name="per_hour" required value="{{ old('per_hour') ?: $host_details->per_hour}}" class="nonzero">
                                    </div>

                                </div>

                                <div class="col-md-4">

                                    <div class="form-group">
                                        <label>{{tr('per_day')}} *</label>
                                        <input type="number" min="0" min="0" name="per_day" value="{{old('per_day') ?: $host_details->per_day}}" class="nonzero">
                                    </div>

                                </div>

                                <div class="col-md-4">

                                    <div class="form-group">
                                        <label>{{tr('per_month')}} *</label>
                                        <input type="number" min="0" name="per_month" value="{{old('per_month') ?: $host_details->per_month}}" class="nonzero">

                                    </div>

                                </div>

                            </div>

                            <br>
                       
                            <h4 class="heading_color">
                                {{tr('area_details')}}
                                <span class="text-muted">({{tr('area_details_note')}})</span>
                            </h4>

                            <hr>

                            <div class="row">
                                <div class="col-md-4">

                                    <div class="form-group">
                                        <label>{{tr('width')}} *</label>
                                        <input type="number" min="0" name="width_of_space" required step="any" value="{{ old('width_of_space') ?: $host_details->width_of_space}}">
                                    </div>

                                </div>

                                <div class="col-md-4">

                                    <div class="form-group">
                                        <label>{{tr('height')}} *</label>
                                        <input type="number" min="0" name="height_of_space" required value="{{ old('height_of_space') ?: $host_details->height_of_space}}">
                                    </div>

                                </div> 

                                <div class="col-md-4">

                                    <div class="form-group">
                                        <label>{{tr('length')}} *</label>
                                        <input type="number" min="0" name="length_of_space" required value="{{ old('length_of_space') ?: $host_details->length_of_space}}">
                                    </div>

                                </div>
                            </div>

                            <h4 class="heading_color">{{tr('access_method')}}</h4><hr>
                      
                            <div class="row">
                                
                                <div class="form-group col-6">

                                    <label>{{tr('access_method_key')}} *</label>

                                    <div class="switch-field">

                                        <input type="radio" id="secret_code" name="access_method" value="{{ACCESS_METHOD_SECRET_CODE}}" @if($host_details->access_method == ACCESS_METHOD_SECRET_CODE) checked @endif/>
                                        <label for="secret_code">
                                            {{tr('secret_code')}}
                                        </label>

                                        <input id="access_method_key" name="access_method" type="radio" value="{{ ACCESS_METHOD_KEY }}" @if($host_details->access_method == ACCESS_METHOD_KEY) checked @endif>

                                        <label for="access_method_key">
                                            {{tr('access_method_key')}}
                                        </label>

                                    </div>

                                </div>

                                <div class="form-group col-6" id="security_code_div">

                                    <div class="form-group">

                                        <label>{{tr('security_code')}} *</label>

                                        <input id="security_code" type="text" name="security_code" value="{{ old('security_code') ?: $host_details->security_code}}">
                                    </div>

                                </div>
                               
                                <div class="col-md-12">

                                    <div class="form-group">

                                        <label for="access_note">{{tr('access_note')}} *</label>

                                        <textarea class="form-control" id="access_note" name="access_note">{{ old('access_note') ?: $host_details->access_note}}</textarea>
                                    
                                    </div>

                                </div>

                            </div>
                            
                        </section>

                        <h3 class="text-uppercase">{{ tr('space_amenities') }}</h3>

                        <section>
                            <h4 class="heading_color">{{ tr('amenities') }}</h4>
                            <hr>

                            <div id="amenitie_details">
                                @include('admin.spaces._amenities')
                            </div>
                            <br>                      

                        </section>

                        <h3 class="text-uppercase">{{tr('finish')}}</h3>

                        <section>

                            <h4 class="heading_color">{{tr('instant_booking_is')}}</h4>

                            <p class="text-muted"><b>{{tr('note')}}: </b>{{tr('instant_booking_note')}}</p>
                            
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input class="radio" type="radio" name="is_automatic_booking" value="1" @if($host_details->is_automatic_booking == YES || $host_details->is_automatic_booking == '' ) checked @endif> {{tr('on')}}
                                <i class="input-helper"></i></label>
                            </div>

                            <div class="form-check">
                                <label class="form-check-label">
                                    <input class="radio" type="radio" name="is_automatic_booking" value="0" @if($host_details->is_automatic_booking == NO ) checked @endif> {{tr('off')}}
                                <i class="input-helper"></i></label>
                            </div>
                           
                            <!-- <h4>Terms and conditions</h4>
                           
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input class="checkbox" type="checkbox" checked > I agree with the Terms and Conditions.
                                </label>
    
                            </div> -->
    
                        </section>
    
                    </div>
    
                </form>
    
            </div>
    
        </div>
    
    </div>

</div>

