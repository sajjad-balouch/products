<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ $school->name ?? config('app.name') }}</title>
    @vite(['resources/css/app.css'])
    <style>@media print { @page { margin: 10mm; } }</style>
</head>
<body class="bg-slate-200 font-sans text-slate-900 print:bg-white">
    <div class="no-print sticky top-0 flex justify-center gap-2 bg-slate-800 p-2">
        <button class="btn btn-sm" onclick="window.print()">Print</button>
        <button class="btn btn-sm" onclick="window.close()">Close</button>
    </div>
    <div class="mx-auto my-4 max-w-4xl bg-white p-6 shadow print:m-0 print:max-w-none print:p-0 print:shadow-none">
        @yield('content')
    </div>
</body>
</html>
