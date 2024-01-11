// to apply datatable
$(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": false,
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
  
  });
  
  // To open Delete Comment pop-up
  $(document).on('click','.deletebtn', function(){
      var commentId = $(this).val();
      $('#deleteModal').modal('show');
      $('#deleting_id').val(commentId);
  });
  
  
  // To open Add Comment modal
  $(document).on('click', '#addComment',  function() {
    $('#modelHeading').html("Create New Comment");
    $('.form-control').val("");
    $('.text-danger, label.error').html("");
    $('#commentForm').attr('action', "insert-comment");
  });
  
  // To Validate
  $(document).ready(function() {
    $("#commentForm").validate();
  });
  
  
  // If getting server side validation_error then show pop-up with prefilled data 
  $(document).ready(function() {
    if ($('#validation_error').length) {
      var commentId = $('#validation_error').val().trim();
  
      if ($('#validation_error').val().trim() === '') {
        // Insert validation error
        $('#commentForm').attr('action', "insert-comment");
        $('#modelHeading').html("Create New Comment");
        $('#exampleModal').modal('show');
      } else {
        // Update validation error
        $('#commentForm').attr('action', "update-comment/"+commentId);
        $('#modelHeading').html("Edit Comment");
        $('#exampleModal').modal('show');
        $('#comment_id').val(commentId);
      }
    } 
  });
    //Initialize Select2 Elements
    $('.select2').select2()

    //Initialize Select2 Elements
    $('.select2bs4').select2({
    theme: 'bootstrap4',
    placeholder: "Select",
    allowClear: true
    })

    $("#section").select2();
    $("#checkbox").click(function(){
    if($("#checkbox").is(':checked') ){
      $("#section > option").prop("selected","selected");
      $("#section").trigger("change");
    }else{
      $("#section > option").removeAttr("selected");
      $("#section").val('').change();
    }
    });
    
  // When click on edit comment detail button
  $(document).on('click','.edit', function(){
    var commentId = $(this).val();
    $('#modelHeading').html("Edit Comment");
    $('#exampleModal').modal('show');
    $('#comment_id').val(commentId);
    $('.text-danger, label.error').html("");
    $('#commentForm').attr('action', "update-comment/"+commentId);
  
    $.ajax({
      type : "GET",
      url : "edit-comment/"+commentId,
      cache: false,
      success : function(response) {
        console.log(response);
        $('#section').val(response.comment_section).trigger('change');
        $('#inputTestName').val(response.comments.test_id).trigger('change');
        $('#inputComment').val(response.comments.comment);
      },
      error : function() {
        // alert('error');
      }
    })
  });
  