$(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": false,
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
  
  });
  
  // To open Delete metabolite pop-up
  $(document).on('click','.deletebtn', function(){
      let tradeId = $(this).val();
      $('#deleteModal').modal('show');
      $('#deleting_id').val(tradeId);
  });
  
  
  // To open Add Metabolite modal
  $(document).on('click', '#addTrades',  function(e) {
    $('#modelHeading').html("Create New Trade Item");
    $('.form-control').val("");
    $('.text-danger, label.error').html("");
    $('#tradeForm').attr('action', "insert-trades");
  });
  
  // To Validate
  $(document).ready(function() {
    $("#tradeForm").validate();
  });
  
  
  // If getting server side validation_error then show pop-up with prefilled data 
  $(document).ready(function() {
    if ($('#validation_error').length) {
      let tradeId = $('#validation_error').val().trim();
  
      if ($('#validation_error').val().trim() === '') {
        // Insert validation error
        $('#tradeForm').attr('action', "insert-trades");
        $('#modelHeading').html("Create New Trades Item");
        $('#exampleModal').modal('show');
      } else {
        // Update validation error
        $('#tradeForm').attr('action', "update-trades/"+tradeId);
        $('#modelHeading').html("Edit Trades Item");
        $('#exampleModal').modal('show');
        $('#edit_id').val(tradeId);
      }
    } 
  });
  
  
  // When click on edit test detail button
  $(document).on('click','.edit', function(){
    let tradeId = $(this).val();
    $('#modelHeading').html("Edit Trade Item");
    $('#exampleModal').modal('show');
    $('#edit_id').val(tradeId);
    $('.text-danger, label.error').html("");
    $('#tradeForm').attr('action', "update-trades/"+tradeId);
  
    $.ajax({
      type : "GET",
      url : "edit-trades/"+tradeId,
      cache : false,
      success : function(response) {
        $('#inputGeneric').val(response.trades.generic);
        $('#inputBrand').val(response.trades.brand);
      },
      error : function() {
        // alert('error');
      }
    })
  });
  