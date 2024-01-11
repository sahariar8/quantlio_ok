// to apply datatable
$(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": false,
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
  
  });
  
  // To open Add Profile modal
  $(document).on('click', '#addProfile',  function() {
    $('#modelHeading').html("Create New Profile");
    $('.form-control').val("");
    $('.text-danger, label.error').html("");
    $('#profileForm').attr('action', "insert-profile");
  });
  
  // To open Delete Profile pop-up
  $(document).on('click','.deletebtn', function(){
      var testId = $(this).val();
      $('#deleteModal').modal('show');
      $('#deleting_id').val(testId);
  });
  
  // To Validate
  $(document).ready(function() {
    $("#profileForm").validate();
  });
  
  // If getting server side validation_error then show pop-up with prefilled data 
  $(document).ready(function() {
    if ($('#validation_error').length) {
      var profileId = $('#validation_error').val().trim();
  
      if ($('#validation_error').val().trim() === '') {
        // Insert validation error
        $('#profileForm').attr('action', "insert-profile");
        $('#modelHeading').html("Create New Profile");
        $('#exampleModal').modal('show');
      } else {
        // Update validation error
        $('#profileForm').attr('action', "update-profile/"+profileId);
        $('#modelHeading').html("Edit Profile");
        $('#exampleModal').modal('show');
        $('#profile_id').val(profileId);
      }
    } 
  });
  
  
  // When click on edit profile button
  $(document).on('click','.edit', function(){
    var profileId = $(this).val();
    $('#modelHeading').html("Edit Profile");
    $('#exampleModal').modal('show');
    $('#profile_id').val(profileId);
    $('.text-danger, label.error').html("");
    $('#profileForm').attr('action', "update-profile/"+profileId);
  
    $.ajax({
      type : "GET",
      url : "edit-profile/"+profileId,
      cache : false,
      success : function(response) {
        $('#inputProfileName').val(response.profile.name);
      },
      error : function() {
        // alert('error');
      }
    })
  });
  