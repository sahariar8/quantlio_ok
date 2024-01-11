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
            <form id="tradeForm" name="tradeForm" class="form-horizontal" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="trade_id" name="trade_id">
                    <div class="form-group mb-3">
                        <label for="inputGeneric">Generic</label>
                        <input type="text" class="form-control" name="generic" id="inputGeneric" required
                            minlength="2" value="{{ old('generic') }}">
                        <span id="spanGeneric" class="spanErrorText" style="color:red;"></span>
                        @if ($errors->has('generic'))
                            <span class="text-danger">{{ $errors->first('generic') }}</span>
                        @endif
                    </div>

                    <div class="form-group mb-3">
                        <label for="inputBrand">Brand</label>
                        <input type="text" class="form-control" name="brand" id="inputBrand"
                            value="{{ old('brand') }}" />
                            <span id="spanBrand" class="spanErrorText" style="color:red;"></span>
                            @if ($errors->has('brand'))
                                <span class="text-danger">{{ $errors->first('brand') }}</span>
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
