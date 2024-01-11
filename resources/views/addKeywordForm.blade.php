<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modelHeading"> </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form id="keywordForm" name="keywordForm" class="form-horizontal" method="POST">
            @csrf
            <div class="modal-body">
              <input type="hidden" id="keyword_id" name="keyword_id">
              <div class="form-group mb-3">
                <label for="inputTestName">{{ __('Profile') }}</label>
                
                <div class="selectAll" style="float: right;"> 
                  <input type="checkbox" id="checkbox" style="margin-right: 5px;">Select All
                </div>
                <select class="js-example-placeholder-multiple select2bs4" name="profile[]" id="profile" multiple="multiple" data-placeholder="Select profile" style="width: 100%;" required>
                @foreach($profiles as $profile)
                <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                @endforeach
                </select>
                <span id="spanProfile" class="spanErrorText" style="color:red;"></span>
                @if ($errors->has('profile'))
                  <span class="text-danger">{{ $errors->first('profile') }}</span>
                @endif
              </div>

              <div class="form-group mb-3">
              <label for="inputPrimaryKeyword">Primary Keyword</label>
              <input type="text" class="form-control" name="primary" id="inputPrimaryKeyword" required minlength="2" value="{{ old('primary') }}">
              <span id="spanPrimary" class="spanErrorText" style="color:red;"></span>
              @if ($errors->has('primary'))
                  <span class="text-danger">{{ $errors->first('primary') }}</span>
              @endif
              </div>

              <div class="form-group mb-3">
              <label for="inputSecondaryKeyword">Secondary Keyword</label>
              <input type="text" class="form-control" name="secondary" id="inputSecondaryKeyword" value="{{ old('secondary') }}">
              </div>
              <div class="form-group mb-3">
              <label for="inputResultantKeyword">Resultant Keyword</label>
              <input type="text" class="form-control" name="result" id="inputResultantKeyword" required minlength="2" value="{{ old('result') }}">
              <span id="spanResult" class="spanErrorText" style="color:red;"></span>
              @if ($errors->has('result'))
                  <span class="text-danger">{{ $errors->first('result') }}</span>
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