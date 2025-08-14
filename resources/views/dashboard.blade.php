@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-tachometer-alt"></i> لوحة التحكم</h2>
        <p class="text-muted">مرحباً {{ $tenant->name }}، إليك ملخص حسابك</p>
    </div>
</div>

{{-- Statistics Cards --}}
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>الروبوتات</h5>
                        <h2>{{ $stats['total_chatbots'] }}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-robot fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>المحادثات</h5>
                        <h2>{{ $stats['total_conversations'] }}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-comments fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>الرسائل المستخدمة</h5>
                        <h2>{{ $stats['messages_used_this_month'] }}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-envelope fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>الباقة</h5>
                        <h2>{{ ucfirst($stats['plan']) }}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-crown fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5>الإجراءات السريعة</h5>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <a href="{{ route('chatbots.create') }}" class="btn btn-primary w-100">
                            <i class="fas fa-plus"></i> إنشاء روبوت جديد
                        </a>
                    </div>
                    <div class="col-md-4 mb-2">
                        <a href="{{ route('chatbots.index') }}" class="btn btn-info w-100">
                            <i class="fas fa-list"></i> عرض جميع الروبوتات
                        </a>
                    </div>
                    <div class="col-md-4 mb-2">
                        <span class="btn w-100 {{ $ollamaStatus ? 'btn-success' : 'btn-danger' }}">
                            <i class="fas fa-server"></i> 
                            Ollama: {{ $ollamaStatus ? 'متصل' : 'غير متصل' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Chatbots --}}
@if($recentChatbots->count() > 0)
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-robot"></i> آخر الروبوتات</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>النموذج</th>
                                <th>الحالة</th>
                                <th>آخر نشاط</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentChatbots as $chatbot)
                            <tr>
                                <td>{{ $chatbot->name }}</td>
                                <td>{{ $chatbot->model_name }}</td>
                                <td>
                                    <span class="badge bg-{{ $chatbot->status === 'active' ? 'success' : 'danger' }}">
                                        {{ $chatbot->status === 'active' ? 'نشط' : 'غير نشط' }}
                                    </span>
                                </td>
                                <td>{{ $chatbot->last_activity?->diffForHumans() ?? 'لا يوجد' }}</td>
                                <td>
                                    <a href="{{ route('chatbots.show', $chatbot) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
