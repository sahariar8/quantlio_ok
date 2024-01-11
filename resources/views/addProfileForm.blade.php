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
          <form id="profileForm" name="profileForm" class="form-horizontal" method="POST">
            @csrf
            <div class="modal-body">
              <input type="hidden" id="profile_id" name="profile_id">

              <div class="form-group mb-3">
                <label for="inputProfileName">{{ __('Profile Name') }}</label>
                <input type="text" class="form-control" id="inputProfileName" name="profileName" required minlength="2" value="{{ old('profileName') }}">
                <span id="spanProfileName" class="spanErrorText" style="color:red;"></span>
                @if ($errors->has('profileName'))
                      <span class="text-danger">{{ $errors->first('profileName') }}</span>
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