<?php

// locale: Русский (Russia) (ru_RU)
// author: Oleg Bogdanov https://github.com/wormen

return array(
    "months"        => explode('_', 'Январь_Февраль_Март_Апрель_Май_Июнь_Июль_Август_Сентябрь_Октябрь_Ноябрь_Декабрь'),
    "monthsShort"   => explode('_', 'Янв_Фев_Мрт_Апр_Май_Июн_Июл_Авг_Сен_Окт_Нбр_Дек'),
    "weekdays"      => explode('_', 'Понедельник_Вторник_Среда_Четверг_Пятница_Суббота_Воскресенье'),
    "weekdaysShort" => explode('_', 'Пн_Вт_Ср_Чт_Пт_Сб_Вс'),
    "calendar"      => array(
        "sameDay"  => '[Сегодня]',
        "nextDay"  => '[Завтра]',
        "lastDay"  => '[Вчера]',
        "lastWeek" => '[Прошлой] l',
        "sameElse" => 'l',
        "withTime" => '[at] H:i',
        "default"  => 'd/m/Y',
    ),
    "relativeTime"  => array(
        "future" => 'в %s',
        "past"   => '%s назад',
        "s"      => 'несколько секунд',
        "m"      => 'минуту',
        "mm"     => '%d минут',
        "h"      => 'час',
        "hh"     => '%d часов',
        "d"      => 'день',
        "dd"     => '%d дней',
        "M"      => 'месяц',
        "MM"     => '%d месяцев',
        "y"      => 'год',
        "yy"     => '%d лет',
    ),
    "ordinal"       => function ($number)
    {
        $n = $number % 100;
        $ends = array('ой', 'ый', 'ой', 'ий', 'ый', 'ый', 'ой', 'ой', 'ой', 'ый');

        if ($n >= 11 && $n <= 13)
        {
            return $number . '[th]';
        }

        return $number . '[' . $ends[$number % 10] . ']';
    },
    "week"          => array(
        "dow" => 1, // Monday is the first day of the week.
        "doy" => 4  // The week that contains Jan 4th is the first week of the year.
    ),
);