@if(count($amenitie_details) > 0)

	@foreach($amenitie_details as $key=>$value)

        <div class="icheck-square">
            <input tabindex="5" type="checkbox" name="amenities[{{$value->amenity_lookup_id}}]" @if($value->is_selected == YES) checked @endif value="{{$value->amenity_lookup_id}}">

            <span>{{$value->value}}</span>
        </div>
    @endforeach

@endif
