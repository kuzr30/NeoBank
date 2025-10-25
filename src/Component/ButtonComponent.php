<?php
namespace App\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('button')]
class ButtonComponent
{
    public string $label;
    public string $type = 'button';
    public string $variant = 'primary'; // primary, secondary, accent, error, success, warning, info
    public ?string $extraClass = null;
}
