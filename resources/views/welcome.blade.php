@extends('layouts.app')

@section('title', config('app.name') . ' - AI-Powered Public Service Guidance')

@section('content')
    <livewire:copilot-chat />
@endsection
