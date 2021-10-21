@extends('layouts.admin') 

@section('title', tr('add_space'))

@section('breadcrumb')

    <li class="breadcrumb-item">
    	<a href="{{ route('admin.spaces.index') }}">{{tr('parking_space')}}</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">
    	<span>{{tr('add_space')}}</span>
    </li>
           
@endsection 

@section('styles')

    <link rel="stylesheet" href="{{ asset('admin-assets/css/host.css')}} ">   

@endsection

@section('content')

    @include('admin.spaces._form')
   
@endsection

@section('scripts')

<script src="{{ asset('admin-assets/node_modules/jquery-steps/build/jquery.steps.min.js')}}"></script>
 
<script src="{{ asset('admin-assets/node_modules/jquery-validation/dist/jquery.validate.min.js')}}"></script>

<script src="{{ asset('admin-assets/js/wizard.js')}}"></script>

<script>

    var autocomplete;
    
    var location_latitude = document.getElementById('latitude');
    
    var location_longitude = document.getElementById('longitude');

    function geolocate() {
        
        autocomplete = new google.maps.places.Autocomplete((document.getElementById('full_address')),
            {types: ['geocode']});

        autocomplete.addListener('place_changed', function(event) {

            var place = autocomplete.getPlace();

            if ( place.hasOwnProperty('place_id') ) {

                if (!place.geometry) {
                    
                    alert("Autocomplete's returned place contains no geometry");

                    document.getElementById('full_address').value = '';

                    return;
                }

                console.log(JSON.stringify(place));

                latitude = location_latitude.value = place.geometry.location.lat();

                longitude = location_longitude.value = place.geometry.location.lng();

                getLatLng(latitude, longitude)          
            } 

        });
    
    }

    function getLatLng(lat, lng) {

        geocoder = new google.maps.Geocoder();

        var latlng = new google.maps.LatLng(lat, lng);

        geocoder.geocode({
            'latLng': latlng
        }, function (results, status) {
            
            if (status == google.maps.GeocoderStatus.OK) {

                if (results[1]) {

                    var indice = 0;

                    for (var j = 0; j < results.length; j++) {
                        if (results[j].types[0] == 'locality') {
                            indice = j;
                            break;
                        }
                    }

                    for (var i = 0; i < results[j].address_components.length; i++) {

                        if (results[j].address_components[i].types[0] == "locality") {
                            //this is the object you are looking for City
                            city = results[j].address_components[i];
                        }
                        if (results[j].address_components[i].types[0] == "administrative_area_level_1") {
                            //this is the object you are looking for State
                            region = results[j].address_components[i];
                        }

                        if (results[j].address_components[i].types[0] == "country") {
                            //this is the object you are looking for
                            country = results[j].address_components[i];
                        }
                    }

                    city.long_name ? $('#city').val(city.long_name) : "";

                    region.long_name ? $('#state').val(region.long_name) : "";

                    country.long_name ? $('#country').val(country.long_name) : "";

                } else {
                    alert("No results found");
                }
                //}
            
            } else {
                alert("Geocoder failed due to: " + results);
            }
        });
    
    }

    $(document).ready(function() {

        $('#host_type').on('select2:select' , function (e) {

            var host_type = $(this).val();

            var amenities_url = "{{route('admin.get_amenities')}}";

            var data = {'host_type' : host_type, _token: '{{csrf_token()}}'};

            var request = $.ajax({
                            url: amenities_url,
                            type: "POST",
                            data: data,
                        });

            request.done(function(result) {
                
                if(result.success == true) {
                    $("#amenitie_details").html(result.view);
                }

            });

            request.fail(function(jqXHR, textStatus) {
                alert( "Request failed: " + textStatus );
            });

        });

        updateAccessMethod();

        $("input[name='access_method']").change(function() {
            updateAccessMethod();
        });

    });

    function updateAccessMethod() {

        if($('#secret_code').is(":checked") == true) {

            $('#security_code_div').show();

            $('#security_code').attr('required');

        } else {

            $('#security_code_div').hide();

            $('#security_code').removeAttr('required');

        }
    }

</script>
@endsection