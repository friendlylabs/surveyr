<?php

namespace App\Support;

use App\Models\Form;
use Illuminate\Validation\Rule;

/**
 * FormTheme
 *
 * The builder's theme choices as stored on a form (forms.theme): which style, layout, palette (or custom colour),
 * mode, font and background image. The SurveyJS theme itself is built from them in the browser
 * (resources/js/surveyjs/theme.js), whose lists these keys must match.
 */
class FormTheme
{
    const STYLES = ['classic', 'brutalist', 'soft', 'minimal'];

    const LAYOUTS = ['panels', 'panelless'];

    const PALETTES = ['surveyr', 'crimson', 'ember', 'saffron', 'lagoon', 'ocean', 'violet', 'berry', 'slate', 'custom'];

    const MODES = ['light', 'dark'];

    const FONTS = ['default', 'system', 'serif', 'mono'];

    /**
     * Validation rules for the choices. A background image must be one of the form's own uploads, so a theme can't
     * pull images (and their tracking) from elsewhere.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(Form $form): array
    {
        return [
            'style' => ['required', Rule::in(self::STYLES)],
            'layout' => ['required', Rule::in(self::LAYOUTS)],
            'palette' => ['required', Rule::in(self::PALETTES)],
            'color' => ['nullable', 'required_if:palette,custom', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'mode' => ['required', Rule::in(self::MODES)],
            'font' => ['required', Rule::in(self::FONTS)],
            'backgroundImage' => ['nullable', 'string', 'max:2048', 'starts_with:'.self::assetUrl($form, '')],
        ];
    }

    /**
     * The choices as stored: only known keys, the colour only with the custom palette.
     *
     * @param  array<string, mixed>  $choices
     * @return array<string, string|null>
     */
    public static function clean(array $choices): array
    {
        return [
            'style' => $choices['style'],
            'layout' => $choices['layout'],
            'palette' => $choices['palette'],
            'color' => $choices['palette'] === 'custom' ? strtolower($choices['color']) : null,
            'mode' => $choices['mode'],
            'font' => $choices['font'],
            'backgroundImage' => $choices['backgroundImage'] ?? null,
        ];
    }

    /** Where the form's uploaded images live on the public disk. */
    public static function assetDirectory(Form $form): string
    {
        return "forms/{$form->hash}";
    }

    /** The public URL of one of the form's uploads (or, with an empty name, of their folder). */
    public static function assetUrl(Form $form, string $name): string
    {
        return asset('storage/'.self::assetDirectory($form).'/'.$name);
    }
}
