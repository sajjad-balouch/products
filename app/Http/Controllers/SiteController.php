<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Site;
use App\Models\SiteMedia;
use App\Models\SitePage;
use App\Services\SiteBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/** The public website. People who can edit a site also see its drafts. */
class SiteController extends Controller
{
    public function home(SiteBuilder $builder)
    {
        $site = $builder->platform();

        // With the owner's site unpublished, / goes back to being the sign-in page.
        if (! $site->is_published && ! $site->allowsAnything(Auth::user())) {
            return Auth::check() ? redirect()->route(Auth::user()->homeRoute()) : redirect()->route('login');
        }

        return $this->render($site, $site->home());
    }

    public function page(SiteBuilder $builder, string $page)
    {
        $site = $builder->platform();

        return $this->render($site, $site->pages()->where('slug', $page)->first());
    }

    public function schoolHome(Request $request, string $school)
    {
        $site = $this->schoolSite($school);
        $page = $site->home();

        return $this->movedToDomain($request, $site, $page) ?? $this->render($site, $page);
    }

    public function schoolPage(Request $request, string $school, string $page)
    {
        $site = $this->schoolSite($school);
        $page = $site->pages()->where('slug', $page)->first();

        return $this->movedToDomain($request, $site, $page) ?? $this->render($site, $page);
    }

    /** Old /site/... links go to the school's own address once it works. Editors stay, to preview drafts while signed in. */
    private function movedToDomain(Request $request, Site $site, ?SitePage $page)
    {
        if ($request->attributes->has('site_host') || ! $page || ! $site->webRoot() || $site->allowsAnything(Auth::user())) {
            return null;
        }

        return redirect()->away($site->pageUrl($page), 301);
    }

    public function media(int $media)
    {
        $file = SiteMedia::findOrFail($media);
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return response()->file(Storage::disk('local')->path($file->path), ['Cache-Control' => 'public, max-age=604800', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function contact(Request $request, Site $site)
    {
        abort_unless($site->is_published, 404);

        // The form can be far down a long page, so the reply returns to that spot.
        $back = redirect(url()->previous().'#'.Str::slug((string) $request->input('anchor')));

        // Bots fill the hidden field. They get the normal reply and nothing is saved.
        if ($request->filled('website')) {
            return $back->with('site_contact_sent', true);
        }

        $check = Validator::make($request->only('name', 'phone', 'email', 'message'), [
            'name' => 'required|string|max:120',
            'phone' => 'nullable|string|max:30|required_without:email',
            'email' => 'nullable|email|max:150|required_without:phone',
            'message' => 'required|string|max:3000',
        ], ['phone.required_without' => 'Please give a phone number or an email.', 'email.required_without' => 'Please give a phone number or an email.']);

        if ($check->fails()) {
            return $back->withErrors($check)->withInput();
        }

        $site->messages()->create($check->validated());

        return $back->with('site_contact_sent', true);
    }

    private function schoolSite(string $slug): Site
    {
        $school = School::where('slug', $slug)->where('is_active', true)->firstOrFail();
        abort_unless($school->hasFeature('website') && ! $school->isLocked(), 404);

        return Site::where('owner_key', 'school:'.$school->id)->firstOrFail();
    }

    private function render(Site $site, ?SitePage $page)
    {
        $viewer = Auth::user();
        $canEdit = $site->allowsAnything($viewer);

        abort_unless($page && (($site->is_published && $page->is_published) || $canEdit), 404);

        return view('site.page', [
            'site' => $site,
            'page' => $page,
            'sections' => $page->sections()->where('is_visible', true)->get(),
            'headerMenu' => $site->menu('header'),
            'footerMenu' => $site->menu('footer'),
            'viewer' => $viewer,
            'canEdit' => $canEdit,
            'canEditPage' => $site->allowsPage($viewer, $page),
        ]);
    }
}
