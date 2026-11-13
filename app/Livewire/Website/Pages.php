<?php

namespace App\Livewire\Website;

use App\Livewire\Website\Concerns\ManagesSite;
use App\Models\SitePage;
use App\Services\SiteBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Website pages')]
class Pages extends Component
{
    use ManagesSite;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $slug = '';

    public string $seo_description = '';

    public bool $addToMenu = true;

    public function mount(): void
    {
        // A website editor with nothing assigned yet still lands here, and is told so.
        abort_unless($this->site()->allowsAnything(Auth::user()) || Auth::user()->isRole('website_editor'), 403);
    }

    private function page(int $id): SitePage
    {
        return $this->site()->pages()->findOrFail($id);
    }

    public function setPublished(bool $published): void
    {
        abort_unless($this->site()->isOwnedBy(Auth::user()), 403);

        $this->site()->update(['is_published' => $published]);
        $this->dispatch('toast', message: $published ? 'The website is now public.' : 'The website is now a draft.');
    }

    public function useSchoolTemplate(SiteBuilder $builder): void
    {
        abort_unless($this->site()->isOwnedBy(Auth::user()) && $this->site()->school_id, 403);

        $builder->applySchoolTemplate($this->site());
        $this->dispatch('toast', message: 'The complete school template is ready. Replace the sample text and add your pictures.');
    }

    public function openForm(?int $id = null): void
    {
        $this->need('pages');
        $this->resetValidation();

        $page = $id ? $this->page($id) : null;
        $this->editingId = $page?->id;
        $this->title = (string) $page?->title;
        $this->slug = (string) $page?->slug;
        $this->seo_description = (string) $page?->seo_description;
        $this->addToMenu = true;
        $this->showForm = true;
    }

    public function save(SiteBuilder $builder): void
    {
        $this->need('pages');
        $site = $this->site();
        $this->slug = Str::slug($this->slug ?: $this->title);

        $this->validate([
            'title' => 'required|string|max:120',
            'slug' => ['required', 'string', 'max:80', 'alpha_dash', Rule::unique('site_pages', 'slug')->where('site_id', $site->id)->ignore($this->editingId)],
            'seo_description' => 'nullable|string|max:300',
        ], ['slug.unique' => 'Another page already uses this address.']);

        if ($this->editingId) {
            $this->page($this->editingId)->update(['title' => $this->title, 'slug' => $this->slug, 'seo_description' => $this->seo_description]);
            $this->showForm = false;

            return;
        }

        $page = $builder->addPage($site, $this->title, $this->slug, $this->addToMenu);
        $page->update(['seo_description' => $this->seo_description]);

        $this->redirectRoute('website.page', $page->id, navigate: true);
    }

    public function togglePublished(int $id): void
    {
        $this->need('pages');
        $page = $this->page($id);

        if ($page->is_home && $page->is_published) {
            $this->dispatch('toast', message: 'The home page cannot be hidden. Make another page the home page first.', error: true);

            return;
        }

        $page->update(['is_published' => ! $page->is_published]);
    }

    public function makeHome(int $id): void
    {
        $this->need('pages');
        $page = $this->page($id);

        DB::transaction(function () use ($page) {
            $this->site()->pages()->update(['is_home' => false]);
            $page->update(['is_home' => true, 'is_published' => true]);
        });
    }

    public function move(int $id, int $direction): void
    {
        $this->need('pages');
        $pages = $this->site()->pages()->get()->values();
        $from = $pages->search(fn ($p) => $p->id === $id);
        $to = $from === false ? false : $from + ($direction < 0 ? -1 : 1);

        if ($from === false || ! isset($pages[$to])) {
            return;
        }

        [$a, $b] = [$pages[$from], $pages[$to]];
        $pages->each(fn ($p, $i) => $p->sort = $i + 1);
        [$a->sort, $b->sort] = [$b->sort, $a->sort];
        $pages->each->save();
    }

    public function delete(int $id): void
    {
        $this->need('pages');
        $page = $this->page($id);

        if ($page->is_home) {
            $this->dispatch('toast', message: 'The home page cannot be deleted. Make another page the home page first.', error: true);

            return;
        }

        // Also takes its sections and menu links with it.
        $page->delete();
        $this->site()->permissions()->where('permission', 'page:'.$id)->delete();
    }

    public function render()
    {
        $site = $this->site();
        $user = Auth::user();

        return view('livewire.website.pages', [
            'site' => $site,
            'isOwner' => $site->isOwnedBy($user),
            'canStructure' => $site->allows($user, 'pages'),
            'pages' => $site->pages()->withCount('sections')->get()->filter(fn ($p) => $site->allowsPage($user, $p))->values(),
            'hasAnything' => $site->allowsAnything($user),
        ]);
    }
}
