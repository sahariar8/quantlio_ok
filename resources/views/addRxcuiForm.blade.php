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
            <form id="rxcuiForm" name="rxcuiForm" class="form-horizontal" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="rxcui_id" name="rxcui_id">
                    <div class="form-group mb-3">
                        <label for="inputDrugsName">Drug Name</label>
                        <input type="text" class="form-control" name="drugsName" id="inputDrugsName" required
                            minlength="2" value="{{ old('drugsName') }}">
                        <span id="spanDrugsName" class="spanErrorText" style="color:red;"></span>
                        @if ($errors->has('drugsName'))
                            <span class="text-danger">{{ $errors->first('drugsName') }}</span>
                        @endif
                    </div>

                    <div class="form-group mb-3">
                        <label for="inputRxcui">RxCUI</label>
                        <input type="text" class="form-control" name="RxCUI" id="inputRxcui"
                            value="{{ old('RxCUI') }}" />
                            <span id="spanRxCUI" class="spanErrorText" style="color:red;"></span>
                            @if ($errors->has('RxCUI'))
                                <span class="text-danger">{{ $errors->first('RxCUI') }}</span>
                            @endif
                    </div>
                    <div class="form-group mb-3">
                        <label for="inputParentDrugsName">Parent Drug Name</label>
                        <input type="text" class="form-control" name="parentDrugName" id="inputParentDrugsName"
                            value="{{ old('parentDrugName') }}" />
                            <span id="spanParentDrugs" class="spanErrorText" style="color:red;"></span>
                            @if ($errors->has('parentDrugName'))
                                <span class="text-danger">{{ $errors->first('parentDrugName') }}</span>
                            @endif
                    </div>
                    <div class="form-group mb-3">
                        <label for="inputParentRxCUI">RxCUI (Parant)</label>
                        <input type="text" class="form-control" name="parentRxcui" id="inputParentRxCUI"
                            value="{{ old('parentRxcui') }}" />
                            <span id="spanParentRxcui" class="spanErrorText" style="color:red;"></span>
                            @if ($errors->has('parentRxcui'))
                                <span class="text-danger">{{ $errors->first('parentRxcui') }}</span>
                            @endif
                    </div>
                    <div class="form-group mb-3">
                        <label for="inputAnalyte">Type of analyte</label>
                        <input type="text" class="form-control" name="analyt" id="inputAnalyte"
                            value="{{ old('analyt') }}" />
                            <span id="spanAnalyte" class="spanErrorText" style="color:red;"></span>
                            @if ($errors->has('analyt'))
                                <span class="text-danger">{{ $errors->first('analyt') }}</span>
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
