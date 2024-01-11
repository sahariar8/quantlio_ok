// to apply datatable
$(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": false,
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
  
  });
  
   // Initialize Select2 Elements
     $('.select2').select2();
  
    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4',
      placeholder: "Select profile",
      allowClear: true
    });
  
    $(".js-example-placeholder-multiple").select2({
      placeholder: "Select profile"
    });
  
    $("#profile").select2();
    $("#checkbox").click(function(){
    if($("#checkbox").is(':checked') ){
      $("#profile > option").prop("selected","selected");
      $("#profile").trigger("change");
    }else{
      $("#profile > option").removeAttr("selected");
      // $("#profiles").trigger("change");
      $("#profile").val('').change();
    }
    });
  
  // To open Delete Keyword pop-up
  $(document).on('click','.deletebtn', function(){
      var keywordId = $(this).val();
      $('#deleteModal').modal('show');
      $('#deleting_id').val(keywordId);
  });
  
  
  // To open Add Keyword modal
  $(document).on('click', '#addKeyword',  function() {
    $("#profile").val('').trigger('change'); // to reset select 2
    $('#modelHeading').html("Create New Keyword");
    $('.form-control').val("");
    $('.text-danger, label.error').html("");
    $('#keywordForm').attr('action', "insert-keyword");
  });
  
  // To Validate
  $(document).ready(function() {
    $("#keywordForm").validate();
  });
  
  
  // If getting server side validation_error then show pop-up with prefilled data 
  $(document).ready(function() {
    if ($('#validation_error').length) {
      var keywordId = $('#validation_error').val().trim();
  
      if ($('#validation_error').val().trim() === '') {
        // Insert validation error
        $('#keywordForm').attr('action', "insert-keyword");
        $('#modelHeading').html("Create New Keyword");
        $('#exampleModal').modal('show');
      } else {
        // Update validation error
        $('#keywordForm').attr('action', "update-keyword/"+keywordId);
        $('#modelHeading').html("Edit Keyword");
        $('#exampleModal').modal('show');
        $('#keyword_id').val(keywordId);
      }
    } 
  });
  
  
  // When click on edit keyword button
  $(document).on('click','.edit', function(){
    var keywordId = $(this).val();
    $('#modelHeading').html("Edit Keyword");
    $('#exampleModal').modal('show');
    $('#keyword_id').val(keywordId);
    $('.text-danger, label.error').html("");
    $('#keywordForm').attr('action', "update-keyword/"+keywordId);
  
    $.ajax({
      type : "GET",
      url : "edit-keyword/"+keywordId,
      cache : false,
      success : function(response) {
        // console.log(response);
        //$("#profile").val(response.detail.profile_id).trigger('change');
        // $("#profile").val([2,16,3]).trigger('change');
        $("#profile").val(response.keyword_profile).trigger('change');
        $('#inputPrimaryKeyword').val(response.detail.primary_keyword);
        $('#inputSecondaryKeyword').val(response.detail.secondary_keyword);
        $('#inputResultantKeyword').val(response.detail.resultant_keyword);
      },
      error : function() {
        // alert('error');
      }
    })
  });
  