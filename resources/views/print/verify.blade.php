<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Certificate check</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 p-4 font-sans">
    <div class="card-pad w-full max-w-md text-sm">
        @if ($certificate)
            <p class="badge badge-green">Genuine certificate</p>
            <h1 class="mt-3 text-lg font-semibold">{{ \App\Models\Certificate::TYPES[$certificate->type] }}</h1>
            <dl class="mt-3 space-y-1">
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Issued by</dt><dd class="text-end">{{ $certificate->school->name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Student</dt><dd class="text-end">{{ $certificate->student->name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Serial</dt><dd class="text-end">{{ $certificate->serial }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Date</dt><dd class="text-end">{{ $certificate->issued_on->format('j F Y') }}</dd></div>
            </dl>
        @else
            <p class="badge badge-red">Not found</p>
            <p class="mt-3">We have no record of this certificate. It may be forged. Please contact the school.</p>
        @endif
    </div>
</body>
</html>
