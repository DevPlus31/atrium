import type { LucideIcon } from 'lucide-react';
import {
    Monitor,
    Moon,
    PanelLeft,
    PanelRight,
    PanelTop,
    Sun,
} from 'lucide-react';
import type { Appearance, ThemePreset } from '@/hooks/use-appearance';
import type { LayoutConfig } from '@/types/admin';

/**
 * Display options shared by the topbar settings menu and the command
 * palette. Values are the wire contract (docs/specs/theming.md).
 */
export const appearanceOptions: ReadonlyArray<{
    value: Appearance;
    label: string;
    icon: LucideIcon;
}> = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
];

export const themePresetOptions: ReadonlyArray<{
    value: ThemePreset;
    label: string;
}> = [
    { value: 'default', label: 'Default' },
    { value: 'ember', label: 'Ember' },
    { value: 'contrast', label: 'High contrast' },
];

export const navPlacementOptions: ReadonlyArray<{
    value: LayoutConfig['nav_placement'];
    label: string;
    icon: LucideIcon;
}> = [
    { value: 'sidebar-left', label: 'Sidebar start', icon: PanelLeft },
    { value: 'sidebar-right', label: 'Sidebar end', icon: PanelRight },
    { value: 'topbar', label: 'Topbar', icon: PanelTop },
];
