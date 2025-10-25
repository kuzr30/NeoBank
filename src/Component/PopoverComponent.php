<?php
namespace App\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('popover')]
class PopoverComponent
{
    public ?string $triggerLabel = null;
    public ?string $extraClass = null;
}
