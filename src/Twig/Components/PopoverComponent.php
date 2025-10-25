<?php
namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\LiveProp;

#[AsLiveComponent('popover')]
class PopoverComponent
{
    #[LiveProp]
    public ?string $triggerLabel = 'Ouvrir';
    #[LiveProp]
    public ?string $message = null;
    #[LiveProp]
    public ?string $yesLabel = 'Oui';
    #[LiveProp]
    public ?string $noLabel = 'Non';
    #[LiveProp]
    public ?string $yesHref = '#';
    #[LiveProp]
    public ?string $noHref = '#';
    #[LiveProp]
    public ?string $extraClass = null;
}
