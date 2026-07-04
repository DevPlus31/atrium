import type { LucideIcon } from 'lucide-react';
import {
    Activity,
    Archive,
    BadgeCheck,
    Banknote,
    Bell,
    BookOpen,
    Box,
    Boxes,
    Briefcase,
    Bug,
    Building2,
    Calendar,
    ChartBar,
    ChartLine,
    ChartPie,
    Circle,
    CircleQuestionMark,
    ClipboardList,
    Clock,
    CreditCard,
    Database,
    Download,
    Eye,
    Files,
    FileText,
    Flag,
    Folder,
    FolderOpen,
    Gauge,
    GitBranch,
    Globe,
    Heart,
    History,
    House,
    Image,
    Inbox,
    KeyRound,
    Landmark,
    Languages,
    LayoutDashboard,
    Layers,
    LayoutGrid,
    LifeBuoy,
    List,
    ListChecks,
    Lock,
    Mail,
    MapPin,
    MessageSquare,
    Newspaper,
    Package,
    Palette,
    Plug,
    Puzzle,
    Receipt,
    RefreshCw,
    Rocket,
    Scale,
    ScrollText,
    Search,
    Send,
    Server,
    Settings,
    Settings2,
    Shield,
    ShieldCheck,
    ShoppingBag,
    ShoppingCart,
    SlidersHorizontal,
    Sparkles,
    Star,
    Table,
    Tag,
    Tags,
    Terminal,
    TrendingUp,
    TriangleAlert,
    Truck,
    Upload,
    User,
    UserCog,
    Users,
    Wallet,
    Webhook,
    Wrench,
    Zap,
} from 'lucide-react';
import type { NavItem } from '@/types/admin';

/**
 * Curated icon map for server-driven navigation. Keyed by kebab-case lucide
 * icon name so the bundle only ships icons the admin actually uses.
 */
const navIconMap: Record<string, LucideIcon> = {
    activity: Activity,
    archive: Archive,
    'badge-check': BadgeCheck,
    banknote: Banknote,
    bell: Bell,
    'book-open': BookOpen,
    box: Box,
    boxes: Boxes,
    briefcase: Briefcase,
    bug: Bug,
    'building-2': Building2,
    calendar: Calendar,
    'chart-bar': ChartBar,
    'chart-line': ChartLine,
    'chart-pie': ChartPie,
    circle: Circle,
    'clipboard-list': ClipboardList,
    clock: Clock,
    'credit-card': CreditCard,
    database: Database,
    download: Download,
    eye: Eye,
    'file-text': FileText,
    files: Files,
    flag: Flag,
    folder: Folder,
    'folder-open': FolderOpen,
    gauge: Gauge,
    'git-branch': GitBranch,
    globe: Globe,
    heart: Heart,
    history: History,
    house: House,
    image: Image,
    inbox: Inbox,
    'key-round': KeyRound,
    landmark: Landmark,
    languages: Languages,
    layers: Layers,
    'layout-dashboard': LayoutDashboard,
    'layout-grid': LayoutGrid,
    'life-buoy': LifeBuoy,
    list: List,
    'list-checks': ListChecks,
    lock: Lock,
    mail: Mail,
    'map-pin': MapPin,
    'message-square': MessageSquare,
    newspaper: Newspaper,
    package: Package,
    palette: Palette,
    plug: Plug,
    puzzle: Puzzle,
    receipt: Receipt,
    'refresh-cw': RefreshCw,
    rocket: Rocket,
    scale: Scale,
    'scroll-text': ScrollText,
    search: Search,
    send: Send,
    server: Server,
    settings: Settings,
    'settings-2': Settings2,
    shield: Shield,
    'shield-check': ShieldCheck,
    'shopping-bag': ShoppingBag,
    'shopping-cart': ShoppingCart,
    'sliders-horizontal': SlidersHorizontal,
    sparkles: Sparkles,
    star: Star,
    table: Table,
    tag: Tag,
    tags: Tags,
    terminal: Terminal,
    'trending-up': TrendingUp,
    'triangle-alert': TriangleAlert,
    truck: Truck,
    upload: Upload,
    user: User,
    'user-cog': UserCog,
    users: Users,
    wallet: Wallet,
    webhook: Webhook,
    wrench: Wrench,
    zap: Zap,
};

/**
 * Resolves a server-provided icon name (kebab-case or PascalCase) to a
 * lucide component. Null renders a neutral placeholder so item labels stay
 * aligned; unknown names render an explicit fallback.
 */
export function resolveNavIcon(name: string | null): LucideIcon {
    if (name === null || name.trim() === '') {
        return Circle;
    }

    const key = name
        .trim()
        .replaceAll(/([a-z\d])([A-Z])/g, '$1-$2')
        .replaceAll(/[\s_]+/g, '-')
        .toLowerCase();

    return navIconMap[key] ?? CircleQuestionMark;
}

export type NavGroup = {
    label: string | null;
    items: NavItem[];
};

/**
 * Groups pre-sorted nav items by their `group` key, preserving server order.
 * Items with a null group form the top-level (unlabeled) section.
 */
export function groupNavItems(items: NavItem[]): NavGroup[] {
    const groups: NavGroup[] = [];
    const byLabel = new Map<string | null, NavGroup>();

    for (const item of items) {
        let group = byLabel.get(item.group);

        if (!group) {
            group = { label: item.group, items: [] };
            byLabel.set(item.group, group);
            groups.push(group);
        }

        group.items.push(item);
    }

    return groups;
}
