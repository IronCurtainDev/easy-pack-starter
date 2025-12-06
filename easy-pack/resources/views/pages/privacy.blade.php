@extends('easypack::layouts.master-frontend-internal')

@push('meta')
    <META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW" />
@endpush

@section('internal-page-contents')
    <div>
        {!! $content !!}
    </div>
@stop
