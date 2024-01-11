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