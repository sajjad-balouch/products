<?php

namespace App\Livewire\Website;

use App\Livewire\Website\Concerns\ManagesSite;
use App\Models\SiteMenuItem;
use App\Support\SiteSchema;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Website menus')]
class Menu extends Component
{
    use ManagesSite;

    #[Url]
    public string $location = 'header';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $label = '';

    public string $site_page_id = '';

    public string $url = '';

    public bool $new_tab = false;

    public string $parent_id = '';

    public function mount(): void
    {
        $this->need('menu');
        abort_unless(in_array($this->location, ['header', 'footer'], true), 404);
    }

    private function item(int $id): SiteMenuItem
    {
        return $this->site()->menuItems()->findOrFail($id);
    }

    public function openForm(?int $id = null): void
    {
        $this->need('menu');
        $this->resetValidation();

        $item = $id ? $this->item($id) : null;
        $this->editingId = $item?->id;
        $this->label = (string) $item?->label;
        $this->site_page_id = (string) $item?->site_page_id;
        $this->url = (string) $item?->url;
        $this->new_tab = (bool) $item?->new_tab;
        $this->parent_id = (string) $item?->parent_id;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->need('menu');
        $site = $this->site();
        abort_unless(in_array($this->location, ['header', 'footer'], true), 422);

        $this->validate([
            'label' => 'required|string|max:60',
            'site_page_id' => ['nullable', Rule::exists('site_pages', 'id')->where('site_id', $site->id)],
            'url' => ['nullable', 'required_without:site_page_id', 'string', 'max:500', fn ($attribute, $value, $fail) => SiteSchema::safeUrl((string) $value) || $fail('Use a full https:// address, or a page address starting with /.')],
            // Dropdowns go one level deep and only in the header.
            'parent_id' => ['nullable', Rule::exists('site_menu_items', 'id')->where('site_id', $site->id)->where('location', 'header')->whereNull('parent_id'), Rule::notIn([(string) $this->editingId])],
        ], ['url.required_without' => 'Choose a page or enter a link.']);

        if ($this->parent_id && $this->editingId && $site->menuItems()->where('parent_id', $this->editingId)->exists()) {
            $this->addError('parent_id', 'This link has its own dropdown, so it cannot go inside another one.');

            return;
        }

        $parent = $this->location === 'header' && $this->parent_id ? (int) $this->parent_id : null;
        $values = ['label' => $this->label, 'site_page_id' => $this->site_page_id ?: null, 'url' => $this->site_page_id ? null : $this->url, 'new_tab' => $this->new_tab, 'parent_id' => $parent];

        if ($this->editingId) {
            $this->item($this->editingId)->update($values);
        } else {
            $site->menuItems()->create($values + ['location' => $this->location, 'sort' => (int) $site->menuItems()->where('location', $this->location)->max('sort') + 1]);
        }

        $this->showForm = false;
    }

    public function toggle(int $id): void
    {
        $this->need('menu');
        $item = $this->item($id);
        $item->update(['is_visible' => ! $item->is_visible]);
    }

    public function move(int $id, int $direction): void
    {
        $this->need('menu');
        $list = $this->site()->menuItems()->where('location', $this->location)->where('parent_id', $this->item($id)->parent_id)->get()->values()->all();
        $from = collect($list)->search(fn ($i) => $i->id === $id);
        $to = $from === false ? false : $from + ($direction < 0 ? -1 : 1);

        if ($from === false || ! isset($list[$to])) {
            return;
        }

        [$list[$from], $list[$to]] = [$list[$to], $list[$from]];
        foreach ($list as $i => $item) {
            $item->update(['sort' => $i + 1]);
        }
    }

    public function delete(int $id): void
    {
        $this->need('menu');
        $this->item($id)->delete();
    }

    public function render()
    {
        $site = $this->site();
        $all = $site->menuItems()->with('page')->where('location', $this->location)->get();

        return view('livewire.website.menu', [
            'site' => $site,
            // Each top link followed by the links in its dropdown.
            'menu' => $all->whereNull('parent_id')->flatMap(fn ($top) => [$top, ...$all->where('parent_id', $top->id)]),
            'parents' => $all->whereNull('parent_id')->where('id', '!=', $this->editingId),
            'pages' => $site->pages()->get(),
        ]);
    }
}
