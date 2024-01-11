// to apply datatable
$(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": false,
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
  
  });
  
  // To open Delete Location pop-up
  $(document).on('click','.deletebtn', function(){
      var locationId = $(this).val();
      $('#deleteModal').modal('show');
      $('#deleting_id').val(locationId);
  });
  
  
  // To open Add Location modal
  $(document).on('click', '#addLocation',  function() {
    $('#modelHeading').html("Create New Lab Location");
    $('.form-control').val("");
    $('.text-danger, label.error').html("");
    $('#locationForm').attr('action', "insert-labLocation");
    $('#logo_img_display').attr('src','');
    
    const elementsListForMasking = document.querySelectorAll("#inputPhone, #inputFax");
    const elementsArrayForMasking = [...elementsListForMasking];

    elementsArrayForMasking.forEach(element => {
      element.addEventListener('input', function (e) {
        var x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
        e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
      });
    });

  });
  
  // To Validate
  $(document).ready(function() {
    $("#locationForm").validate();
  });
  
  
  // If getting server side validation_error then show pop-up with prefilled data 
  $(document).ready(function() {
    if ($('#validation_error').length) {
      var locationId = $('#validation_error').val().trim();
  
      if ($('#validation_error').val().trim() === '') {
        // Insert validation error
        $('#locationForm').attr('action', "insert-labLocation");
        $('#modelHeading').html("Create New Lab Location");
        $('#exampleModal').modal('show');
      } else {
        // Update validation error
        $('#locationForm').attr('action', "update-location/"+locationId);
        $('#modelHeading').html("Edit Location");
        $('#exampleModal').modal('show');
        $('#location_id').val(locationId);
      }
    } 
  });
  
  
  // When click on edit location detail button
  $(document).on('click','.edit', function(){
    var locationId = $(this).val();
    $('#modelHeading').html("Edit Location");
    $('#exampleModal').modal('show');
    $('#location_id').val(locationId);
    $('.text-danger, label.error').html("");
    $('#locationForm').attr('action', "update-location/"+locationId);

    const elementsListForMasking = document.querySelectorAll("#inputPhone, #inputFax");
    const elementsArrayForMasking = [...elementsListForMasking];

    elementsArrayForMasking.forEach(element => {
      element.addEventListener('input', function (e) {
        var x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
        e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
      });
    });
  
    $.ajax({
      type : "GET",
      url : "edit-location/"+locationId,
      cache : false,
      success : function(response) {
        console.log(response);
        $('#inputLocationName').val(response.locations.location);
        $('#inputPrintableLocation').val(response.locations.printable_location);
        $('#inputAddress').val(response.locations.address);
        $('#inputDirector').val(response.locations.director);
        $('#inputclia').val(response.locations.CLIA);
        $('#inputPhone').val(response.locations.phone);
        $('#inputFax').val(response.locations.fax);
        $('#inputWebsite').val(response.locations.website);
        $('#logo_img_display').attr('src','images/logo_image/'+response.locations.logo_image);
      },
      error : function() {
        // alert('error');
      }
    })
  });
  