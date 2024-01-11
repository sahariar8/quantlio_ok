@extends('layouts.app')
@section('load_external_css')

<link rel="stylesheet" href="{{ asset('plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/bs-stepper/css/bs-stepper.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/dropzone/min/dropzone.min.css') }}">
@endsection

<style type="text/css">
	.select2-container--default .select2-selection--multiple .select2-selection__rendered li {
	    list-style: none;
	    max-width: 100%;
	    overflow: hidden;
	    word-wrap: normal !important;
	    white-space: normal;
	}
	.select2-container--default .select2-selection--multiple .select2-selection__rendered li:first-child.select2-search.select2-search--inline {
	    height: 35px;
	}

	.select2-container--default .select2-selection--multiple {
	    border: 1px solid #ced4da !important;
	}
</style>

@section('content')

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">{{ __('Track Orders') }}</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Track Orders</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
            <div class="card card-primary">
              <div class="col-md-12 marginTop10">
              @if (session('save_error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  {{ session('save_error')}}
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
              @endif
            
              <form id="trackOrder" name="trackOrder" method="POST" action="/track-orders">
                @csrf
                <div class="card-body">
                  <div class="row">
                    <!-- <div class="col-md-6"> -->
                      <div class="row" style="width:100%">
                        <div class="form-group col-md-4">
                          <label for="inputLabName">Lab Name</label>

                          <div class="selectAll" style="float: right;"> 
                            <input type="checkbox" id="checkbox" style="margin-right: 5px;">Select All
                          </div>
                          <select class="js-example-placeholder-multiple select2bs4 form-control" name="lab[]" id="lab" multiple="multiple" data-placeholder="Select Lab" style="width: 100%;" required>
                              @if(!empty($orderDetails))
                                @foreach($orderDetails as $orderDetails_key => $orderDetails_value)
                                <option value="{{ $orderDetails_value->in_house_lab_locations }}" style="width:auto;">{{ $orderDetails_value->in_house_lab_locations }}</option>  
                                @endforeach
                              @endif
                          </select>
                          <span id="spanLab" class="spanErrorText" style="color:red;"></span>
                          @if ($errors->has('lab'))
                            <span class="text-danger">{{ $errors->first('lab') }}</span>
                          @endif
                        </div>
                        <div class="form-group col-md-3">
                          <label>{{ __('Start Date') }}</label>
                          <div class="input-group date" id="start_date" data-target-input="nearest">
                              <input type="text" class="form-control datetimepicker-input" name="start_date" value="{{ date('m-d-Y', strtotime('first day of last month')) }}" data-target="#start_date" onkeydown="return false">
                              <div class="input-group-append" data-target="#start_date" data-toggle="datetimepicker">
                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                              </div>
                          </div>
                          @if($errors->has('start_date'))
                            <span class="text-danger">{{ $errors->first('start_date') }}</span>
                          @endif
                        </div>

                        <div class="form-group col-md-3">
                          <label>{{ __('End Date') }}</label>
                          <div class="input-group date" id="end_date" data-target-input="nearest">
                              <input type="text" class="form-control datetimepicker-input" name="end_date" value="{{ date('m-d-Y', strtotime('last day of last month')) }}" data-target="#end_date" onkeydown="return false">
                              <div class="input-group-append" data-target="#end_date" data-toggle="datetimepicker">
                                  <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                              </div>
                          </div>
                          @if($errors->has('end_date'))
                            <span class="text-danger">{{ $errors->first('end_date') }}</span>
                          @endif
                        </div>
                      </div>
                    <!-- </div> -->
                  </div>
                  <div class="row">
                    <button type="submit" class="btn btn-primary" id="track-order" data-href="/track-orders" style="margin-right:5px;">{{ __('Export Report') }}</button>
                  </div>
                </div>               
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
@endsection
@section('load_external_js')
  <script src="{{'js/dashboard.js'}}"></script>
  <script>
    //Date picker
    $('#start_date').datetimepicker({
        format: 'MM-DD-YYYY'
    });

    $('#end_date').datetimepicker({
        format: 'MM-DD-YYYY'
    });
    
  </script>
@endsection
