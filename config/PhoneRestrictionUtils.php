<?php

function isUnsupportedPhoneCountry($phone, $unsupportedDialCodes) {
    foreach ($unsupportedDialCodes as $dialCode) {
        if (strpos($phone, $dialCode) === 0) {
            return true;
        }
    }

    return false;
}
