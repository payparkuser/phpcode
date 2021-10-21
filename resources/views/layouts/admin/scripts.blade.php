<script src="{{ asset('admin-assets/node_modules/jquery/dist/jquery.min.js')}}"></script>
<script src="{{ asset('admin-assets/node_modules/popper.js/dist/umd/popper.min.js') }}"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js"></script>

<script src="{{ asset('admin-assets/node_modules/bootstrap/dist/js/bootstrap.min.js') }}"></script>

<script src="{{ asset('admin-assets/node_modules/perfect-scrollbar/dist/js/perfect-scrollbar.jquery.min.js') }}"></script>
<!-- endinject -->

<!-- Plugin js for this page-->
<script src="{{ asset('admin-assets/node_modules/jquery-bar-rating/dist/jquery.barrating.min.js') }}"></script>
<script src="{{ asset('admin-assets/node_modules/chart.js/dist/Chart.min.js') }}"></script>
<script src="{{ asset('admin-assets/node_modules/raphael/raphael.min.js') }}"></script>
<script src="{{ asset('admin-assets/node_modules/morris.js/morris.min.js') }}"></script>
<script src="{{ asset('admin-assets/node_modules/jquery-sparkline/jquery.sparkline.min.js') }}"></script>

<script src="{{ asset('admin-assets/node_modules/icheck/icheck.min.js') }}"></script>
<!-- End plugin js for this page-->
<!-- inject:js -->
<script src="{{ asset('admin-assets/js/off-canvas.js') }}"></script>
<script src="{{ asset('admin-assets/js/hoverable-collapse.js') }}"></script>
<script src="{{ asset('admin-assets/js/misc.js') }}"></script>
<script src="{{ asset('admin-assets/js/settings.js') }}"></script>
<script src="{{ asset('admin-assets/js/todolist.js') }}"></script>

<script src="{{ asset('admin-assets/js/file-upload.js') }}"></script>
<script src="{{ asset('admin-assets/js/iCheck.js') }}"></script>

<!-- Plugin js for this page-->
<script src="{{ asset('admin-assets/node_modules/datatables.net/js/jquery.dataTables.js')}}"></script>
<script src="{{ asset('admin-assets/node_modules/datatables.net-bs4/js/dataTables.bootstrap4.js')}}"></script>
<!-- endinject -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>

<script src="{{ asset('admin-assets/node_modules/simplemde/dist/simplemde.min.js') }}"></script>
<!-- Custom js for this page-->
<script src="{{ asset('admin-assets/js/dashboard.js') }}"></script>

<script src="{{ asset('admin-assets/js/file-upload.js')}}"></script>


<script src="{{ asset('admin-assets/node_modules/summernote/dist/summernote-bs4.min.js')}}"></script>

<script src="{{ asset('admin-assets/node_modules/summernote/dist/summernote-bs4.min.js')}}"></script>

<script src="{{ asset('admin-assets/node_modules/lightgallery/dist/js/lightgallery-all.min.js')}}"></script>
<script src = "{{asset('admin-assets/datepicker/bootstrap-datepicker.js')}}"></script> 

<!-- Js file for Image pop-up view-->
<script src="{{ asset('admin-assets/js/light-gallery.js') }}"></script>

<!-- Js file for full calendar view-->
<script src="{{ asset('admin-assets/node_modules/moment/moment.js') }}"></script>
<script src="{{ asset('admin-assets/node_modules/fullcalendar/dist/fullcalendar.min.js') }}"></script>

<script src="{{ asset('admin-assets/node_modules/d3/d3.min.js') }}"></script>
<script src="{{ asset('admin-assets/node_modules/c3/c3.min.js') }}"></script>
<!-- <script src="{{ asset('admin-assets/js/calendar.js') }}"></script>

 -->

 <script src="https://maps.googleapis.com/maps/api/js?key={{ Setting::get('google_api_key') }}&libraries=places" async defer></script>

<!-- Js file for Google Map-->
<script src="{{ asset('admin-assets/js/google-maps.js') }}"></script>

<script src="{{ asset('sparkleHover.js')}}"></script>

<script type="text/javascript">

    @if(isset($page)) $("#{{$page}}").addClass("mainactive"); @endif
        
    @if(isset($sub_page)) $("#{{$sub_page}}").addClass("subactive"); @endif

    $(document).ready(function() {

        $('.select2').select2({width: '100%'});

        $('#summernote').summernote({
            height: 300,
            tabsize: 2
        });

        $('select').on("select2:close", function () { $(this).focus(); });

        $('#expiry_date').datepicker({
            autoclose:true,
            format : 'dd-mm-yyyy',
            startDate: 'today',
        });

    });

    $('#visit-website').sparkleHover({
            colors : ['maroon', 'rgba(255, 99, 71, 0.4)', 'pink', 'teal', 'grey', 'orange'],
            num_sprites: 200,
            lifespan: 3000,
            radius: 800,
            sprite_size: 15,
            shape: "triangle", // circle, square
        });
    
    (function($) {
        'use strict';
        $(function() {
            $('#order-listing').DataTable({
                "aLengthMenu": [
                    [5, 10],
                    [5, 10]
                ],
                "iDisplayLength": 10,
                "search":false,
                "language": {
                    search: ""
                }
            });
            $('#order-listing').each(function() {
                var datatable = $(this);
                // SEARCH - Add the placeholder for Search and Turn this into in-line form control
                var search_input = datatable.closest('.dataTables_wrapper').find('div[id$=_filter] input');
                search_input.attr('placeholder', 'Search');
                search_input.removeClass('form-control-sm');
                // LENGTH - Inline-Form control
                var length_sel = datatable.closest('.dataTables_wrapper').find('div[id$=_length] select');
                length_sel.removeClass('form-control-sm');
            });
        });
    })(jQuery);

    $(document).ready(function(){
       
         setTimeout(function(){
             $('#order-listing_filter').hide();

             $('#order-listing_filter').hide();

             var replaced = $("#order-listing_info").html().replace(/entries/g,'Entries');
              $("#order-listing_info").html(replaced);
            
         },100);
     });

     $(document).ready(function(){
        $('.nonzero').on('input change', function (e) {
            var reg = /^0+/gi;
            if (this.value.match(reg)) {
                this.value = this.value.replace(reg, '');
            }
        });
     });
</script>