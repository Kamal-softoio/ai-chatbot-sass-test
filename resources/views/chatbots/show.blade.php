@extends('layouts.app')

@section('title', $chatbot->name)

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-robot"></i> {{ $chatbot->name }}</h2>
        <p class="text-muted">{{ $chatbot->description }}</p>
    </div>
    <div class="col-md-4 text-end">
        <div class="btn-group">
            <a href="{{ route('chatbots.edit', $chatbot) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> تعديل
            </a>
            @if($chatbot->is_public)
            <a href="{{ route('chatbots.embed-code', $chatbot) }}" class="btn btn-info">
                <i class="fas fa-code"></i> كود التضمين
            </a>
            @endif
        </div>
    </div>
</div>

{{-- Statistics --}}
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-primary">{{ $stats['total_conversations'] }}</h3>
                <p class="text-muted mb-0">إجمالي المحادثات</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-success">{{ $stats['total_messages'] }}</h3>
                <p class="text-muted mb-0">إجمالي الرسائل</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-info">{{ number_format($stats['avg_messages_per_conversation'], 1) }}</h3>
                <p class="text-muted mb-0">متوسط الرسائل</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-warning">{{ $stats['last_activity']?->diffForHumans() ?? 'لا يوجد' }}</h3>
                <p class="text-muted mb-0">آخر نشاط</p>
            </div>
        </div>
    </div>
</div>

{{-- Bot Details --}}
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> تفاصيل الروبوت</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Widget ID:</strong></td>
                        <td><code>{{ $chatbot->widget_id }}</code></td>
                    </tr>
                    <tr>
                        <td><strong>النموذج:</strong></td>
                        <td>{{ $chatbot->model_name }}</td>
                    </tr>
                    <tr>

@endsection
