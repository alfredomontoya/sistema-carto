import { LayoutDashboard, Mail, Settings, Users, Boxes, Palette, UserRound } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

export interface NavItem {
    label: string;
    href: string;
    icon: LucideIcon;
    permission?: 'manage_users' | 'manage_areas' | 'manage_settings';
}

export interface NavSection {
    title: string;
    items: NavItem[];
}

export function navigation(permissions: {
    manage_users: boolean;
    manage_areas: boolean;
    manage_settings: boolean;
}): NavSection[] {
    const sections: NavSection[] = [
        {
            title: 'Principal',
            items: [
                { label: 'Panel', href: '/dashboard', icon: LayoutDashboard },
                {
                    label: 'Comunicaciones',
                    href: '/comunicaciones',
                    icon: Mail,
                },
            ],
        },
    ];

    const adminItems: NavItem[] = [];
    if (permissions.manage_users) {
        adminItems.push({ label: 'Usuarios', href: '/admin/users', icon: Users });
    }
    if (permissions.manage_areas) {
        adminItems.push({ label: 'Áreas', href: '/admin/areas', icon: Boxes });
    }
    if (permissions.manage_settings) {
        adminItems.push({ label: 'Ajustes', href: '/admin/settings', icon: Palette });
    }

    if (adminItems.length > 0) {
        sections.push({ title: 'Administración', items: adminItems });
    }

    sections.push({
        title: 'Cuenta',
        items: [{ label: 'Mi perfil', href: '/profile', icon: UserRound }],
    });

    return sections;
}
