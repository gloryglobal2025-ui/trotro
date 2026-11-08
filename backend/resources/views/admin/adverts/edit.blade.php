@extends('admin.layout')

@section('content')
<div class="p-6 lg:p-8">
    <div class="mb-6">
        <a href="{{ route('admin.adverts.index') }}" class="text-xs font-semibold text-orange-600 hover:underline">&larr; Back to adverts</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Edit advert</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $advert->title }}</p>
    </div>

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-semibold">Please fix the highlighted fields.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.adverts.update', $advert) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.adverts._form')
    </form>
</div>
@endsection
