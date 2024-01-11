@extends('layouts.app')

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        {{-- @include('shared.breadcrumb', ['pageHeading' => 'Metabolite']) --}}
        <!-- /.content-header -->
        <div class="content-header">
            <div class="container-fluid">
              <div class="row mb-2">
                <div class="col-sm-6">
                  <h1 class="m-0">{{ "Metabolite" }}</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                  <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/home">{{ __('Home') }}</a></li>
                    <li class="breadcrumb-item active">{{ "Metabolite" }}</li>
                  </ol>
                </div><!-- /.col -->
              </div><!-- /.row -->
            </div><!-- /.container-fluid -->
          </div>

        <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ __('Delete Metabolite') }}</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ url('delete-metabolites') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="modal-body">
                            <input type="hidden" id="deleting_id" name="deleting_id">
                            <p style="padding-left: 20px;font-size: 18px;">{{ __('Are you sure want to delete') }} !</p>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">{{ __('Yes') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- End Delete Modal -->

        @include('addMetaboliteForm')

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-md-12">

                        @if (session('save_success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('save_success') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if (session('save_error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('save_error') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if (session('validation_error'))
                            <input type="hidden" id="validation_error" value="{{ session('validation_error') }}" />
                        @endif

                        <div class="card">
                            <div class="card-header">
                                @can('insert-metabolites')
                                    <button type="button" class="btn btn-primary" id="addMetabolite" data-toggle="modal"
                                        data-target="#exampleModal" style="float:right;">
                                        {{ __('Add Metabolite') }}
                                    </button>
                                @endcan

                                <!-- Button trigger modal -->

                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="example1" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>{{ __('ID') }}</th>
                                            <th>{{ __('Test Name') }}</th>
                                            <th>{{ __('Class') }}</th>
                                            <th>{{ __('Parent') }} </th>
                                            <th>{{ __('Metabolite') }} </th>
                                            <th>{{ __('Action') }} </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($metabolites as $metabolite)
                                            <tr>
                                                <td>{{ ++$i }}</td>
                                                <td>{{ $metabolite->testName }}</td>
                                                <td>{{ $metabolite->class }}</td>
                                                <td>{{ $metabolite->parent }}</td>
                                                <td>{{ $metabolite->metabolite }}</td>
                                                <td>
                                                    <ul class="list-inline m-0">
                                                        <li class="list-inline-item">
                                                            @can('edit-metabolites')
                                                                <button class="btn btn-success btn-sm rounded-0 edit"
                                                                    type="button" data-toggle="tooltip" data-placement="top"
                                                                    title="Edit" value="{{ $metabolite->id }}"><i
                                                                        class="fa fa-edit"></i></button>
                                                            @endcan
                                                        </li>
                                                        <li class="list-inline-item">
                                                            @can('delete-metabolites')
                                                                <button class="btn btn-danger deletebtn btn-sm rounded-0"
                                                                    type="button" data-toggle="tooltip" data-placement="top"
                                                                    value="{{ $metabolite->id }}" title="Delete"><i
                                                                        class="fa fa-trash"></i></button>
                                                            @endcan
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
    <script src="{{ 'js/metabolite.js' }}"></script>
@endsection
