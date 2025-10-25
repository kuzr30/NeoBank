<?php
namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\LiveProp;

#[AsLiveComponent('card')]
class CardComponent
{
    #[LiveProp]
    public ?string $title = null;
    #[LiveProp]
    public ?string $subtitle = null;
    #[LiveProp]
    public ?string $footer = null;
    #[LiveProp]
    public ?string $extraClass = null;
    #[LiveProp]
    public ?array $actions = null; // tableau d'actions (boutons, liens)
}
