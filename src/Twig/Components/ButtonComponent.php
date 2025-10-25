<?php
namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\LiveProp;

#[AsLiveComponent('button')]
class ButtonComponent
{
    #[LiveProp]
    public string $label;
    #[LiveProp]
    public string $href = '#';
    #[LiveProp]
    public string $variant = 'primary'; // primary, secondary, accent, error, success, warning, info
    #[LiveProp]
    public ?string $icon = null;
    #[LiveProp]
    public ?string $extraClass = null;
    #[LiveProp]
    public bool $disabled = false;
}
