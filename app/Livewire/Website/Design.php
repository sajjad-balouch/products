<?php

namespace App\Livewire\Website;

use App\Livewire\Website\Concerns\ManagesSite;
use App\Support\SiteSchema;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Website look')]
class Design extends Component
{
    use ManagesSite;

    public const TABS = ['design' => 'Brand and colours', 'header' => 'Header', 'footer' => 'Footer', 'contact' => 'Contact and campuses', 'popup' => 'Pop-up notice'];

    #[Url]
    public string $tab = '';

    public array $form = [];

    public array $items = [];

    protected function itemFields(): array
    {
        return SiteSchema::settingItems()[$this->tab]['fields'] ?? [];
    }

    public function mount(): void
    {
        $tabs = $this->site()->designTabs(Auth::user());
        abort_if($tabs === [], 403);

        $this->tab = in_array($this->tab, $tabs, true) ? $this->tab : $tabs[0];
        $this->load();
    }

    public function updatedTab(): void
    {
        abort_unless(isset(self::TABS[$this->tab]), 422);
        $this->need($this->tab);
        $this->resetValidation();
        $this->uploads = [];
        $this->load();
    }

    private function load(): void
    {
        $values = $this->site()->setting($this->tab);
        $this->form = SiteSchema::clean(SiteSchema::settings()[$this->tab], $values);
        $list = SiteSchema::settingItems()[$this->tab]['key'] ?? null;
        $this->loadItems($list ? ($values[$list] ?? []) : []);
    }

    public function save(): void
    {
        abort_unless(isset(self::TABS[$this->tab]), 422);
        $this->need($this->tab);
        $site = $this->site();
        $fields = SiteSchema::settings()[$this->tab];

        $itemFields = $this->itemFields();

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

        $settings = $site->settings ?? [];
        $settings[$this->tab] = SiteSchema::clean($fields, $this->form);

        if ($itemFields) {
            $settings[$this->tab][SiteSchema::settingItems()[$this->tab]['key']] = $this->cleanItems($itemFields);
        }

        $site->update(['settings' => $settings]);

        $this->dispatch('toast', message: 'Saved.');
    }

    public function render()
    {
        $site = $this->site();

        return view('livewire.website.design', [
            'site' => $site,
            'tabs' => array_intersect_key(self::TABS, array_flip($site->designTabs(Auth::user()))),
            'fields' => SiteSchema::settings()[$this->tab] ?? [],
            'itemFields' => $this->itemFields(),
            'list' => SiteSchema::settingItems()[$this->tab] ?? null,
        ]);
    }
}
