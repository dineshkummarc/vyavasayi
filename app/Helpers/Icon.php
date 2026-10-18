<?php
namespace App\Helpers;

/**
 * Inline SVG icons.
 *
 * The icons live in public/icons/{ui,social}/NAME.svg  (the "SVG icon collection").
 * To add an icon: drop NAME.svg into public/icons/ui/ (or social/), then run
 *     php bin/build-icons.php
 * to refresh public/js/icons.js (the copy used by JavaScript). PHP picks new files up automatically.
 *
 * Usage in a view:   <?= icon('plus') ?>            <?= icon('check-circle', 'ok', 'color:var(--green)') ?>
 * Usage in JS:       Icons.svg('plus')              Icons.svg('check-circle', 'ok', 'color:var(--green)')
 *
 * Icons are sized with font-size (1em) and coloured with the text colour (currentColor), so they behave
 * like text: set font-size / color on the icon or on a parent.
 */
final class Icon
{
    /** @var array<string,string>|null name => file path */
    private static ?array $index = null;
    /** @var array<string,array{0:array,1:string}> name => [root attributes, inner markup] */
    private static array $parsed = [];

    /**
     * Old icon names -> collection names. Only used to translate names that may still be stored in the
     * database (e.g. expense_categories.icon) or in old links. New code should use collection names.
     */
    private const LEGACY = [
        'address-book' => 'address-book',
        'address-card' => 'id-card',
        'align-left' => 'align-left',
        'angles-left' => 'chevrons-left',
        'angles-right' => 'chevrons-right',
        'arrow-down' => 'arrow-down',
        'arrow-left' => 'arrow-left',
        'arrow-left-long' => 'arrow-left',
        'arrow-right' => 'arrow-right',
        'arrow-right-long' => 'arrow-right',
        'arrow-trend-down' => 'trending-down',
        'arrow-trend-up' => 'trending-up',
        'arrow-up' => 'arrow-up',
        'arrow-up-right-dots' => 'trending-up',
        'arrow-up-right-from-square' => 'external-link',
        'arrows-spin' => 'refresh',
        'arrows-turn-to-dots' => 'refresh',
        'award' => 'award',
        'ban' => 'ban',
        'barcode' => 'barcode',
        'bars' => 'menu',
        'behance' => 'behance',
        'bell' => 'bell',
        'bell-slash' => 'bell-off',
        'bolt' => 'zap',
        'book' => 'book',
        'book-open' => 'book-open',
        'box' => 'package',
        'box-open' => 'package-open',
        'box-xmark' => 'package-x',
        'boxes-stacked' => 'boxes',
        'briefcase' => 'briefcase',
        'building' => 'building',
        'bullhorn' => 'megaphone',
        'cake-candles' => 'cake',
        'calendar' => 'calendar',
        'calendar-check' => 'calendar-check',
        'calendar-day' => 'calendar',
        'calendar-days' => 'calendar',
        'camera' => 'camera',
        'car' => 'car-front',
        'cart-flatbed' => 'trolley',
        'cart-shopping' => 'shopping-cart',
        'cash-register' => 'cash-register',
        'chart-bar' => 'bar-chart',
        'chart-line' => 'chart-line',
        'check' => 'check',
        'check-circle' => 'check-circle',
        'check-double' => 'check-double',
        'chevron-down' => 'chevron-down',
        'chevron-left' => 'chevron-left',
        'chevron-right' => 'chevron-right',
        'circle' => 'circle',
        'circle-arrow-down' => 'circle-arrow-down',
        'circle-arrow-up' => 'circle-arrow-up',
        'circle-check' => 'check-circle',
        'circle-dot' => 'circle-dot',
        'circle-exclamation' => 'alert-circle',
        'circle-info' => 'info',
        'circle-question' => 'help-circle',
        'circle-xmark' => 'x-circle',
        'clock' => 'clock',
        'clock-rotate-left' => 'history',
        'code' => 'code',
        'code-branch' => 'git-branch',
        'coffee' => 'coffee',
        'coins' => 'coins',
        'copy' => 'copy',
        'credit-card' => 'credit-card',
        'crown' => 'crown',
        'desktop' => 'monitor',
        'dribbble' => 'dribbble',
        'droplet' => 'droplet',
        'ellipsis' => 'ellipsis',
        'envelope' => 'mail',
        'envelope-open-text' => 'mail-open',
        'eye' => 'eye',
        'eye-slash' => 'eye-off',
        'facebook' => 'facebook',
        'file-circle-minus' => 'file-minus',
        'file-circle-xmark' => 'file-x',
        'file-image' => 'file-image',
        'file-invoice' => 'invoice',
        'file-invoice-dollar' => 'invoice-dollar',
        'file-lines' => 'file-text',
        'file-pdf' => 'file-pdf',
        'file-pen' => 'file-edit',
        'fire' => 'flame',
        'floppy-disk' => 'save',
        'folder-plus' => 'folder-plus',
        'gauge' => 'gauge',
        'gear' => 'settings',
        'github' => 'github',
        'globe' => 'globe',
        'graduation-cap' => 'graduation-cap',
        'grip' => 'grid',
        'hand-holding-dollar' => 'hand-coins',
        'hashtag' => 'hash',
        'heart' => 'heart-filled',
        'home' => 'home',
        'hourglass-half' => 'hourglass',
        'house' => 'home',
        'id-badge' => 'id-card',
        'id-card' => 'id-card',
        'image' => 'image',
        'images' => 'images',
        'infinity' => 'infinity',
        'info-circle' => 'info',
        'instagram' => 'instagram',
        'key' => 'key',
        'language' => 'languages',
        'laptop' => 'laptop',
        'layer-group' => 'layers',
        'leaf' => 'leaf',
        'left-right-long' => 'arrows-left-right',
        'life-ring' => 'life-buoy',
        'link' => 'link',
        'linkedin' => 'linkedin',
        'list' => 'list',
        'list-check' => 'list-check',
        'location-dot' => 'map-pin',
        'lock' => 'lock',
        'magnifying-glass' => 'search',
        'medium' => 'medium',
        'minus' => 'minus',
        'mobile-screen-button' => 'smartphone',
        'money-bill' => 'banknote',
        'money-bill-wave' => 'banknote',
        'paint-brush' => 'paintbrush',
        'palette' => 'palette',
        'panorama' => 'image',
        'paper-plane' => 'send',
        'pen' => 'square-pen',
        'percent' => 'percent',
        'phone' => 'phone',
        'piggy-bank' => 'piggy-bank',
        'plus' => 'plus',
        'print' => 'printer',
        'qrcode' => 'qr-code',
        'receipt' => 'receipt',
        'rectangle-list' => 'list',
        'right-from-bracket' => 'logout',
        'right-to-bracket' => 'login',
        'rocket' => 'rocket',
        'rotate-left' => 'rotate-ccw',
        'rotate-right' => 'rotate-cw',
        'scale-balanced' => 'scale',
        'school' => 'school',
        'search' => 'search',
        'seedling' => 'sprout',
        'share-nodes' => 'share',
        'shield' => 'shield',
        'shield-check' => 'shield-check',
        'shield-halved' => 'shield',
        'shield-xmark' => 'shield-x',
        'sitemap' => 'sitemap',
        'sliders' => 'sliders',
        'spinner' => 'loader',
        'stack-overflow' => 'stackoverflow',
        'star' => 'star-filled',
        'store' => 'store',
        'tablet-screen-button' => 'tablet',
        'tag' => 'tag',
        'tags' => 'tags',
        'ticket' => 'ticket',
        'tiktok' => 'tiktok',
        'toggle-off' => 'toggle-off',
        'toggle-on' => 'toggle-on',
        'tools' => 'hammer',
        'trash' => 'trash',
        'triangle-exclamation' => 'alert-triangle',
        'truck' => 'truck',
        'truck-fast' => 'truck-fast',
        'truck-ramp-box' => 'package-return',
        'tshirt' => 'shirt',
        'upload' => 'upload',
        'user' => 'user',
        'user-check' => 'user-check',
        'user-pen' => 'user-edit',
        'user-plus' => 'user-plus',
        'user-slash' => 'user-off',
        'user-tie' => 'user-tie',
        'user-xmark' => 'user-x',
        'users' => 'users',
        'utensils' => 'utensils',
        'venus-mars' => 'gender',
        'wallet' => 'wallet',
        'wand-magic-sparkles' => 'sparkles',
        'warehouse' => 'warehouse',
        'water' => 'droplet',
        'whatsapp' => 'whatsapp',
        'wrench' => 'wrench',
        'x-twitter' => 'x',
        'xmark' => 'close',
        'youtube' => 'youtube'
    ];

    public static function dir(): string
    {
        return (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2)) . '/public/icons';
    }

    /** name => absolute file path, for every icon in the collection. */
    private static function index(): array
    {
        if (self::$index === null) {
            self::$index = [];
            foreach (['ui', 'social'] as $group) {
                foreach (glob(self::dir() . '/' . $group . '/*.svg') ?: [] as $file) {
                    self::$index[basename($file, '.svg')] = $file;
                }
            }
        }
        return self::$index;
    }

    /** All available icon names. */
    public static function names(): array
    {
        $n = array_keys(self::index());
        sort($n);
        return $n;
    }

    public static function exists(string $name): bool
    {
        return isset(self::index()[self::resolve($name)]);
    }

    /** Normalise a name: accepts "plus", "ui/plus", or an old stored name such as "fa-solid fa-xmark". */
    public static function resolve(string $name): string
    {
        $name = strtolower(trim($name));
        if ($name === '') return 'circle';
        if (str_contains($name, '/')) $name = substr($name, strrpos($name, '/') + 1);
        if (isset(self::index()[$name])) return $name;
        // old stored value: take the last "fa-xxx" token (ignoring fa-solid / fa-regular / fa-brands)
        if (preg_match_all('/fa-([a-z0-9-]+)/', $name, $m)) {
            foreach (array_reverse($m[1]) as $tok) {
                if (in_array($tok, ['solid', 'regular', 'brands', 'light', 'duotone', 'fw', 'xs', 'spin'], true)) continue;
                return self::LEGACY[$tok] ?? 'circle';
            }
        }
        return self::LEGACY[$name] ?? 'circle';
    }

    /**
     * Render an icon as inline SVG.
     *
     * @param string $name   icon name (see public/icons)
     * @param string $class  extra CSS classes (ic-xs makes it smaller, ic-spin makes it rotate)
     * @param string $style  inline style, e.g. 'color:var(--brand);font-size:20px'
     * @param array  $attrs  extra attributes, e.g. ['id' => 'x', 'onclick' => '...']
     */
    public static function svg(string $name, string $class = '', string $style = '', array $attrs = []): string
    {
        $name = self::resolve($name);
        $index = self::index();
        if (!isset($index[$name])) $name = 'circle';
        if (!isset(self::$parsed[$name])) {
            $raw = is_file($index[$name] ?? '') ? (string)file_get_contents($index[$name]) : '';
            if (!preg_match('/<svg\s+([^>]*)>(.*)<\/svg>/s', $raw, $m)) {
                return '';
            }
            preg_match_all('/([\w:-]+)="([^"]*)"/', $m[1], $am, PREG_SET_ORDER);
            $root = [];
            foreach ($am as $a) {
                if (in_array($a[1], ['xmlns', 'width', 'height'], true)) continue;
                $root[$a[1]] = $a[2];
            }
            self::$parsed[$name] = [$root, trim($m[2])];
        }
        [$root, $inner] = self::$parsed[$name];

        $esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
        $out = '<svg';
        foreach ($root as $k => $v) $out .= ' ' . $k . '="' . $esc($v) . '"';
        $out .= ' width="1em" height="1em"';
        $out .= ' class="ic ic-' . $name . ($class !== '' ? ' ' . $esc($class) : '') . '"';
        if ($style !== '') $out .= ' style="' . $esc($style) . '"';
        foreach ($attrs as $k => $v) {
            if (!preg_match('/^[a-zA-Z_:][\w:.-]*$/', (string)$k)) continue;
            $out .= ' ' . $k . '="' . $esc((string)$v) . '"';
        }
        return $out . ' aria-hidden="true" focusable="false">' . $inner . '</svg>';
    }

    /** Collection-name equivalents of old names, for migrations. */
    public static function legacyMap(): array
    {
        return self::LEGACY;
    }
}
