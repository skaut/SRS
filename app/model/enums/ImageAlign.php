<?php

namespace App\Model\Enums;


class ImageAlign
{
    const LEFT = 'left';
    const RIGHT = 'right';
    const CENTER = 'center';

    public static $aligns = [
        self::LEFT,
        self::RIGHT,
        self::CENTER
    ];
}