<?php

namespace EasyPack\Entities\Settings;

use EasyPack\Entities\BaseRepository;
use EasyPack\Models\SettingGroup;

class SettingGroupsRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return SettingGroup::class;
    }

    /**
     * Find a group by slug.
     */
    public function findBySlug(string $slug): ?SettingGroup
    {
        return $this->findFirstBy('slug', $slug);
    }

    /**
     * Get all groups with their settings.
     */
    public function getAllWithSettings()
    {
        return $this->with('settings')->orderBy('sort_order')->all();
    }

    /**
     * Get a group with its settings.
     */
    public function getWithSettings(int $id): ?SettingGroup
    {
        return $this->with('settings')->find($id);
    }

    /**
     * Get a group by slug with its settings.
     */
    public function getBySlugWithSettings(string $slug): ?SettingGroup
    {
        return $this->with('settings')
            ->where('slug', $slug)
            ->fresh()
            ->applyRelations()
            ->first();
    }

    /**
     * Create a new group with settings.
     */
    public function createWithSettings(array $groupData, array $settings = []): SettingGroup
    {
        $group = $this->create($groupData);

        foreach ($settings as $setting) {
            $setting['setting_group_id'] = $group->id;
            $group->settings()->create($setting);
        }

        return $group->load('settings');
    }

    /**
     * Get groups sorted by sort_order.
     */
    public function getSorted()
    {
        return $this->orderBy('sort_order')->all();
    }

    /**
     * Reorder groups.
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $order => $id) {
            SettingGroup::where('id', $id)->update(['sort_order' => $order]);
        }
    }
}
