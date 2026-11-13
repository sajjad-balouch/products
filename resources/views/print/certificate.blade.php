@extends('print.layout')
@section('title', $certificate->serial)
@section('content')
@php($student = $certificate->student)
<div class="border-4 border-double border-brand-700 p-8">
    <div class="text-center"><div class="text-2xl font-bold">{{ $school->name }}</div><div class="text-xs text-slate-600">{{ $school->address }} {{ $school->phone }}</div>
        <h1 class="mt-4 text-xl font-semibold underline">{{ \App\Models\Certificate::TYPES[$certificate->type] }}</h1></div>
    <div class="mt-2 flex justify-between text-xs"><span>Serial: {{ $certificate->serial }}</span><span>Date: {{ $certificate->issued_on->format('j F Y') }}</span></div>
    <p class="mt-6 text-sm leading-7">
        This is to certify that <b>{{ $student->name }}</b>{{ $student->father_name ? ', child of '.$student->father_name.',' : '' }} admission number <b>{{ $student->admission_no }}</b>{{ $student->dob ? ', born on '.$student->dob->format('j F Y').',' : '' }}
        @if ($certificate->type === 'leaving')
            was a student of this institution from {{ $student->admitted_on?->format('j F Y') ?? 'the date of admission' }} until {{ ($student->left_on ?? $certificate->issued_on)->format('j F Y') }}, last enrolled in {{ $student->section->label }}.
        @elseif ($certificate->type === 'character')
            is a student of this institution in {{ $student->section->label }}. To the best of our knowledge, the student bears a good moral character.
        @else
            is a bonafide student of this institution, currently enrolled in {{ $student->section->label }}.
        @endif
    </p>
    @if ($certificate->remarks)<p class="mt-3 text-sm">Remarks: {{ $certificate->remarks }}</p>@endif
    <div class="mt-12 flex items-end justify-between">
        <div class="text-center"><div class="h-24 w-24">{!! $qr !!}</div><div class="mt-1 text-[10px] text-slate-600">Scan to verify</div></div>
        <div class="text-sm">Principal: {{ $school->principal_name }}</div>
    </div>
</div>
@endsection
