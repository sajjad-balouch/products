@php($school = auth()->user()->school)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscription ended · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-screen place-items-center bg-brand-900 p-4 font-sans text-slate-900">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-xl">
        <div class="text-2xl font-bold">{{ config('app.name') }}</div>
        <h1 class="mt-4 text-lg font-semibold">{{ $school->name }}: subscription ended</h1>
        <p class="mt-2 text-sm text-slate-600">
            Access ended on {{ $school->accessUntil()?->format('j M Y') }}. Your records are safe and nothing has been deleted.
            Everything will work again as soon as the subscription is renewed.
        </p>
        @if ($school->monthly_fee)<p class="mt-2 text-sm font-medium">Monthly fee: Rs {{ number_format($school->monthly_fee) }}</p>@endif
        <p class="mt-2 text-sm text-slate-600">Please contact {{ config('app.name') }} to renew.</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-5">@csrf<button class="btn">Sign out</button></form>
    </div>
</body>
</html>
