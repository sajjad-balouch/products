<?php

namespace App\Livewire\Website;

use App\Livewire\Website\Concerns\ManagesSite;
use App\Models\SitePage;
use App\Models\SiteSection;
use App\Support\SiteSchema;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit page')]
class PageEditor extends Component
{
    use ManagesSite;

    public int $pageId;

    public bool $showTypes = false;

    /** Null when no section is open. A null editingId with a type means a new section. */
    public ?string $editingType = null;

    public ?int $editingId = null;

    public array $form = [];

    public array $items = [];

    public function mount(int $page): void
    {
        $this->pageId = $page;
        $this->page();
    }

    private function page(): SitePage
    {
        $page = $this->site()->pages()->findOrFail($this->pageId);
        abort_unless($this->site()->allowsPage(Auth::user(), $page), 403);

        return $page;
    }

    private function section(int $id): SiteSection
    {
        return $this->page()->sections()->findOrFail($id);
    }

    private function fields(string $type): array
    {
        return SiteSchema::sections()[$type]['fields'] + SiteSchema::sectionStyle();
    }

    public function chooseType(string $type): void
    {
        $this->page();
        abort_unless(isset(SiteSchema::sections()[$type]), 422);

        $this->showTypes = false;
        $this->open($type, null, ['show_form' => true, 'show_details' => true, 'show_map' => true]);
    }

    public function edit(int $id): void
    {
        $section = $this->section($id);
        $this->open($section->type, $section->id, $section->data ?? []);
    }

    private function open(string $type, ?int $id, array $data): void
    {
        $this->resetValidation();
        $this->uploads = [];
        $this->editingType = $type;
        $this->editingId = $id;
        $this->form = SiteSchema::clean($this->fields($type), $data);

        foreach ($this->fields($type) as $key => $field) {
            if ($field['type'] === 'select' && $this->form[$key] === '') {
                $this->form[$key] = (string) array_key_first($field['options']);
            }
        }

        $this->loadItems($data['items'] ?? []);
    }

    public function closeEditor(): void
    {
        $this->editingType = null;
        $this->editingId = null;
        $this->uploads = [];
    }

    protected function itemFields(): array
    {
        return SiteSchema::sections()[$this->editingType]['items'] ?? [];
    }

    public function saveSection(): void
    {
        $page = $this->page();
        $site = $this->site();
        $type = $this->editingType;
        abort_unless(isset(SiteSchema::sections()[$type]), 422);

        $fields = $this->fields($type);
        $itemFields = SiteSchema::sections()[$type]['items'] ?? [];

        $this->validate(
            SiteSchema::rules($fields, 'form', $site) + SiteSchema::rules($itemFields, 'items.*', $site) + $this->uploadRules(),
            [],
            SiteSchema::labels($fields, 'form') + SiteSchema::labels($itemFields, 'items.*'),
        );

        foreach ($fields as $key => $field) {
            if ($field['type'] === 'image') {
                $this->form[$key] = $this->imageFor($key, $this->form[$key] ?? null);
            }
        }

        $data = SiteSchema::clean($fields, $this->form);

        if ($itemFields) {
            $data['items'] = $this->cleanItems($itemFields);
        }

        if ($this->editingId) {
            $this->section($this->editingId)->update(['data' => $data]);
        } else {
            $page->sections()->create(['type' => $type, 'data' => $data, 'sort' => (int) $page->sections()->max('sort') + 1]);
        }

        $this->closeEditor();
        $this->dispatch('toast', message: 'Section saved.');
    }

    public function toggleSection(int $id): void
    {
        $section = $this->section($id);
        $section->update(['is_visible' => ! $section->is_visible]);
    }

    public function duplicateSection(int $id): void
    {
        $section = $this->section($id);
        $copy = $section->replicate();
        $copy->sort = $section->sort;
        $copy->save();
        $this->renumber($this->page()->sections()->get()->sortBy(fn ($s) => [$s->sort, $s->id])->values());
    }

    public function deleteSection(int $id): void
    {
        $this->section($id)->delete();
    }

    public function moveSection(int $id, int $direction): void
    {
        $sections = $this->page()->sections()->get()->values();
        $from = $sections->search(fn ($s) => $s->id === $id);
        $to = $from === false ? false : $from + ($direction < 0 ? -1 : 1);

        if ($from === false || ! isset($sections[$to])) {
            return;
        }

        $list = $sections->all();
        [$list[$from], $list[$to]] = [$list[$to], $list[$from]];
        $this->renumber(collect($list));
    }

    private function renumber($sections): void
    {
        $sections->each(fn ($s, $i) => $s->update(['sort' => $i + 1]));
    }

    public function render()
    {
        $page = $this->page();
        $types = SiteSchema::sections();

        return view('livewire.website.page-editor', [
            'site' => $this->site(),
            'page' => $page,
            'sections' => $page->sections()->get(),
            'types' => $types,
            'fields' => $this->editingType ? $this->fields($this->editingType) : [],
            'itemFields' => $this->editingType ? ($types[$this->editingType]['items'] ?? []) : [],
        ]);
    }
}
