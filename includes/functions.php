<?php
function calculate_age($birthdate)
{
    $birth = new DateTime($birthdate);
    $today = new DateTime();

    $age = $today->diff($birth)->y;

    return $age;
}