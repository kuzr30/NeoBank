<?php
namespace App\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('card')]
class CardComponent
{
    public ?string $title = null;
    public ?string $subtitle = null;
    public ?string $extraClass = null;
}
