<!-- Modal -->
<div class="modal fade" id="exampleModal" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modelHeading"> </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form id="commentForm" name="commentForm" class="form-horizontal" method="POST">
            @csrf
            <div class="modal-body">
              <input type="hidden" id="comment_id" name="comment_id">
                <div class="form-group mb-3">
                <label>Section</label>
                <div class="selectAll" style="float: right;"> 
                  <input type="checkbox" id="checkbox" style="margin-right: 5px;">Select All
                </div>
                <select class="js-example-placeholder-multiple select2bs4" name="section[]" id="section" multiple="multiple" data-placeholder="Select Sections" style="width: 100%;" required>
                @foreach($sections as $section)
                <option value="{{ $section->id }}">{{ $section->name }}</option>
                @endforeach
                </select>
                <span id="spanSectionName" class="spanErrorText" style="color:red;"></span>
                @if ($errors->has('section'))
                    <span class="text-danger">{{ $errors->first('section') }}</span>
                @endif
                </div>
                <div class="form-group mb-3">
                <label>Test Name</label>
                <select class="select2bs4" name="inputTestName" id="inputTestName" data-placeholder="Select Tests" style="width: 100%;" required>
                @foreach($testDetails as $testDetail)
                <option value="{{ $testDetail->id }}">{{ $testDetail->dendi_test_name }}</option>
                @endforeach
                </select>
                <span id="spanTestName" class="spanErrorText" style="color:red;"></span>

                @if ($errors->has('inputTestName'))
                    <span class="text-danger">{{ $errors->first('inputTestName') }}</span>
                @endif
                </div>
                <div class="form-group mb-3">
                <label for="inputComment">Comment</label>
                <input type="text" class="form-control" name="inputComment" id="inputComment" required value="{{ old('inputComment') }}">
                <span id="spanComment" class="spanErrorText" style="color:red;"></span>

                @if ($errors->has('inputComment'))
                    <span class="text-danger">{{ $errors->first('inputComment') }}</span>
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