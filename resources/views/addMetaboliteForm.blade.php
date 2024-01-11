<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modelHeading"> </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="metaboliteForm" name="metaboliteForm" class="form-horizontal" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="metabolite_id" name="metabolite_id">
                    <div class="form-group mb-3">
                        <label for="inputTestName">Test Name</label>
                        <input type="text" class="form-control" name="testName" id="inputTestName" required
                            minlength="2" value="{{ old('testName') }}">
                        <span id="spanTestName" class="spanErrorText" style="color:red;"></span>
                        @if ($errors->has('testName'))
                            <span class="text-danger">{{ $errors->first('testName') }}</span>
                        @endif
                    </div>

                    <div class="form-group mb-3">
                        <label for="inputClass">Class</label>
                        <input type="text" class="form-control" name="class" id="inputClass"
                            value="{{ old('class') }}" />
                            <span id="spanClass" class="spanErrorText" style="color:red;"></span>
                            @if ($errors->has('class'))
                                <span class="text-danger">{{ $errors->first('class') }}</span>
                            @endif
                    </div>
                    <div class="form-group mb-3">
                        <label for="inputParent">Parent</label>
                        <input type="text" class="form-control" name="parent" id="inputParent"
                            value="{{ old('parent') }}" />
                            <span id="spanParent" class="spanErrorText" style="color:red;"></span>
                            @if ($errors->has('parent'))
                                <span class="text-danger">{{ $errors->first('parent') }}</span>
                            @endif
                    </div>
                    <div class="form-group mb-3">
                        <label for="inputMetabolite">Metabolite</label>
                        <input type="text" class="form-control" name="metabolite" id="inputMetabolite"
                            value="{{ old('metabolite') }}" />
                            <span id="spanMetabolite" class="spanErrorText" style="color:red;"></span>
                            @if ($errors->has('metabolite'))
                                <span class="text-danger">{{ $errors->first('metabolite') }}</span>
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
