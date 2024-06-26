@extends('layouts.admin')

@section('title')
    Launchpads &rarr; {{ $launchpad->name }}
@endsection

@section('content-header')
    <h1>{{ $launchpad->name }}<small>{{ str_limit($launchpad->description, 50) }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.launchpads') }}">Nests</a></li>
        <li class="active">{{ $launchpad->name }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <form action="{{ route('admin.launchpads.view', $launchpad->id) }}" method="POST">
        <div class="col-md-6">
            <div class="box">
                <div class="box-body">
                    <div class="form-group">
                        <label class="control-label">Name <span class="field-required"></span></label>
                        <div>
                            <input type="text" name="name" class="form-control" value="{{ $launchpad->name }}" />
                            <p class="text-muted"><small>This should be a descriptive category name that encompasses all of the options within the service.</small></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Description</label>
                        <div>
                            <textarea name="description" class="form-control" rows="7">{{ $launchpad->description }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" name="_method" value="PATCH" class="btn btn-primary btn-sm pull-right">Save</button>
                    <button id="deleteButton" type="submit" name="_method" value="DELETE" class="btn btn-sm btn-danger muted muted-hover"><i class="fa fa-trash-o"></i></button>
                </div>
            </div>
        </div>
    </form>
    <div class="col-md-6">
        <div class="box">
            <div class="box-body">
                <div class="form-group">
                    <label class="control-label">Nest ID</label>
                    <div>
                        <input type="text" readonly class="form-control" value="{{ $launchpad->id }}" />
                        <p class="text-muted small">A unique ID used for identification of this nest internally and through the API.</p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label">Author</label>
                    <div>
                        <input type="text" readonly class="form-control" value="{{ $launchpad->author }}" />
                        <p class="text-muted small">The author of this service option. Please direct questions and issues to them unless this is an official option authored by <code>support@pterodactyl.io</code>.</p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label">UUID</label>
                    <div>
                        <input type="text" readonly class="form-control" value="{{ $launchpad->uuid }}" />
                        <p class="text-muted small">A UUID that all servers using this option are assigned for identification purposes.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Launchpad Rockets</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th class="text-center">Servers</th>
                        <th class="text-center"></th>
                    </tr>
                    @foreach($launchpad->rockets as $rocket)
                        <tr>
                            <td class="align-middle"><code>{{ $rocket->id }}</code></td>
                            <td class="align-middle"><a href="{{ route('admin.launchpads.rocket.view', $rocket->id) }}" data-toggle="tooltip" data-placement="right" title="{{ $rocket->author }}">{{ $rocket->name }}</a></td>
                            <td class="col-xs-8 align-middle">{{ $rocket->description }}</td>
                            <td class="text-center align-middle"><code>{{ $rocket->servers->count() }}</code></td>
                            <td class="align-middle">
                                <a href="{{ route('admin.launchpads.rocket.export', ['rocket' => $rocket->id]) }}"><i class="fa fa-download"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
            <div class="box-footer">
                <a href="{{ route('admin.launchpads.rocket.new') }}"><button class="btn btn-success btn-sm pull-right">New Rocket</button></a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#deleteButton').on('mouseenter', function (event) {
            $(this).find('i').html(' Delete Launchpad');
        }).on('mouseleave', function (event) {
            $(this).find('i').html('');
        });
    </script>
@endsection
