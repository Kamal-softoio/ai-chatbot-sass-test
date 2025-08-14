@extends('layouts.app')

@section('title', 'الروبوتات')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-robot"></i> الروبوتات</h2>
    </div>
    <div class="col-md-4 text-end">
        @if($tenant->canCreateChatbot())
            <a href="{{ route('chatbots.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> إنشاء روبوت جديد
            </a>
        @else
            <span class="text-muted">وصلت للحد الأقصى من الروبوتات</span>
        @endif
    </div>
</div>

@if($chatbots->count() > 0)
    <div class="row">
        @foreach($chatbots as $chatbot)
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title">{{ $chatbot->name }}</h5>
                        <span class="badge bg-{{ $chatbot->status === 'active' ? 'success' : 'danger' }}">
                            {{ $chatbot->status === 'active' ? 'نشط' : 'غير نشط' }}
                        </span>
                    </div>
                    
                    <p class="card-text text-muted">{{ Str::limit($chatbot->description, 100) }}</p>
                    
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <small class="text-muted">المحادثات</small><br>
                            <strong>{{ $chatbot->conversations_count }}</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">الرسائل</small><br>
                            <strong>{{ $chatbot->messages_count }}</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">النموذج</small><br>
                            <strong>{{ $chatbot->model_name }}</strong>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <a href="{{ route('chatbots.show', $chatbot) }}" class="btn btn-primary btn-sm flex-fill">
                            <i class="fas fa-eye"></i> عرض
                        </a>
                        <a href="{{ route('chatbots.edit', $chatbot) }}" class="btn btn-warning btn-sm flex-fill">
                            <i class="fas fa-edit"></i> تعديل
                        </a>
                        @if($chatbot->is_public)
                        <a href="{{ route('chatbots.embed-code', $chatbot) }}" class="btn btn-info btn-sm flex-fill">
                            <i class="fas fa-code"></i> الكود
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    
    <div class="d-flex justify-content-center">
        {{ $chatbots->links() }}
    </div>
@else
    <div class="text-center">
        <div class="card">
            <div class="card-body p-5">
                <i class="fas fa-robot fa-4x text-muted mb-3"></i>
                <h4>لا توجد روبوتات حتى الآن</h4>
                <p class="text-muted">ابدأ بإنشاء أول روبوت محادثة لك</p>
                @if($tenant->canCreateChatbot())
                    <a href="{{ route('chatbots.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> إنشاء روبوت جديد
                    </a>
                @endif
            </div>
        </div>
    </div>
@endif
@endsection