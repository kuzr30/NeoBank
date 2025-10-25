<?php

namespace App\Enum;

enum AssuranceType: string
{
    case AUTO = 'auto';
    case HABITATION = 'habitation';
    case SANTE = 'sante';
    case VIE = 'vie';
    case PRET = 'pret';
    case VOYAGE = 'voyage';
    case PRO = 'pro';
    case CYBER = 'cyber';
    case DECENNALE = 'decennale';
    case RC = 'rc';
    case FLOTTE = 'flotte';
    case GARAGE = 'garage';

    public function getLabel(): string
    {
        return match($this) {
            self::AUTO => 'enums.assurance_type.auto.label',
            self::HABITATION => 'enums.assurance_type.habitation.label',
            self::SANTE => 'enums.assurance_type.sante.label',
            self::VIE => 'enums.assurance_type.vie.label',
            self::PRET => 'enums.assurance_type.pret.label',
            self::VOYAGE => 'enums.assurance_type.voyage.label',
            self::PRO => 'enums.assurance_type.pro.label',
            self::CYBER => 'enums.assurance_type.cyber.label',
            self::DECENNALE => 'enums.assurance_type.decennale.label',
            self::RC => 'enums.assurance_type.rc.label',
            self::FLOTTE => 'enums.assurance_type.flotte.label',
            self::GARAGE => 'enums.assurance_type.garage.label',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::AUTO => 'enums.assurance_type.auto.description',
            self::HABITATION => 'enums.assurance_type.habitation.description',
            self::SANTE => 'enums.assurance_type.sante.description',
            self::VIE => 'enums.assurance_type.vie.description',
            self::PRET => 'enums.assurance_type.pret.description',
            self::VOYAGE => 'enums.assurance_type.voyage.description',
            self::PRO => 'enums.assurance_type.pro.description',
            self::CYBER => 'enums.assurance_type.cyber.description',
            self::DECENNALE => 'enums.assurance_type.decennale.description',
            self::RC => 'enums.assurance_type.rc.description',
            self::FLOTTE => 'enums.assurance_type.flotte.description',
            self::GARAGE => 'enums.assurance_type.garage.description',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::AUTO => 'heroicons:truck',
            self::HABITATION => 'heroicons:home',
            self::SANTE => 'heroicons:heart',
            self::VIE => 'heroicons:gift',
            self::PRET => 'heroicons:building-library',
            self::VOYAGE => 'heroicons:globe-alt',
            self::PRO => 'heroicons:briefcase',
            self::CYBER => 'heroicons:shield-check',
            self::DECENNALE => 'heroicons:wrench-screwdriver',
            self::RC => 'heroicons:users',
            self::FLOTTE => 'heroicons:truck',
            self::GARAGE => 'heroicons:cog-6-tooth',
        };
    }

    public static function getAllTypes(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function getTypePattern(): string
    {
        return implode('|', self::getAllTypes());
    }
}
