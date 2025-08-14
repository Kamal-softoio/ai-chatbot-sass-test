@extends('layouts.app')

@section('title', 'إنشاء روبوت جديد')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-plus"></i> إنشاء روبوت جديد</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('chatbots.store') }}">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">اسم الروبوت</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">الوصف</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="model_name" class="form-label">النموذج</label>
                        <select class="form-control @error('model_name') is-invalid @enderror" 
                                id="model_name" name="model_name" required>
                            <option value="">اختر النموذج</option>
                            @if(count($availableModels) > 0)
                                @foreach($availableModels as $model)
                                    <option value="{{ $model['name'] }}" {{ old('model_name') == $model['name'] ? 'selected' : '' }}>
                                        {{ $model['name'] }}
                                    </option>
                                @endforeach
                            @else
                                <option value="llama2">llama2 (افتراضي)</option>
                                <option value="mistral">mistral</option>
                            @endif
                        </select>
                        @error('model_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="system_prompt" class="form-label">التعليمات الأساسية</label>
                        <textarea class="form-control @error('system_prompt') is-invalid @enderror" 
                                  id="system_prompt" name="system_prompt" rows="4">{{ old('system_prompt', 'أنت مساعد ذكي مفيد وودود. أجب على الأسئلة بوضوح ودقة.') }}</textarea>
                        @error('system_prompt')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="temperature" class="form-label">Temperature</label>
                                <input type="number" class="form-control @error('settings.temperature') is-invalid @enderror" 
                                       id="temperature" name="settings[temperature]" step="0.1" min="0" max="1" 
                                       value="{{ old('settings.temperature', 0.7) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="max_tokens" class="form-label">حد الرموز</label>
                                <input type="number" class="form-control @error('settings.max_tokens') is-invalid @enderror" 
                                       id="max_tokens" name="settings[max_tokens]" min="100" max="4000" 
                                       value="{{ old('settings.max_tokens', 2048) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3 form-check mt-4">
                                <input type="checkbox" class="form-check-input" id="is_public" name="is_public" 
                                       {{ old('is_public') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_public">
                                    روبوت عام (يمكن تضمينه في المواقع)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> حفظ الروبوت
                        </button>
                        <a href="{{ route('chatbots.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> العودة
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection