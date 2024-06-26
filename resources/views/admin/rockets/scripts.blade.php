@extends('layouts.admin')

@section('title')
    Launchpads &rarr; Rocket: {{ $rocket->name }} &rarr; Install Script
@endsection

@section('content-header')
    <h1>{{ $rocket->name }}<small>Manage the install script for this Rocket.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.launchpads') }}">Nests</a></li>
        <li><a href="{{ route('admin.launchpads.view', $rocket->launchpad->id) }}">{{ $rocket->launchpad->name }}</a></li>
        <li><a href="{{ route('admin.launchpads.rocket.view', $rocket->id) }}">{{ $rocket->name }}</a></li>
        <li class="active">{{ $rocket->name }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li><a href="{{ route('admin.launchpads.rocket.view', $rocket->id) }}">Configuration</a></li>
                <li><a href="{{ route('admin.launchpads.rocket.variables', $rocket->id) }}">Variables</a></li>
                <li class="active"><a href="{{ route('admin.launchpads.rocket.scripts', $rocket->id) }}">Install Script</a></li>
            </ul>
        </div>
    </div>
</div>
<form action="{{ route('admin.launchpads.rocket.scripts', $rocket->id) }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Install Script</h3>
                </div>
                @if(! is_null($rocket->copyFrom))
                    <div class="box-body">
                        <div class="callout callout-warning no-margin">
                            This service option is copying installation scripts and container options from <a href="{{ route('admin.launchpads.rocket.view', $rocket->copyFrom->id) }}">{{ $rocket->copyFrom->name }}</a>. Any changes you make to this script will not apply unless you select "None" from the dropdown box below.
                        </div>
                    </div>
                @endif
                <div class="box-body no-padding">
                    <div id="editor_install"style="height:300px">{{ $rocket->script_install }}</div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-sm-4">
                            <label class="control-label">Copy Script From</label>
                            <select id="pCopyScriptFrom" name="copy_script_from">
                                <option value="">None</option>
                                @foreach($copyFromOptions as $opt)
                                    <option value="{{ $opt->id }}" {{ $rocket->copy_script_from !== $opt->id ?: 'selected' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-muted small">If selected, script above will be ignored and script from selected option will be used in place.</p>
                        </div>
                        <div class="form-group col-sm-4">
                            <label class="control-label">Script Container</label>
                            <input type="text" name="script_container" class="form-control" value="{{ $rocket->script_container }}" />
                            <p class="text-muted small">Docker container to use when running this script for the server.</p>
                        </div>
                        <div class="form-group col-sm-4">
                            <label class="control-label">Script Entrypoint Command</label>
                            <input type="text" name="script_entry" class="form-control" value="{{ $rocket->script_entry }}" />
                            <p class="text-muted small">The entrypoint command to use for this script.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 text-muted">
                            The following service options rely on this script:
                            @if(count($relyOnScript) > 0)
                                @foreach($relyOnScript as $rely)
                                    <a href="{{ route('admin.launchpads.rocket.view', $rely->id) }}">
                                        <code>{{ $rely->name }}</code>@if(!$loop->last),&nbsp;@endif
                                    </a>
                                @endforeach
                            @else
                                <em>none</em>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <textarea name="script_install" class="hidden"></textarea>
                    <button type="submit" name="_method" value="PATCH" class="btn btn-primary btn-sm pull-right">Save</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/ace/ace.js') !!}
    {!! Theme::js('vendor/ace/ext-modelist.js') !!}
    <script>
    $(document).ready(function () {
        $('#pCopyScriptFrom').select2();

        const InstallEditor = ace.edit('editor_install');
        const Modelist = ace.require('ace/ext/modelist')

        InstallEditor.setTheme('ace/theme/chrome');
        InstallEditor.getSession().setMode('ace/mode/sh');
        InstallEditor.getSession().setUseWrapMode(true);
        InstallEditor.setShowPrintMargin(false);

        $('form').on('submit', function (e) {
            $('textarea[name="script_install"]').val(InstallEditor.getValue());
        });
    });
    </script>

@endsection
