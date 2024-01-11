// to apply datatable
$(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": false,
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
  
  });
  
  // To open Delete Role pop-up
  $(document).on('click','.deletebtn', function(){
      var roleId = $(this).val();
      $('#deleteModal').modal('show');
      $('#deleting_id').val(roleId);
  });
  
  
  // To open Add Role modal
  $(document).on('click', '#addRole',  function() {
    $('#modelHeading').html("Create New Role");
    $('.form-control').val("");
    $('.text-danger, label.error').html("");
    $('#roleForm').attr('action', "insert-role");
  });
  
  // To Validate
  $(document).ready(function() {
    $("#roleForm").validate();
  });
  
  
  // If getting server side validation_error then show pop-up with prefilled data 
  $(document).ready(function() {
    if ($('#validation_error').length) {
      var roleId = $('#validation_error').val().trim();
  
      if ($('#validation_error').val().trim() === '') {
        // Insert validation error
        $('#roleForm').attr('action', "insert-role");
        $('#modelHeading').html("Create New Role");
        $('#exampleModal').modal('show');
      } else {
        // Update validation error
        $('#roleForm').attr('action', "update-role/"+roleId);
        $('#modelHeading').html("Edit Role");
        $('#exampleModal').modal('show');
        $('#role_id').val(roleId);
      }
    } 
  });
  
   //Initialize Select2 Elements
   $('.select2').select2();

   //Initialize Select2 Elements
   $('.select2').select2({
   theme: 'bootstrap4',
   placeholder: "Select Option",
   allowClear: true
   })
   $(".js-example-placeholder-multiple").select2({
   placeholder: "Select Option"
   });

   $("#listId").select2();
   $(".checkbox").click(function(){
     var permissionGroupId = $(this).val();

     if($(".checkbox").is(':checked') ){
         $(".list_" + permissionGroupId + " > option").prop("selected","selected");
         $(".list_" + permissionGroupId).trigger("change");
     }else{
        $(".list_" + permissionGroupId).val('').change();
     }
   });
  // When click on edit Role button
  $(document).on('click','.edit', function(){
    var roleId = $(this).val();
    $('#modelHeading').html("Edit Role");
    $('#exampleModal').modal('show');
    $('#role_id').val(roleId);
    $('.text-danger, label.error').html("");
    $('#roleForm').attr('action', "update-role/"+roleId);
  
    $.ajax({
      type : "GET",
      url : "edit-role/"+roleId,
      cache : false,
      success : function(response) {
        console.log(response);
        $('#inputRoleName').val(response.role.name);
        $('.select2').val(Object.values(response.rolePermissions)).change();
      },
      error : function() {
        // alert('error');
      }
    })
  });
  