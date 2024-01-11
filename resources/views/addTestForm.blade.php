<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modelHeading">Modal title</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form id="testForm" name="testForm" class="form-horizontal" method="POST">
            @csrf
            <div class="modal-body">
              <input type="hidden" id="edit_id" name="edit_id">
              <div class="form-group mb-3">
                <label for="inputTestName">{{ __('Test Name') }}</label>
                <input type="text" class="form-control" id="inputTestName" name="testName" required minlength="2" value="{{ old('testName') }}">
                <span id="spanTestName" class="spanErrorText" style="color:red;"></span>
                @if ($errors->has('testName'))
                      <span class="text-danger">{{ $errors->first('testName') }}</span>
                @endif
              </div>

              <div class="form-group mb-3">
                <label for="inputclass">Class</label>
                <input type="text" class="form-control" id="inputclass" name="testClass" required minlength="2" value="{{ old('testClass') }}">
                <span id="spanClassName" class="spanErrorText" style="color:red;"></span>
                @if ($errors->has('testClass'))
                      <span class="text-danger">{{ $errors->first('testClass') }}</span>
                @endif
              </div>

              <div class="form-group mb-3">
                <label for="inputDescription">{{ __('Description') }}</label>
                <input type="text" class="form-control" id="inputDescription" name="testDescription" value="{{ old('testDescription') }}">
              </div>

              <div class="form-group mb-3">
                <label for="inputCutoff">{{ __('Cutoff (LLOQ)') }}</label>
                <input type="number" class="form-control" id="inputCutoff" name="testCutoff" required value="{{ old('testCutoff') }}">
                <span id="spanCutoff" class="spanErrorText" style="color:red;"></span>
                @if ($errors->has('testCutoff'))
                      <span class="text-danger">{{ $errors->first('testCutoff') }}</span>
                @endif
              </div>

              <div class="form-group mb-3">
                <label for="inputRange">{{ __('Range (ULOQ)') }}</label>
                <input type="number" class="form-control" id="inputRange" name="testRange" required value="{{ old('testRange') }}"> 
                <span id="spanTestRange" class="spanErrorText" style="color:red;"></span>
                @if ($errors->has('testRange'))
                      <span class="text-danger">{{ $errors->first('testRange') }}</span>
                @endif
              </div>
            </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary" id="submit_form">{{ __('Save') }}</button>
          </div>
          </form>
        </div>
      </div>
    </div>