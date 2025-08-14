@extends('layouts.app')

@section('title', 'مرحباً بك')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 text-center">
        <div class="card">
            <div class="card-body p-5">
                <h1 class="display-4 text-primary mb-4">
                    <i class="fas fa-robot"></i> نظام إدارة الشات بوت
                </h1>
                <p class="lead mb-4">أنشئ وأدر روبوتات المحادثة الذكية بسهولة</p>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <a href="{{ route('tenant.login') }}" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="{{ route('tenant.register') }}" class="btn btn-success btn-lg w-100">
                            <i class="fas fa-user-plus"></i> إنشاء حساب جديد
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection