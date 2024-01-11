// to apply datatable
$(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": false,"paging": true,
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
});

// When click on edit test detail button
$(document).on('click','.edit', function(){
  var resultValue = $(this).val();
  $('#modelHeading').html("Edit Severity");
  $('#formModel').modal('show');
  $('#edit_id').val(resultValue);
  $('.text-danger, label.error').html("");
  $('#editForm').attr('action', "update-details/"+resultValue);

  $.ajax({
    type : "GET",
    url : "edit-details/"+resultValue,
    success : function(response) {
      // console.log(response);
      $('#inputPrescribedTest').val(response.result.prescribed_test);
      $('#inputclass').val(response.result.drug_class);
      $('#inputCondition').val(response.result.conditions);
      $('#severityValue').val(response.result.risk_score);
    },
    error : function() {
      // alert('error');
    }
  })
});