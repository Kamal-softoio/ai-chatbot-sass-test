@extends('layouts.app')

@section('title', 'إنشاء حساب جديد')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white text-center">
                <h4><i class="fas fa-user-plus"></i> إنشاء حساب جديد</h4>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('tenant.register') }}">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">الاسم الكامل</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">البريد الإلكتروني</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">كلمة المرور</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">تأكيد كلمة المرور</label>
                        <input type="password" class="form-control" id="password_confirmation" 
                               name="password_confirmation" required>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-user-plus"></i> إنشاء الحساب
                    </button>
                </form>

                <div class="text-center mt-3">
                    <p>لديك حساب بالفعل؟ <a href="{{ route('tenant.login') }}">تسجيل الدخول</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection