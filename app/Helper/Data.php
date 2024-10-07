<?php

use Carbon\Carbon;

if (!function_exists("is_all_data")) {

    function is_all_data()
    {
        return [
            'administrator',
            'Direktur Utama',
            'Distribution Channel (DC)',
            'Marketing Manager (MM)',
            'Marketing Support',
            'Operational Manager',
            'Sales Counter (SC)',
            'super-admin',
            'Support Bagian Distributor',
            'Support Bagian Kegiatan',
            'Support Distributor',
            'Support Kegiatan',
            'Support Supervisor',
            'User Jember',
        ];
    }
}

if (!function_exists("supervisor_data")) {

    function supervisor_data()
    {
        return [
            'Marketing Manager (MM)',
            'Marketing District Manager (MDM)',
            'Assistant MDM',
            'Regional Marketing Coordinator (RMC)',
        ];
    }
}

if (!function_exists("date_mysql")) {

    function date_mysql($date)
    {
        if (is_numeric($date)) {
            $timestamp = ($date - 25569) * 86400;
            $carbonDate = Carbon::createFromTimestamp($timestamp);
            return $carbonDate->format('Y-m-d');
        } else {
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                return "Invalid Date";
            } else {
                return date('Y-m-d', $timestamp);
            }
        }
    }
}

if (!function_exists('convertMonthToIndonesian')) {
    function convertMonthToIndonesian($monthNumber)
    {
        // Ensure the month number is an integer
        $monthNumber = (int) $monthNumber;

        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return isset($months[$monthNumber]) ? $months[$monthNumber] : 'Invalid month';
    }
}

