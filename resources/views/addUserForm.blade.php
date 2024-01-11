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
          <form id="userForm" name="userForm" class="form-horizontal" method="POST" autocomplete="off">
          @csrf
            <div class="modal-body">
              <input type="hidden" id="user_id" name="user_id">
            <div class="form-group mb-3">
            <label for="username">{{ __('Username') }}</label>
            <input type="text" class="form-control" name="username" id="username" required minlength="2" value="{{ old('username') }}"> 
            <span id="spanUserName" class="spanErrorText" style="color:red;"></span>
            @if ($errors->has('username'))
                <span class="text-danger">{{ $errors->first('username') }}</span>
            @endif
            </div>

            <div class="form-group mb-3">
            <label for="email">Email</label>
            <input type="email" class="form-control" name="email" id="email" required value="{{ old('email') }}" autocomplete="off">
            <span id="spanEmail" class="spanErrorText" style="color:red;"></span>
            @if ($errors->has('email'))
                <span class="text-danger">{{ $errors->first('email') }}</span>
            @endif
            </div>
            <div class="form-group mb-3" id="password_field">
            <label for="password">Password</label>
            <input type="password" class="form-control" name="password" id="password">
            <span id="spanPassword" class="spanErrorText" style="color:red;"></span>
            @if ($errors->has('password'))
                <span class="text-danger">{{ $errors->first('password') }}</span>
            @endif
            </div>
              <div class="form-group mb-3">
              <label for="roles">Roles</label>
              <select class="form-control select2" name="roles" id="roles" data-placeholder="Select Role" style="width: 100%;" required>
                @foreach($roles as $role)
                <option value="{{$role->id}}" selected="selected">{{$role->name}}</option>
                @endforeach
              </select>
              <span id="#spanRoles" class="spanErrorText" style="color:red;"></span>
              @if ($errors->has('roles'))
                  <span class="text-danger">{{ $errors->first('roles') }}</span>
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