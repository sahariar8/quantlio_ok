$(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": false,
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
  
  });
  
  // To open Delete Test Detail pop-up
  $(document).on('click','.deletebtn', function(){
      var testId = $(this).val();
      $('#deleteModal').modal('show');
      $('#deleting_id').val(testId);
  });
  
  
  // To open Add Test Detail modal
  $(document).on('click', '#addTest',  function() {
    $('#modelHeading').html("Create New Test");
    $('.form-control').val("");
    $('.text-danger, label.error').html("");
    $('#testForm').attr('action', "insert-testDetails");
  });
  
  // To Validate
  $(document).ready(function() {
    $("#testForm").validate();
  });
  
  
  // If getting server side validation_error then show pop-up with prefilled data 
  $(document).ready(function() {
    if ($('#validation_error').length) {
      var testId = $('#validation_error').val().trim();
  
      if ($('#validation_error').val().trim() === '') {
        // Insert validation error
        $('#testForm').attr('action', "insert-testDetails");
        $('#modelHeading').html("Create New Test");
        $('#exampleModal').modal('show');
      } else {
        // Update validation error
        $('#testForm').attr('action', "update-testDetails/"+testId);
        $('#modelHeading').html("Edit Test Details");
        $('#exampleModal').modal('show');
        $('#edit_id').val(testId);
      }
    } 
  });
  
  
  // When click on edit test detail button
  $(document).on('click','.edit', function(){
    var testId = $(this).val();
    $('#modelHeading').html("Edit Test Detail");
    $('#exampleModal').modal('show');
    $('#edit_id').val(testId);
    $('.text-danger, label.error').html("");
    $('#testForm').attr('action', "update-testDetails/"+testId);
  
    $.ajax({
      type : "GET",
      url : "edit-testDetails/"+testId,
      cache : false,
      success : function(response) {
        // console.log(response);
        $('#inputTestName').val(response.test.dendi_test_name);
        $('#inputclass').val(response.test.class);
        $('#inputDescription').val(response.test.description);
        $('#inputCutoff').val(response.test.LLOQ);
        $('#inputRange').val(response.test.ULOQ);
      },
      error : function() {
        // alert('error');
      }
    })
  });
  