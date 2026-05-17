<?php

namespace App\Enums;

enum IdeaContext: string
{
    case Mainstream = 'mainstream';
    case Underground = 'underground';
    case Technical = 'technical';
    case Opinion = 'opinion';
    case Culture = 'culture';
    case InternetNiche = 'internet_niche';
    case BroadReading = 'broad_reading';
    case OffTopic = 'off_topic';

    public function label(): string
    {
        return match ($this) {
            self::Mainstream => 'Mainstream',
            self::Underground => 'Underground',
            self::Technical => 'Técnico',
            self::Opinion => 'Opinión',
            self::Culture => 'Cultura',
            self::InternetNiche => 'Internet + nicho',
            self::BroadReading => 'Lectura amplia',
            self::OffTopic => 'Fuera de tema',
        };
    }

    public function helper(): string
    {
        return match ($this) {
            self::Mainstream => 'Algo que mucha gente puede reconocer rápido.',
            self::Underground => 'Algo más específico para gente metida en la escena.',
            self::Technical => 'Tocar, grabar, reparar, construir o entender música/instrumentos.',
            self::Opinion => 'Una postura, crítica, take, comparación o juicio personal.',
            self::Culture => 'Escena, comportamientos, identidad, tribus o códigos del nicho.',
            self::InternetNiche => 'Tu nicho mezclado con memes, trends o lenguaje de internet.',
            self::BroadReading => 'Cuando una idea del nicho habla de algo más general.',
            self::OffTopic => 'Algo que no pertenece claramente a la cuenta.',
        };
    }
}