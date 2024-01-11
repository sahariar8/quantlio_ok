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
          <form id="locationForm" name="locationForm" class="form-horizontal" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
              <input type="hidden" id="location_id" name="location_id">
              <div class="form-group mb-3">
                <label for="inputTestName">{{ __('Query Info Location') }}</label>
                <input type="text" class="form-control" id="inputLocationName" name="locationName" required minlength="2" value="{{ old('locationName') }}">
                <span id="spanLocationName" class="spanErrorText" style="color:red;"></span>
                @if ($errors->has('locationName'))
                  <span class="text-danger">{{ $errors->first('locationName') }}</span>
                @endif
              </div>

              <div class="form-group mb-3">
                <label for="inputPrintableLocation">{{ __('Printed Info Location') }}</label>
                <input type="text" class="form-control" id="inputPrintableLocation" name="printableLocationName" required minlength="2" value="{{ old('printableLocationName') }}">
                <span id="spanPrintableLocationName" class="spanErrorText" style="color:red;"></span>
                @if ($errors->has('printableLocationName'))
                  <span class="text-danger">{{ $errors->first('printableLocationName') }}</span>
                @endif
              </div>

              <div class="form-group mb-3">
                <label for="inputAddress">Address</label>
                <input type="text" class="form-control" id="inputAddress" name="address" required minlength="2" value="{{ old('address') }}">
                <span id="spanAddress" class="spanErrorText" style="color:red;"></span>
                @if ($errors->has('address'))
                  <span class="text-danger">{{ $errors->first('address') }}</span>
                @endif
              </div>
              <div class="form-group mb-3">
                <label for="inputDirector">Director</label>
                <input type="text" class="form-control" id="inputDirector" name="director" required minlength="2" value="{{ old('director') }}">
                <span id="spanDirector" class="spanErrorText" style="color:red;"></span>
                @if ($errors->has('director'))
                  <span class="text-danger">{{ $errors->first('director') }}</span>
            @endif
              </div>
              <div class="form-group mb-3">
                <label for="inputclia">CLIA</label>
                <input type="text" class="form-control" id="inputclia" name="clia" required minlength="2" value="{{ old('clia') }}">
                <span id="spanClia" class="spanErrorText" style="color:red;"></span>
                @if ($errors->has('clia'))
                  <span class="text-danger">{{ $errors->first('clia') }}</span>
            @endif
              </div>
              <div class="form-group mb-3">
                <label for="inputPhone">Phone</label>
                <input type="text" class="form-control" id="inputPhone" placeholder="(555) 555-5555" name="phone" required value="{{ old('phone') }}">
                <span id="spanPhone" class="spanErrorText" style="color:red;"></span>
                @if ($errors->has('phone'))
                  <span class="text-danger">{{ $errors->first('phone') }}</span>
            @endif
              </div>
              <div class="form-group mb-3">
                <label for="inputFax">Fax</label>
                <input type="text" class="form-control" id="inputFax" placeholder="(555) 555-5555" name="fax" value="{{ old('fax') }}">
                <span id="spanFax" class="spanErrorText" style="color:red;"></span>
                @if ($errors->has('fax'))
                  <span class="text-danger">{{ $errors->first('fax') }}</span>
            @endif
              </div>
              <div class="form-group mb-3">
                <label for="inputWebsite">Website</label>
                <input type="text" class="form-control" id="inputWebsite" name="website" value="{{ old('website') }}">
                <span id="spanWebsite" class="spanErrorText" style="color:red;"></span>
                @if ($errors->has('website'))
                  <span class="text-danger">{{ $errors->first('website') }}</span>
                @endif
              </div>
              <div class="form-group mb-3">
                <label for="inputWebsite">Logo</label>
                <input type="file" name="logo_img" id="logo_img" class="form-control">
                <br />
                <img id="logo_img_display" src="" width="100px">
                <span id="spanWebsite" class="spanErrorText" style="color:red;"></span>
                @if ($errors->has('logo_img'))
                  <span class="text-danger">{{ $errors->first('logo_img') }}</span>
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