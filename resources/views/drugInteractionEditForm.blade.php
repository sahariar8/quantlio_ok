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
                  <label for="inputPrescribedTest">Prescribed Test</label>
                  <input type="text" class="form-control" id="inputPrescribedTest" name="inputPrescribedTest" disabled>
                  </div>
                  <div class="form-group mb-3">
                  <label for="inputInteractedWith">Interacted With</label>
                  <input type="text" class="form-control" id="inputInteractedWith" name="inputInteractedWith" disabled>
                  </div>
                  <div class="form-group mb-3">
                  <label for="inputTestName">Description</label>
                  <textarea class="form-control" id="inputDescription" name="inputDescription" disabled></textarea>
                  </div>
                  <div class="form-group mb-3">
                  <label for="inputclass">Drug Class</label>
                  <input type="text" class="form-control" id="inputclass" name="inputclass" disabled>
                  </div>
                  <div class="form-group mb-3">
                  <label for="inputKeyword">Keyword</label>
                  <input type="text" class="form-control" id="inputKeyword" name="inputKeyword" disabled>
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