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
  $('#editForm').attr('action', "update-result/"+resultValue);

  $.ajax({
    type : "GET",
    url : "edit-result/"+resultValue,
    cache : false,
    success : function(response) {
      // console.log(response);
      $('#inputPrescribedTest').val(response.result.prescribed_test);
      $('#inputInteractedWith').val(response.result.interacted_with);
      $('#inputDescription').val(response.result.description);
      $('#inputclass').val(response.result.drug_class);
      $('#inputKeyword').val(response.result.keyword);
      $('#severityValue').val(response.result.risk_score);
    },
    error : function() {
      // alert('error');
    }
  })
});