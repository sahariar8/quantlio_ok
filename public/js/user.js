// to apply datatable
$(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": false,
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
  
  });
  
  // To open Delete User pop-up
  $(document).on('click','.deletebtn', function(){
      var userId = $(this).val();
      $('#deleteModal').modal('show');
      $('#deleting_id').val(userId);
  });
  
  
  // To open Add User modal
  $(document).on('click', '#addUser',  function() {
    $('#modelHeading').html("Create New User");
    $('.form-control').val("");
    $("#password_field").show();
    $('.text-danger, label.error').html("");
    $('#userForm').attr('action', "insert-user");
  });
  
   //Initialize Select2 Elements
   $('.select2').select2();

   //Initialize Select2 Elements
   $('.select2').select2({
     theme: 'bootstrap4',
     placeholder: "Select Role",
     allowClear: true
   });
  // To Validate
  $(document).ready(function() {
    $("#userForm").validate();
  });
  
  
  // If getting server side validation_error then show pop-up with prefilled data 
  $(document).ready(function() {
    if ($('#validation_error').length) {
      var userId = $('#validation_error').val().trim();
  
      if ($('#validation_error').val().trim() === '') {
        // Insert validation error
        $('#userForm').attr('action', "insert-user");
        $('#modelHeading').html("Create New User");
        $('#exampleModal').modal('show');
      } else {
        // Update validation error
        $('#userForm').attr('action', "update-user/"+userId);
        $('#modelHeading').html("Edit User");
        $('#exampleModal').modal('show');
        $('#user_id').val(userId);
      }
    } 
  });
  
  
  // When click on edit user detail button
  $(document).on('click','.edit', function(){
    var userId = $(this).val();
    $('#modelHeading').html("Edit User");
    $('#exampleModal').modal('show');
    $('#user_id').val(userId);
    $("#password_field").hide();
    $('.text-danger, label.error').html("");
    $('#userForm').attr('action', "update-user/"+userId);
  
    $.ajax({
      type : "GET",
      url : "edit/"+userId,
	cache: false,
      success : function(response) {
        // console.log(response);
        $('#username').val(response.detail.username);
        $('#email').val(response.detail.email);  
        $('.select2').val(Object.keys(response.userRole)).trigger('change');
      },
      error : function() {
        // alert('error');
      }
    })
  });
  