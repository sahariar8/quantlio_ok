// Initialize Select2 Elements
$('.select2').select2();

//Initialize Select2 Elements
$('.select2bs4').select2({
  theme: 'bootstrap4',
  allowClear: true
});

$('#reported_date_from').datetimepicker({
  format: 'YYYY-MM-DD'
});

$('#reported_date_to').datetimepicker({
  format: 'YYYY-MM-DD'
});

$(function () {
  $("#example1").DataTable({
    "responsive": true, "lengthChange": false, "autoWidth": false,
    "buttons": ["excel"]
  }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
  $('#example2').DataTable({
    "paging": true,
    "lengthChange": false,
    "searching": false,
    "ordering": true,
    "info": true,
    "autoWidth": false,
    "responsive": true,
  });
});

$(document).on('click', '#export-data',  function() {
  $('#dataFilter').attr('action', "export-data");
});
$(document).on('click', '#export-data-by-patients',  function() {
  $('#dataFilter').attr('action', "export-data-by-patients");
});
$(document).on('click', '#ddi-report',  function() {
  $('#dataFilter').attr('action', "export-report");
});
$(document).on('click', '#detailed-ddi-report',  function() {
  $('#dataFilter').attr('action', "export-detailed-ddi-report");
});
$(document).on('click', '#ci-report',  function() {
  $('#dataFilter').attr('action', "export-ci-report");
});

$.ajaxSetup({
  headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
  }
});
$(document).ready(function () {
  $('#state-dropdown').on('change', function () {
      var selectedValues = $('#state-dropdown').val();
      $("#clinic-dropdown").html('');
      $.ajax({
          url: "fetch-clinics",
          type: "POST",
          data: {
            state: selectedValues
          },
          dataType: 'json',
          success: function (result) {
            console.log(result);
            // $('#clinic-dropdown').html('<option value="">-- Select Clinic --</option>');
            $.each(result.clinics, function (key, value) {
                $("#clinic-dropdown").append('<option value="' + value
                    .account_name + '">' + value.account_name + '</option>');
            });
            // $('#city-dropdown').html('<option value="">-- Select City --</option>');
          },
          error: function (result, textStatus, errorThrown) {
            // console.log(result);
          },
      });
  });
  $('#clinic-dropdown').on('change', function () {
    var clinics = $('#clinic-dropdown').val();
    $("#provider-dropdown").html('');
    $.ajax({
        url: "fetch-providers",
        type: "POST",
        data: {
          clinic: clinics
        },
        dataType: 'json',
        success: function (result) {
          console.log(result);
          $.each(result.providers, function (key, value) {
              $("#provider-dropdown").append('<option value="' + value
                  .provider_name + '">' + value.provider_name + '</option>');
          });
          // $('#city-dropdown').html('<option value="">-- Select City --</option>');
        },
        error: function (result, textStatus, errorThrown) {
          // console.log(result);
        },
    });
  });
  $('#provider-dropdown').on('change', function () {
    var providers = $('#provider-dropdown').val();
    $("#patient-dropdown").html('');
    $.ajax({
        url: "fetch-patients",
        type: "POST",
        data: {
          provider: providers
        },
        dataType: 'json',
        success: function (result) {
          console.log(result);
          $.each(result.patients, function (key, value) {
              $("#patient-dropdown").append('<option value="' + value
                  .patient_name + '">' + value.patient_name + '</option>');
          });
          // $('#city-dropdown').html('<option value="">-- Select City --</option>');
        },
        error: function (result, textStatus, errorThrown) {
          // console.log(result);
        },
    });
  });

  $("#lab").select2();
  $("#checkbox").click(function(){
    if($("#checkbox").is(':checked') ){
      $("#lab > option").prop("selected","selected");
      $("#lab").trigger("change");
    }else{
      $("#lab > option").removeAttr("selected");
      $("#lab").val('').change();
    }
  });

});