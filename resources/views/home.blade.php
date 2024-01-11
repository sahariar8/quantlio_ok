@extends('layouts.app')
@section('load_external_css')

<link rel="stylesheet" href="{{ asset('plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/bs-stepper/css/bs-stepper.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/dropzone/min/dropzone.min.css') }}">
@endsection

@section('content')
 <!-- Content Wrapper. Contains page content -->
 <div class="content-wrapper">
    
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">{{ __('Dashboard For Reporting') }}</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Dashboard</li>
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
          <!-- left column -->
          <div class="col-md-12">
            <!-- general form elements -->
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">{{ __('Dashboard For Reporting') }}</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
              <div class="col-md-12 marginTop10">
              @if (session('save_error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  {{ session('save_error')}}
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
              @endif
            
              <form id="dataFilter" name="dataFilter" method="POST">
              @csrf
                <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>{{ __('States') }}</label>
                      <select class="select2" id="state-dropdown" multiple="multiple" name="states[]" data-placeholder="Select State" style="width: 100%;">
                        @foreach($states as $state)
                          <option value="{{ $state->state }}">{{ $state->state }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>{{ __('Clinic') }}</label>
                      <select id="clinic-dropdown" class="select2" name="clinics[]" multiple="multiple" data-placeholder="Select Clinic" style="width: 100%;">
                        @foreach($lablocations as $lablocation)
                          <option value="{{ $lablocation }}">{{ $lablocation }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                </div>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>{{ __('Providers') }}</label>
                        <select id="provider-dropdown" class="select2" name="providers[]" multiple="multiple" data-placeholder="Select Provider" style="width: 100%;">
                          @foreach($providers as $provider)
                            <option value="{{ $provider->provider_name }}">{{ $provider->provider_name }}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>{{ __('Patients') }}</label>
                        <select id="patient-dropdown" class="select2" multiple="multiple" name="patients[]" data-placeholder="Select Patient" style="width: 100%;">
                          @foreach($patients as $patient)
                            <option value="{{ $patient->patient_name }}">{{ $patient->patient_name }}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                  </div><!-- /.row -->

                
                  <div class="row">
                    <div class="col-md-6">
                      <div class="row">
                        <div class="form-group col-md-6">
                          <label>{{ __('Patient Age From') }}</label>
                          <select class="form-control" name="ageFrom">
                            <option value="" >Select Age</option>
                            <?php for($i=1; $i<=100; $i++) { ?>
                              <option value="<?php echo $i ?>"><?php echo $i ?></option>
                            <?php } ?>
                          </select>
                        </div>
                        <div class="form-group col-md-6">
                          <label>{{ __('Patient Age To') }}</label>
                          <select class="form-control" name="ageTo">
                          <option value="" >Select Age</option>
                            <?php for($i=1; $i<=100; $i++) { ?>
                              <option value="<?php echo $i ?>"><?php echo $i ?></option>
                            <?php } ?>
                          </select>
                          @if ($errors->has('ageTo'))
                            <span class="text-danger">{{ $errors->first('ageTo') }}</span>
                          @endif
                        </div>
                      </div>
                      
                      
                    </div>

                    <div class="col-md-6">
                      <div class="form-group">
                        <label>{{ __('Patient Gender') }}</label>
                        <select class="select2" multiple="multiple" name="gender[]" data-placeholder="Select Gender" style="width: 100%;">
                          @foreach($genders as $gender)
                            <option value="{{ $gender->patient_gender }}">{{ $gender->patient_gender }}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                  </div><!-- /.row -->

                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>{{ __('Test') }}</label>
                        <select class="select2" multiple="multiple" name="tests[]" data-placeholder="Select Test" style="width: 100%;">
                          @foreach($tests as $test)
                            <option value="{{ $test->test }}">{{ $test->test }}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-group">
                        <label>{{ __('Test Class') }}</label>
                        <select class="select2" multiple="multiple" name="classes[]" data-placeholder="Select Test Class" style="width: 100%;">
                          @foreach($testClasses as $testClass)
                            <option value="{{ $testClass->test_class }}">{{ $testClass->test_class }}</option>
                          @endforeach
                        </select>
                        
                      </div>
                    </div>
                  </div><!-- /.row -->

                  <div class="row">
                    <div class="col-md-6">
                      <div class="row">
                        <div class="form-group col-md-6">
                          <label>{{ __('Reported Date From') }}</label>
                            <div class="input-group date" id="reported_date_from" data-target-input="nearest">
                                <input type="text" class="form-control datetimepicker-input" name="reported_date_from" data-target="#reported_date_from">
                                <div class="input-group-append" data-target="#reported_date_from" data-toggle="datetimepicker">
                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                </div>
                            </div>
                            @if($errors->has('reported_date_from'))
                                    <span class="text-danger">{{ $errors->first('reported_date_from') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-6">
                          <label>{{ __('Reported Date To') }}</label>
                            <div class="input-group date" id="reported_date_to" data-target-input="nearest">
                                <input type="text" class="form-control datetimepicker-input" name="reported_date_to" data-target="#reported_date_to">
                                <div class="input-group-append" data-target="#reported_date_to" data-toggle="datetimepicker">
                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                </div>
                            </div>
                            @if($errors->has('reported_date_to'))
                                    <span class="text-danger">{{ $errors->first('reported_date_to') }}</span>
                            @endif
                        </div>
                      </div>
                    </div>

                    <div class="col-md-6">
                      
                    </div>
                  </div><!-- /.row -->

                </div>
                <!-- /.card-body -->

                <div class="card-footer">
                  <button type="submit" class="btn btn-primary" id="export-data" style="margin-right:5px;">{{ __('Export All') }}</button>
                  <button type="submit" class="btn btn-primary" id="export-data-by-patients">{{ __('Export by unique patients') }}</button>
                  <button type="submit" class="btn btn-primary" id="ddi-report" style="margin-right:5px;">{{ __('DDI Report') }}</button>
                  <button type="submit" class="btn btn-primary" id="detailed-ddi-report" style="margin-right:5px;">{{ __('Detailed DDI Report') }}</button>
                  <button type="submit" class="btn btn-primary" id="ci-report" style="margin-right:5px;">{{ __('CI Report') }}</button>

                </div>
                
              </form>
            </div>
            <!-- /.card -->
          </div>
          <!--/.col (left) -->
        </div>
      </div>
    </section>
    <!-- /.content -->
  </div>
@endsection
@section('load_external_js')
  <script src="{{'js/dashboard.js'}}"></script>
  <script>
    //Date picker
    $('#reported_date_from').datetimepicker({
        format: 'YYYY-MM-DD'
    });

    $('#reported_date_to').datetimepicker({
        format: 'YYYY-MM-DD'
    });
    
  </script>
@endsection