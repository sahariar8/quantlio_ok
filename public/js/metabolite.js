$(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": false,
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
  
  });
  
  // To open Delete metabolite pop-up
  $(document).on('click','.deletebtn', function(){
      let metaboliteId = $(this).val();
      $('#deleteModal').modal('show');
      $('#deleting_id').val(metaboliteId);
  });
  
  
  // To open Add Metabolite modal
  $(document).on('click', '#addMetabolite',  function(e) {
    $('#modelHeading').html("Create New Metabolite Item");
    $('.form-control').val("");
    $('.text-danger, label.error').html("");
    $('#metaboliteForm').attr('action', "insert-metabolites");
  });
  
  // To Validate
  $(document).ready(function() {
    $("#metaboliteForm").validate();
  });
  
  
  // If getting server side validation_error then show pop-up with prefilled data 
  $(document).ready(function() {
    if ($('#validation_error').length) {
      let metaboliteId = $('#validation_error').val().trim();
  
      if ($('#validation_error').val().trim() === '') {
        // Insert validation error
        $('#metaboliteForm').attr('action', "insert-metabolites");
        $('#modelHeading').html("Create New Metabolite Item");
        $('#exampleModal').modal('show');
      } else {
        // Update validation error
        $('#metaboliteForm').attr('action', "update-metabolites/"+metaboliteId);
        $('#modelHeading').html("Edit Test Metabolites Item");
        $('#exampleModal').modal('show');
        $('#edit_id').val(metaboliteId);
      }
    } 
  });
  
  
  // When click on edit test detail button
  $(document).on('click','.edit', function(){
    let metaboliteId = $(this).val();
    $('#modelHeading').html("Edit Metabolite Item");
    $('#exampleModal').modal('show');
    $('#edit_id').val(metaboliteId);
    $('.text-danger, label.error').html("");
    $('#metaboliteForm').attr('action', "update-metabolites/"+metaboliteId);
  
    $.ajax({
      type : "GET",
      url : "edit-metabolites/"+metaboliteId,
      cache : false,
      success : function(response) {
        $('#inputTestName').val(response.metabolite.testName);
        $('#inputClass').val(response.metabolite.class);
        $('#inputParent').val(response.metabolite.parent);
        $('#inputMetabolite').val(response.metabolite.metabolite);
      },
      error : function() {
        // alert('error');
      }
    })
  });
  