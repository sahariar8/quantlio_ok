<div class="modal fade" id="formModel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
            </div>
          
            <div class="modal-body">
                <form id="editForm" name="editForm" class="form-horizontal" method="POST">
                  @csrf
                  <input type="hidden" id="edit_id" name="edit_id">
                  <div class="form-group mb-3">
                  <label for="inputTestName">Prescribed Test</label>
                  <input type="text" class="form-control" id="inputPrescribedTest" name="testName" disabled>
                  </div>
                  <div class="form-group mb-3">
                  <label for="inputclass">Drug Class</label>
                  <input type="text" class="form-control" id="inputclass" name="className" disabled>
                  </div>
                  <div class="form-group mb-3">
                  <label for="inputCondition">Conditions</label>
                  <textarea class="form-control" id="inputCondition" name="condition" disabled></textarea>
                  
                  </div>
                  <div class="form-group mb-3">
                  <label for="severityValue">Severity</label>
                  <input type="text" class="form-control" id="severityValue" name="severityValue">
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="submit_form">Save changes</button>
                  </div>
                </form>
            </div>
        </div>
    </div>
</div>