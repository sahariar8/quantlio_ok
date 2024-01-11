@extends('layouts.app')

@section('content')
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">{{ __('Drug Interactions') }}</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="/home">{{ __('Home') }}</a></li>
              <li class="breadcrumb-item active">{{ __('Drug Interactions') }}</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    @include('drugInteractionEditForm')

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row justify-content-center">
          <div class="col-md-12">
            @if (session('save_success'))
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('save_success')}}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
            @endif

            @if (session('save_error'))
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('save_error')}}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
            @endif
              <!-- /.card-header -->
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>{{ __('ID') }}</th>
                    <th>{{ __('Prescribed Test') }}</th>
                    <th>{{ __('Interacted With') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th>{{ __('Drug Class') }} </th>
                    <th>{{ __('Keyword') }} </th>
                    <th>{{ __('Severity') }} </th>
                    <th>{{ __('Action') }} </th>
                  </tr>
                  </thead>
                  <tbody>
                    @foreach($interactions as $result)
                      <tr>
                          <td>{{ ++$i }}</td>
                          <td>{{ $result['prescribed_test'] }}</td>
                          <td>{{ $result['interacted_with'] }}</td>
                          <td>{{ $result['description'] }}</td>
                          <td>{{ $result['drug_class'] }}</td>
                          <td>{{ $result['keyword'] }}</td>
                          <td>{{ $result['risk_score'] }}</td>
                          <td>
                              <ul class="m-0">
                                <li class="list-inline-item">
                                  <button class="btn btn-success btn-sm rounded-0 edit" type="button" data-toggle="tooltip" data-placement="top" title="Edit" value="{{$result->id}}"><i class="fa fa-edit"></i></button>
                                </li>
                              </ul>
                          </td>
                      </tr>
                    @endforeach
                  </tbody>
                  
                </table>
              </div>
              <!-- /.card-body -->
            </div>
          </div>
        </div>
    </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>

@endsection
@section('load_external_js')
  <script src="{{'js/drug-interactions.js'}}"></script>
@endsection
