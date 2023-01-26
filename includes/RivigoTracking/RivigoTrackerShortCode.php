<?php

function rivigoTrackerShortCode()
{
    ob_start();
    include(__DIR__ . './../../public/partials/RivigoParcelShortCode.html');
    return ob_get_clean();
}