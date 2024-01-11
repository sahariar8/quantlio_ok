$(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": false,
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
  
  });
  
  // To open Delete metabolite pop-up
  $(document).on('click','.deletebtn', function(){
      let rxcuiId = $(this).val();
      $('#deleteModal').modal('show');
      $('#deleting_id').val(rxcuiId);
  });
  
  
  // To open Add Metabolite modal
  $(document).on('click', '#addRxcui',  function(e) {
    $('#modelHeading').html("Create New RxCUI Item");
    $('.form-control').val("");
    $('.text-danger, label.error').html("");
    $('#rxcuiForm').attr('action', "insert-rxcui");
  });
  
  // To Validate
  $(document).ready(function() {
    $("#rxcuiForm").validate();
  });
  
  
  // If getting server side validation_error then show pop-up with prefilled data 
  $(document).ready(function() {
    if ($('#validation_error').length) {
      let rxcuiId = $('#validation_error').val().trim();
  
      if ($('#validation_error').val().trim() === '') {
        // Insert validation error
        $('#rxcuiForm').attr('action', "insert-rxcui");
        $('#modelHeading').html("Create New RxCUI Item");
        $('#exampleModal').modal('show');
      } else {
        // Update validation error
        $('#rxcuiForm').attr('action', "update-rxcui/"+rxcuiId);
        $('#modelHeading').html("Edit RxCUI Item");
        $('#exampleModal').modal('show');
        $('#edit_id').val(rxcuiId);
      }
    } 
  });
  
  
  // When click on edit test detail button
  $(document).on('click','.edit', function(){
    let rxcuiId = $(this).val();
    $('#modelHeading').html("Edit RxCUI Item");
    $('#exampleModal').modal('show');
    $('#edit_id').val(rxcuiId);
    $('.text-danger, label.error').html("");
    $('#rxcuiForm').attr('action', "update-rxcui/"+rxcuiId);
  
    $.ajax({
      type : "GET",
      url : "edit-rxcui/"+rxcuiId,
      cache : false,
      success : function(response) {
        console.log(response)
        $('#inputDrugsName').val(response.rxcuiItems.drugsName);
        $('#inputRxcui').val(response.rxcuiItems.RxCUI);
        $('#inputParentDrugsName').val(response.rxcuiItems.parentDrugName);
        $('#inputParentRxCUI').val(response.rxcuiItems.parentRxcui);
        $('#inputAnalyte').val(response.rxcuiItems.analyt);
      },
      error : function() {
        // alert('error');
      }
    })
  });
  