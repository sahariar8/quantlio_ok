<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modelHeading"> </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form id="roleForm" name="roleForm" class="form-horizontal" method="POST">
            @csrf
            <div class="modal-body">
              <input type="hidden" id="role_id" name="role_id">
                <div class="form-group mb-3">
                    <label for="inputRoleName">{{ __('Role Name') }}</label>
                    <input type="text" class="form-control" id="inputRoleName" name="name" required minlength="2" value="{{ old('name') }}">
                    <span id="spanRoleName" class="spanErrorText" style="color:red;"></span>
                    @if ($errors->has('inputRoleName'))
                        <span class="text-danger">{{ $errors->first('inputRoleName') }}</span>
                    @endif
                </div>

                <div class="form-group mb-3">
                    <label for="role">Permission</label>
                    <div class="row">
                    @foreach($permissionGroup as $permissionGroupvalue)
                    
                        <div class="col-sm-6">
                            <!-- checkbox -->
                            <div class="form-group">
                                <div class="form-check">
                                <input class="form-check-input checkbox" type="checkbox" value="{{ $permissionGroupvalue->id }}">
                                <label class="form-check-label">{{ $permissionGroupvalue->name}}</label>
                                </div>
                            </div>
                            <div class="form-group mb-5">
                                <select class="select2 list_{{ $permissionGroupvalue->id }}" name="permissionValues[]" multiple="multiple" data-placeholder="Select Option" style="width: 100%;">
                                    @foreach($permissionGroupvalue->permissions as $permissionValue)
                                    <option value="{{ $permissionValue->id }}">{{ $permissionValue->name }}</option>
                                    @endforeach
                                </select>
                            </div> 
                        </div>
                    @endforeach
                    </div>
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