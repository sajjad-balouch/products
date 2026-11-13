@extends('print.layout')
@section('title', 'Report card: '.$student->name)
@section('content')
@php($attended = ($attendance['P'] ?? 0) + ($attendance['L'] ?? 0))
<div class="text-center"><div class="text-xl font-bold">{{ $school->name }}</div><div class="text-xs text-slate-600">{{ $school->address }}</div><div class="mt-1 font-semibold">Report card: {{ $exam->name }}</div></div>
<div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
    <div>Student: <b>{{ $student->name }}</b></div><div>Class: <b>{{ $student->section->label }}</b></div>
    <div>Father: {{ $student->father_name }}</div><div>Admission no.: {{ $student->admission_no }}</div>
</div>
<table class="mt-4 w-full border-collapse text-sm">
    <thead><tr class="bg-slate-100"><th class="border border-slate-300 p-2 text-start">Subject</th><th class="border border-slate-300 p-2">Total</th><th class="border border-slate-300 p-2">Pass</th><th class="border border-slate-300 p-2">Obtained</th><th class="border border-slate-300 p-2">Result</th></tr></thead>
    <tbody>
    @foreach ($row['papers'] as $paper)
        <tr><td class="border border-slate-300 p-2">{{ $paper['subject'] }}</td><td class="border border-slate-300 p-2 text-center">{{ $paper['total'] }}</td><td class="border border-slate-300 p-2 text-center">{{ $paper['pass'] }}</td><td @class(['border border-slate-300 p-2 text-center', 'font-semibold text-red-700' => $paper['failed']])>{{ $paper['obtained'] === null ? 'Absent' : (float) $paper['obtained'] }}</td><td class="border border-slate-300 p-2 text-center">{{ $paper['failed'] ? 'Fail' : 'Pass' }}</td></tr>
    @endforeach
    <tr class="font-semibold"><td class="border border-slate-300 p-2">Total</td><td class="border border-slate-300 p-2 text-center">{{ $row['total'] }}</td><td class="border border-slate-300 p-2"></td><td class="border border-slate-300 p-2 text-center">{{ (float) $row['obtained'] }}</td><td class="border border-slate-300 p-2"></td></tr>
    </tbody>
</table>
<div class="mt-4 grid grid-cols-4 gap-3 text-center text-sm">
    <div class="rounded border border-slate-300 p-2"><div class="text-xs text-slate-500">Percentage</div><b>{{ $row['percent'] }}%</b></div>
    <div class="rounded border border-slate-300 p-2"><div class="text-xs text-slate-500">Grade</div><b>{{ $row['grade'] }}</b></div>
    <div class="rounded border border-slate-300 p-2"><div class="text-xs text-slate-500">Class position</div><b>{{ $row['position'] }}</b></div>
    <div class="rounded border border-slate-300 p-2"><div class="text-xs text-slate-500">Attendance</div><b>{{ $attendance->sum() ? round($attended / $attendance->sum() * 100).'%' : '-' }}</b></div>
</div>
<div class="mt-16 flex justify-between text-xs text-slate-600"><span>Class teacher</span><span>Principal: {{ $school->principal_name }}</span><span>Parent</span></div>
@endsection
