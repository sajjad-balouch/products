<?php

namespace App\Livewire\Website\Concerns;

use App\Models\Site;
use App\Models\SiteMedia;
use App\Services\SiteBuilder;
use App\Support\SiteSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/** The signed-in user's website, the permission checks and picture uploads shared by the manager screens. */
trait ManagesSite
{
    use WithFileUploads;

    /** Files waiting to be saved, by field. */
    public array $uploads = [];

    private ?Site $resolvedSite = null;

    protected function site(): Site
    {
        return $this->resolvedSite ??= app(SiteBuilder::class)->forUser(Auth::user());
    }

    protected function need(string $permission): void
    {
        abort_unless($this->site()->allows(Auth::user(), $permission), 403);
    }

    protected function uploadRules(): array
    {
        return ['uploads.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096']];
    }

    /** Saves a chosen file and returns its id, or the value the field already had. */
    protected function imageFor(string $uploadKey, mixed $current): mixed
    {
        $file = $this->uploads[$uploadKey] ?? null;

        if (! $file instanceof TemporaryUploadedFile) {
            return $current;
        }

        $site = $this->site();
        $path = $file->store('site-media/'.$site->id, 'local');
        unset($this->uploads[$uploadKey]);

        return SiteMedia::create(['site_id' => $site->id, 'path' => $path, 'original_name' => $file->getClientOriginalName()])->id;
    }

    /** @return array<string, array<string, mixed>> fields of one row in the list being edited, on screens that have one */
    protected function itemFields(): array
    {
        return [];
    }

    public function addItem(): void
    {
        $fields = $this->itemFields();
        abort_unless($fields, 422);

        $this->items[] = SiteSchema::clean($fields, []) + ['_id' => Str::random(8)];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function moveItem(int $index, int $direction): void
    {
        $to = $index + ($direction < 0 ? -1 : 1);

        if (isset($this->items[$index], $this->items[$to])) {
            [$this->items[$index], $this->items[$to]] = [$this->items[$to], $this->items[$index]];
        }
    }

    /** Saves the pictures chosen for each row and returns the rows ready to store. A row left empty is dropped. */
    protected function cleanItems(array $fields): array
    {
        foreach ($this->items as $i => $item) {
            foreach ($fields as $key => $field) {
                if ($field['type'] === 'image') {
                    $this->items[$i][$key] = $this->imageFor($item['_id'].'_'.$key, $item[$key] ?? null);
                }
            }
        }

        return collect($this->items)->map(fn ($item) => SiteSchema::clean($fields, $item))
            ->filter(fn ($item) => array_filter($item))->values()->all();
    }

    protected function loadItems(array $rows): void
    {
        $this->items = collect($rows)->map(fn ($item) => $item + ['_id' => Str::random(8)])->values()->all();
    }

    public function clearImage(string $path): void
    {
        abort_unless(preg_match('/^(form\.\w+|items\.\d+\.\w+)$/', $path), 422);

        $parts = explode('.', $path);

        if ($parts[0] === 'form') {
            $this->form[$parts[1]] = null;
        } else {
            $this->items[(int) $parts[1]][$parts[2]] = null;
        }
    }
}
